<?php

namespace App\Support\LiveClasses;

use App\Models\CourseOffering;
use App\Models\CourseOfferingLecturerAllocation;
use App\Models\CourseRegistration;
use App\Models\LiveClass;
use App\Models\User;
use App\Support\Permissions\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Authoritative Offering-scoped Live Class participation and staffing checks. */
class LiveClassAccessService
{
    private const VIEW_CAPABILITY = 'live_classes.view';
    private const MANAGE_CAPABILITIES = ['live_classes.create', 'live_classes.manage_all'];

    public function isOfferingBacked(LiveClass $class): bool
    {
        return $class->course_offering_id !== null;
    }

    public function canStudentViewClass(User $user, LiveClass $class): bool
    {
        return (bool) $class->is_published
            && $class->status !== LiveClass::STATUS_CANCELLED
            && $this->studentOfferingPermitsHistoricalAccess($class)
            && $this->confirmedStudentRegistration($user, $class);
    }

    public function confirmedStudentRegistration(User $user, LiveClass $class): bool
    {
        return (int) $user->role_id === 7
            && $user->account_status !== 'disable'
            && (int) $user->school_id === (int) $class->school_id
            && $this->offering($class) !== null
            && CourseRegistration::query()->where('school_id', $class->school_id)
                ->where('course_offering_id', $class->course_offering_id)
                ->where('student_id', $user->id)
                ->where('status', CourseRegistration::STATUS_CONFIRMED)->exists();
    }

    public function confirmedOfferingIdsQuery(User $user, int $schoolId): Builder
    {
        return CourseRegistration::query()->select('course_offering_id')
            ->where('school_id', $schoolId)
            ->where('student_id', $user->id)
            ->where('status', CourseRegistration::STATUS_CONFIRMED)
            ->whereNotNull('course_offering_id')
            ->whereIn('student_id', User::query()->select('id')->where('school_id', $schoolId)
                ->where('role_id', 7)
                ->where(function ($query): void {
                    $query->whereNull('account_status')->orWhere('account_status', '!=', 'disable');
                }))
            ->whereIn('course_offering_id', CourseOffering::query()
                ->select('id')
                ->where('school_id', $schoolId)
                ->whereIn('status', [
                    CourseOffering::STATUS_OPEN,
                    CourseOffering::STATUS_IN_PROGRESS,
                    CourseOffering::STATUS_COMPLETED,
                ]));
    }

    public function canStudentJoin(User $user, LiveClass $class, ?Carbon $now = null): bool
    {
        if (! $this->canStudentViewClass($user, $class)
            || ! in_array($class->status, [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE], true)
            || ! $this->withinJoinWindow($class, $now)) return false;
        return $this->offeringAllowsOperations($class);
    }

    public function canStudentViewMaterials(User $user, LiveClass $class): bool
    {
        return $this->canStudentViewClass($user, $class);
    }

    public function canStudentViewRecording(User $user, LiveClass $class): bool
    {
        return $this->canStudentViewClass($user, $class);
    }

    /** Historical display uses allocation validity on the scheduled date. */
    public function canLecturerView(User $user, LiveClass $class): bool
    {
        return $this->hasCapability($user, self::VIEW_CAPABILITY)
            && $this->allocation($user, $class, false, $this->meetingDate($class)) !== null;
    }

    /** Current mutation requires current allocation and an operational Offering. */
    public function canLecturerManage(User $user, LiveClass $class): bool
    {
        return $this->hasAnyCapability($user, self::MANAGE_CAPABILITIES)
            && ! in_array($class->status, [LiveClass::STATUS_CANCELLED, LiveClass::STATUS_ENDED], true)
            && $this->offeringAllowsOperations($class)
            && $this->allocation($user, $class, true, now()->toDateString()) !== null;
    }

    public function canLecturerCreateForOffering(User $user, CourseOffering $offering, ?Carbon $date = null): bool
    {
        $date ??= now();
        return (int) $user->school_id === (int) $offering->school_id
            && $this->hasCapability($user, 'live_classes.create')
            && in_array($offering->status, [CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true)
            && $this->activeManagerAllocation($user, $offering, $date->toDateString()) !== null;
    }

    public function canAdminCreateForOffering(User $user, CourseOffering $offering): bool
    {
        $adminRole = in_array((int) $user->role_id, [PermissionService::SUPER_ADMIN, PermissionService::SCHOOL_ADMIN], true);
        return (int) $user->school_id === (int) $offering->school_id
            && $this->hasCapability($user, 'live_classes.create')
            && ($adminRole || $this->hasCapability($user, 'live_classes.manage_all'))
            && in_array($offering->status, [CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true);
    }

    /** Current, active Primary/Co allocations available as normal facilitators. */
    public function activeManagerAllocationsForOffering(CourseOffering $offering, ?Carbon $date = null)
    {
        $date ??= now();
        return CourseOfferingLecturerAllocation::query()
            ->where('school_id', $offering->school_id)
            ->where('course_offering_id', $offering->id)
            ->whereIn('role', [CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER, CourseOfferingLecturerAllocation::ROLE_CO_LECTURER])
            ->where('status', CourseOfferingLecturerAllocation::STATUS_ACTIVE)
            ->whereDate('starts_on', '<=', $date->toDateString())
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date->toDateString()))
            ->whereHas('lecturer', fn ($query) => $query->where('school_id', $offering->school_id)
                ->where(function ($active) {
                    $active->whereNull('account_status')->orWhere('account_status', '!=', 'disable');
                }))
            ->with('lecturer')
            ->get();
    }

    public function canLecturerManageMaterials(User $user, LiveClass $class): bool
    {
        return $this->canLecturerManage($user, $class);
    }

    /** Any valid allocation role may join; only Primary/Co may host. */
    public function canLecturerJoin(User $user, LiveClass $class, ?Carbon $now = null): bool
    {
        return $this->hasCapability($user, self::VIEW_CAPABILITY)
            && (bool) $class->is_published
            && in_array($class->status, [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE], true)
            && $this->offeringAllowsOperations($class)
            && $this->withinJoinWindow($class, $now)
            && $this->allocation($user, $class, true, $this->meetingDate($class), false, false) !== null;
    }

    public function canLecturerHost(User $user, LiveClass $class, ?Carbon $now = null): bool
    {
        return $this->hasAnyCapability($user, self::MANAGE_CAPABILITIES)
            && (bool) $class->is_published
            && in_array($class->status, [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE], true)
            && $this->offeringAllowsOperations($class)
            && $this->withinJoinWindow($class, $now)
            && $this->allocation($user, $class, true, ($now ?? now())->toDateString(), true) !== null
            && $this->allocation($user, $class, true, $this->meetingDate($class), true) !== null;
    }

    public function canTenantAdmin(User $user, LiveClass $class, string $capability = 'live_classes.manage_all'): bool
    {
        return in_array((int) $user->role_id, [PermissionService::SUPER_ADMIN, PermissionService::SCHOOL_ADMIN], true)
            && $this->canAdminForTenant($user, (int) $class->school_id, $capability);
    }

    public function canTenantAdminJoin(User $user, LiveClass $class, ?Carbon $now = null): bool
    {
        return $this->canTenantAdmin($user, $class, 'live_classes.manage_all')
            && (bool) $class->is_published
            && in_array($class->status, [LiveClass::STATUS_SCHEDULED, LiveClass::STATUS_LIVE], true)
            && $this->offeringAllowsOperations($class)
            && $this->withinJoinWindow($class, $now);
    }

    public function canTenantAdminManage(User $user, LiveClass $class): bool
    {
        return $this->canTenantAdmin($user, $class, 'live_classes.manage_all')
            && ! in_array($class->status, [LiveClass::STATUS_CANCELLED, LiveClass::STATUS_ENDED], true)
            && $this->offeringAllowsOperations($class);
    }

    public function canAdminForTenant(User $user, int $schoolId, string $capability): bool
    {
        $platformAdmin = (int) $user->role_id === PermissionService::SUPER_ADMIN;
        return ($platformAdmin || (int) $user->school_id === $schoolId)
            && $this->hasCapability($user, $capability);
    }

    public function canViewAllOfferingClasses(User $user, int $schoolId): bool
    {
        $tenantAdmin = in_array((int) $user->role_id, [PermissionService::SUPER_ADMIN, PermissionService::SCHOOL_ADMIN], true);
        return ($tenantAdmin && $this->canAdminForTenant($user, $schoolId, self::VIEW_CAPABILITY))
            || $this->canAdminForTenant($user, $schoolId, 'live_classes.manage_all');
    }

    public function lecturerVisibleClassIdsQuery(User $user, int $schoolId): Builder
    {
        $query = DB::table('live_classes as access_lc')
            ->join('course_offering_lecturer_allocations as access_alloc', function ($join): void {
                $join->on('access_alloc.course_offering_id', '=', 'access_lc.course_offering_id')
                    ->on('access_alloc.school_id', '=', 'access_lc.school_id');
            })
            ->where('access_lc.school_id', $schoolId)
            ->where('access_alloc.school_id', $schoolId)
            ->where('access_alloc.user_id', $user->id)
            ->whereIn('access_alloc.role', CourseOfferingLecturerAllocation::ROLES)
            ->whereIn('access_alloc.status', [CourseOfferingLecturerAllocation::STATUS_ACTIVE, CourseOfferingLecturerAllocation::STATUS_ENDED])
            ->whereRaw('COALESCE(DATE(access_lc.scheduled_at), access_lc.start_date) >= access_alloc.starts_on')
            ->where(function (Builder $q): void {
                $q->whereNull('access_alloc.ends_on')
                    ->orWhereRaw('COALESCE(DATE(access_lc.scheduled_at), access_lc.start_date) <= access_alloc.ends_on');
            })
            ->select('access_lc.id');

        if (! $this->hasCapability($user, self::VIEW_CAPABILITY)) {
            $query->whereRaw('1 = 0');
        }
        return LiveClass::query()->whereIn('id', $query);
    }

    public function withinJoinWindow(LiveClass $class, ?Carbon $now = null): bool
    {
        if (! $class->scheduled_at || ! $class->ends_at) return false;
        $now ??= now();
        return $now->betweenIncluded(
            $class->scheduled_at->copy()->subMinutes(15),
            $class->ends_at->copy()->addMinutes(15)
        );
    }

    public function offeringAllowsOperations(LiveClass $class): bool
    {
        $offering = $this->offering($class);
        return $offering !== null
            && in_array($offering->status, [CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true);
    }

    private function offering(LiveClass $class): ?CourseOffering
    {
        if (! $class->course_offering_id) return null;
        return CourseOffering::query()->where('school_id', $class->school_id)->whereKey($class->course_offering_id)->first();
    }

    private function studentOfferingPermitsHistoricalAccess(LiveClass $class): bool
    {
        $offering = $this->offering($class);
        if (! $offering) return false;
        if (in_array($offering->status, [CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true)) return true;
        return $offering->status === CourseOffering::STATUS_COMPLETED
            && $class->computed_status === LiveClass::STATUS_ENDED;
    }

    private function meetingDate(LiveClass $class): string
    {
        return $class->scheduled_at?->toDateString() ?: $class->start_date?->toDateString() ?: now()->toDateString();
    }

    private function allocation(User $user, LiveClass $class, bool $operational, string $date, bool $host = false, bool $manager = true): ?CourseOfferingLecturerAllocation
    {
        if ((int) $user->school_id !== (int) $class->school_id || ! $this->offering($class)) return null;
        $query = CourseOfferingLecturerAllocation::query()->where('school_id', $class->school_id)
            ->where('course_offering_id', $class->course_offering_id)
            ->where('user_id', $user->id)
            ->whereDate('starts_on', '<=', $date)
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date));
        $query->whereIn('status', $operational
            ? [CourseOfferingLecturerAllocation::STATUS_ACTIVE]
            : [CourseOfferingLecturerAllocation::STATUS_ACTIVE, CourseOfferingLecturerAllocation::STATUS_ENDED]);
        if ($operational && $manager && ! $host) {
            $query->whereIn('role', [
                CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER,
                CourseOfferingLecturerAllocation::ROLE_CO_LECTURER,
            ]);
        }
        if ($host) {
            $query->whereIn('role', [CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER, CourseOfferingLecturerAllocation::ROLE_CO_LECTURER]);
        }
        return $query->first();
    }

    private function activeManagerAllocation(User $user, CourseOffering $offering, string $date): ?CourseOfferingLecturerAllocation
    {
        return CourseOfferingLecturerAllocation::query()
            ->where('school_id', $offering->school_id)
            ->where('course_offering_id', $offering->id)
            ->where('user_id', $user->id)
            ->whereIn('role', [CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER, CourseOfferingLecturerAllocation::ROLE_CO_LECTURER])
            ->where('status', CourseOfferingLecturerAllocation::STATUS_ACTIVE)
            ->whereDate('starts_on', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))
            ->first();
    }

    private function hasCapability(User $user, string $capability): bool
    {
        return app(PermissionService::class)->allows($user, $capability);
    }

    private function hasAnyCapability(User $user, array $capabilities): bool
    {
        return app(PermissionService::class)->allowsAny($user, $capabilities);
    }
}
