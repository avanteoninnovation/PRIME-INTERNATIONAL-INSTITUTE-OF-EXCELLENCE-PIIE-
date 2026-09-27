<?php

namespace App\Support\LiveClasses;

use App\Models\AuditLog;
use App\Models\CourseOffering;
use App\Models\LiveClass;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

/**
 * Authoritative foundation operations for Offering-backed Live Classes.
 * Lecturer-allocation and participant authorization are intentionally handled
 * by a later integration phase.
 */
class LiveClassService
{
    private const MEETING_FIELDS = [
        'title', 'description', 'teacher_id', 'platform', 'meeting_url',
        'meeting_id', 'meeting_password', 'scheduled_at', 'ends_at',
        'start_date', 'start_time', 'end_time', 'timezone', 'status',
        'is_published', 'attendance_enabled', 'recording_url',
    ];

    private const RESERVED_CONTEXT_FIELDS = [
        'school_id', 'course_offering_id', 'programme_id', 'academic_session_id',
        'academic_year_id', 'academic_period_id', 'curriculum_id',
        'curriculum_membership_id', 'teaching_group_id',
    ];

    public function createForOffering(User $actor, int $courseOfferingId, array $attributes): LiveClass
    {
        $this->assertContextInput($attributes);
        $schoolId = (int) $actor->school_id;
        if ($schoolId <= 0) {
            throw new DomainException('An authenticated tenant context is required to create a Live Class.');
        }

        return DB::transaction(function () use ($actor, $schoolId, $courseOfferingId, $attributes): LiveClass {
            $offering = $this->lockOffering($schoolId, $courseOfferingId);
            $this->assertOperationalOffering($offering);

            if (array_key_exists('subject_id', $attributes)
                && (int) $attributes['subject_id'] !== (int) $offering->subject_id) {
                throw new DomainException('The supplied subject conflicts with the Course Offering subject.');
            }

            if (! empty($attributes['teacher_id'])) {
                $this->assertFacilitatorAllocation($offering, (int) $attributes['teacher_id'], $attributes);
            }

            $payload = $this->meetingAttributes($attributes) + [
                'school_id' => $offering->school_id,
                'course_offering_id' => $offering->id,
                'subject_id' => $offering->subject_id,
                'programme_id' => null,
                'academic_session_id' => null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ];

            $liveClass = LiveClass::create($payload);
            $this->recordAudit('create', $liveClass, null, $liveClass->only(['course_offering_id', 'subject_id']));

            return $liveClass->setRelation('courseOffering', $offering);
        });
    }

    /** Update meeting details without allowing academic-context reassignment. */
    public function updateOfferingMeeting(User $actor, int $liveClassId, array $attributes): LiveClass
    {
        $this->assertContextInput($attributes);
        $schoolId = (int) $actor->school_id;

        return DB::transaction(function () use ($actor, $schoolId, $liveClassId, $attributes): LiveClass {
            $identity = LiveClass::query()->where('school_id', $schoolId)->whereKey($liveClassId)->first();
            if (! $identity || ! $identity->course_offering_id) {
                throw new DomainException('The Offering-backed Live Class was not found for this tenant.');
            }

            $offering = $this->lockOffering($schoolId, (int) $identity->course_offering_id);
            $this->assertOperationalOffering($offering);
            if (array_key_exists('subject_id', $attributes)
                && (int) $attributes['subject_id'] !== (int) $offering->subject_id) {
                throw new DomainException('The supplied subject conflicts with the Course Offering subject.');
            }
            if (! empty($attributes['teacher_id'])) {
                $this->assertFacilitatorAllocation($offering, (int) $attributes['teacher_id'], $attributes);
            }
            $liveClass = LiveClass::query()->where('school_id', $schoolId)->whereKey($liveClassId)->lockForUpdate()->firstOrFail();
            $before = $liveClass->only(['course_offering_id', 'subject_id', 'scheduled_at', 'ends_at', 'status']);

            $liveClass->fill($this->meetingAttributes($attributes));
            $liveClass->updated_by = $actor->id;
            $liveClass->save();
            $this->recordAudit('update', $liveClass, $before, $liveClass->only(['course_offering_id', 'subject_id', 'scheduled_at', 'ends_at', 'status']));

            return $liveClass->setRelation('courseOffering', $offering);
        });
    }

    /** Cancellation preserves the Live Class row and its academic history. */
    public function cancelOfferingMeeting(User $actor, int $liveClassId): LiveClass
    {
        $schoolId = (int) $actor->school_id;

        return DB::transaction(function () use ($actor, $schoolId, $liveClassId): LiveClass {
            $identity = LiveClass::query()->where('school_id', $schoolId)->whereKey($liveClassId)->first();
            if (! $identity || ! $identity->course_offering_id) {
                throw new DomainException('The Offering-backed Live Class was not found for this tenant.');
            }

            $this->lockOffering($schoolId, (int) $identity->course_offering_id);
            $liveClass = LiveClass::query()->where('school_id', $schoolId)->whereKey($liveClassId)->lockForUpdate()->firstOrFail();
            $before = ['status' => $liveClass->status];
            $liveClass->status = LiveClass::STATUS_CANCELLED;
            $liveClass->updated_by = $actor->id;
            $liveClass->save();
            $this->recordAudit('update', $liveClass, $before, ['status' => $liveClass->status]);

            return $liveClass;
        });
    }

    private function lockOffering(int $schoolId, int $offeringId): CourseOffering
    {
        $offering = CourseOffering::query()
            ->where('school_id', $schoolId)
            ->whereKey($offeringId)
            ->lockForUpdate()
            ->first();

        if (! $offering) {
            throw new DomainException('The selected Course Offering does not belong to the authenticated tenant.');
        }

        if (! DB::table('subjects')->where('school_id', $schoolId)->where('id', $offering->subject_id)->exists()) {
            throw new DomainException('The Course Offering subject does not belong to the authenticated tenant.');
        }

        return $offering;
    }

    private function assertOperationalOffering(CourseOffering $offering): void
    {
        if (! in_array($offering->status, [CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true)) {
            throw new DomainException('Live Classes may only be scheduled for open or in-progress Course Offerings.');
        }
    }

    private function assertFacilitatorAllocation(CourseOffering $offering, int $teacherId, array $attributes): void
    {
        $today = now()->toDateString();
        $meetingDate = isset($attributes['scheduled_at'])
            ? Carbon::parse($attributes['scheduled_at'])->toDateString()
            : (isset($attributes['start_date']) ? Carbon::parse($attributes['start_date'])->toDateString() : $today);

        $validForDate = fn (string $date) => DB::table('course_offering_lecturer_allocations')
            ->where('school_id', $offering->school_id)
            ->where('course_offering_id', $offering->id)
            ->where('user_id', $teacherId)
            ->whereIn('role', [
                \App\Models\CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER,
                \App\Models\CourseOfferingLecturerAllocation::ROLE_CO_LECTURER,
            ])
            ->where('status', \App\Models\CourseOfferingLecturerAllocation::STATUS_ACTIVE)
            ->whereDate('starts_on', '<=', $date)
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))
            ->exists();

        $eligibleUser = DB::table('users')->where('id', $teacherId)
            ->where('school_id', $offering->school_id)
            ->where(function ($query): void {
                $query->whereNull('account_status')->orWhere('account_status', '!=', 'disable');
            })->exists();

        if (! $eligibleUser || ! $validForDate($today) || ! $validForDate($meetingDate)) {
            throw new DomainException('The facilitator must have a current Primary or Co Lecturer allocation for this Offering and meeting date.');
        }
    }

    private function assertContextInput(array $attributes): void
    {
        foreach (self::RESERVED_CONTEXT_FIELDS as $field) {
            if (array_key_exists($field, $attributes) && $attributes[$field] !== null && $attributes[$field] !== '') {
                throw new DomainException("{$field} is derived or reserved for Offering-backed Live Classes.");
            }
        }
    }

    private function meetingAttributes(array $attributes): array
    {
        return array_intersect_key($attributes, array_flip(self::MEETING_FIELDS));
    }

    private function recordAudit(string $action, LiveClass $liveClass, ?array $oldValues, array $newValues): void
    {
        AuditLog::record($action, 'Live Classes', ucfirst($action) . " Offering-backed Live Class: {$liveClass->title}", [
            'school_id' => $liveClass->school_id,
            'record_type' => LiveClass::class,
            'record_id' => $liveClass->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
