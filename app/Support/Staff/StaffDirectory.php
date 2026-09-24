<?php

namespace App\Support\Staff;

use App\Models\User;
use App\Support\Permissions\PermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Backend for the (future) Staff Directory: safe list data only.
 *
 * Governed by staff.view (not "is School Admin"). It selects an explicit
 * allow-list of columns — never the NIN (plain, encrypted or hashed), emergency
 * contacts, certificate numbers, document data, passwords or tokens — and only
 * staff of the viewer's own school.
 */
final class StaffDirectory
{
    /** users columns a directory row may carry. */
    public const COLUMNS = [
        'users.id', 'users.name', 'users.email', 'users.code', 'users.role_id', 'users.department_id', 'users.designation_id',
        'users.employment_type', 'users.staff_status', 'users.account_status',
    ];

    /** Not staff: Super Admin, Parent, Student, reserved role 8. */
    private const NOT_STAFF = [1, 6, 7, 8];

    /**
     * @param  array{q?: string, base_role?: int|string, department_id?: int|string, designation_id?: int|string, staff_status?: string}  $filters
     */
    public static function query(User $viewer, array $filters = []): Builder
    {
        if (!app(PermissionService::class)->allows($viewer, 'staff.view')) {
            throw new AuthorizationException('You do not have permission to view the staff directory.');
        }

        $query = User::query()
            ->select(self::COLUMNS)
            ->addSelect(['department_name' => \App\Models\Department::select('name')->whereColumn('departments.id', 'users.department_id')->limit(1)])
            ->addSelect(['designation_name' => \App\Models\Designation::select('name')->whereColumn('designations.id', 'users.designation_id')->limit(1)])
            ->where('users.school_id', (int) $viewer->school_id)
            ->whereNotIn('users.role_id', self::NOT_STAFF)
            ->orderBy('users.name');

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $query->where(fn ($q) => $q->where('users.name', 'like', "%{$search}%")->orWhere('users.email', 'like', "%{$search}%")->orWhere('users.code', 'like', "%{$search}%"));
        }
        foreach (['base_role' => 'users.role_id', 'department_id' => 'users.department_id', 'designation_id' => 'users.designation_id'] as $key => $column) {
            if (isset($filters[$key]) && $filters[$key] !== '') {
                $query->where($column, (int) $filters[$key]);
            }
        }
        if (in_array($filters['staff_status'] ?? null, StaffStatus::ALL, true)) {
            $query->where('users.staff_status', $filters['staff_status']);
        }

        return $query;
    }
}
