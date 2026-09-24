<?php

namespace App\Support\Permissions;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Roles\SystemRole;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC Phase 3A/3B — the only way permissions and custom staff roles are
 * changed. The Phase 3B Roles & Permissions screens call nothing else.
 *
 * Invariants (enforced before anything is written):
 *  - only a School Administrator (for their own school) or the Super
 *    Administrator (naming the school explicitly) may manage roles and
 *    permissions — "permissions.assign" and friends are not delegable, so no
 *    staff member can ever hand out access;
 *  - nobody changes their own permissions (no self-elevation);
 *  - the actor must hold every permission they grant;
 *  - only registered, delegable permissions can be granted or bundled;
 *    sensitive ones only by an administrator;
 *  - targets are staff of the same school: never Super/School Admins,
 *    Parents, Students or the reserved role 8;
 *  - custom roles never touch users.role_id — they are permission bundles;
 *  - granting an action also grants the view it requires; revoking a view
 *    also revokes the actions that require it (no dangling access);
 *  - deactivated roles grant nothing and cannot be newly assigned.
 * Every change is audited (actor, school, target, before, after).
 */
class PermissionAssignmentService
{
    public const PERMISSION_GRANTED = 'PERMISSION_GRANTED';
    public const PERMISSION_REVOKED = 'PERMISSION_REVOKED';
    public const ROLE_CREATED = 'ROLE_CREATED';
    public const ROLE_UPDATED = 'ROLE_UPDATED';
    public const ROLE_DEACTIVATED = 'ROLE_DEACTIVATED';
    public const ROLE_ACTIVATED = 'ROLE_ACTIVATED';
    public const ROLE_DELETED = 'ROLE_DELETED';
    public const ROLE_ASSIGNED = 'ROLE_ASSIGNED';
    public const ROLE_REMOVED = 'ROLE_REMOVED';

    public function __construct(private PermissionService $permissions)
    {
    }

    // ── Direct permissions ──────────────────────────────────────────────────

    /** Grants $permission (plus the view it requires) directly to $target. */
    public function grant(User $actor, User $target, string $permission): void
    {
        $this->grantMany($actor, $target, [$permission]);
    }

    public function grantMany(User $actor, User $target, array $permissions): void
    {
        $this->assertCanManage($actor, $target);
        $keys = $this->permissions->withDependencies($permissions);
        foreach ($keys as $key) {
            $this->assertCanDelegate($actor, $key);
        }

        $before = $this->directPermissions($target);
        foreach (array_diff($keys, $before) as $key) {
            DB::table('user_permissions')->insert([
                'user_id' => $target->id, 'permission' => $key, 'school_id' => (int) $target->school_id,
                'granted_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $after = $this->directPermissions($target);
        if ($after !== $before) {
            $this->auditUser($actor, $target, self::PERMISSION_GRANTED, 'Granted ' . implode(', ', array_diff($after, $before)), $before, $after);
        }
    }

    /** Revokes a direct permission (and any direct action that requires it). */
    public function revoke(User $actor, User $target, string $permission): void
    {
        $this->assertCanManage($actor, $target);

        $before = $this->directPermissions($target);
        $remove = array_unique(array_merge([$permission], $this->permissions->dependentsOf($permission)));
        DB::table('user_permissions')->where('user_id', $target->id)->whereIn('permission', $remove)->delete();

        $after = $this->directPermissions($target);
        if ($after !== $before) {
            $this->auditUser($actor, $target, self::PERMISSION_REVOKED, 'Revoked ' . implode(', ', array_diff($before, $after)), $before, $after);
        }
    }

    /** Removes every direct permission of $target (custom roles and base role untouched). */
    public function revokeAll(User $actor, User $target): void
    {
        $this->assertCanManage($actor, $target);

        $before = $this->directPermissions($target);
        DB::table('user_permissions')->where('user_id', $target->id)->delete();

        if ($before) {
            $this->auditUser($actor, $target, self::PERMISSION_REVOKED, 'Revoked all direct permissions', $before, []);
        }
    }

    // ── Custom staff roles (permission bundles) ─────────────────────────────

    /** Creates a custom staff role in the actor's school (or the named school for the Super Admin). */
    public function createStaffRole(User $actor, string $name, array $permissions, ?string $description = null, ?int $schoolId = null): int
    {
        $school = $this->actingSchool($actor, $schoolId);
        $name = $this->validName($name, $school);
        $keys = $this->validBundle($actor, $permissions);

        return DB::transaction(function () use ($actor, $school, $name, $description, $keys) {
            $row = ['school_id' => $school, 'name' => $name, 'description' => $description,
                'created_by' => $actor->id, 'updated_by' => $actor->id, 'created_at' => now(), 'updated_at' => now()];
            if ($this->hasStatusColumn()) {
                $row['is_active'] = true;
            }
            $roleId = (int) DB::table('staff_roles')->insertGetId($row);
            $this->writeBundle($roleId, $keys);

            $this->auditRole($actor, $school, $roleId, self::ROLE_CREATED, "Created staff role \"{$name}\"", [], $keys);

            return $roleId;
        });
    }

    /** Renames/re-describes a role and replaces its permission bundle. */
    public function updateStaffRole(User $actor, int $staffRoleId, string $name, ?string $description, array $permissions): void
    {
        $role = $this->roleForActorOrFail($actor, $staffRoleId);
        $name = $this->validName($name, (int) $role->school_id, (int) $role->id);
        $keys = $this->validBundle($actor, $permissions);
        $before = $this->bundle((int) $role->id);

        DB::transaction(function () use ($actor, $role, $name, $description, $keys) {
            DB::table('staff_roles')->where('id', $role->id)->update(['name' => $name, 'description' => $description, 'updated_by' => $actor->id, 'updated_at' => now()]);
            DB::table('staff_role_permissions')->where('staff_role_id', $role->id)->delete();
            $this->writeBundle((int) $role->id, $keys);
        });

        $this->auditRole($actor, (int) $role->school_id, (int) $role->id, self::ROLE_UPDATED, "Updated staff role \"{$name}\"",
            ['name' => $role->name, 'description' => $role->description, 'permissions' => $before],
            ['name' => $name, 'description' => $description, 'permissions' => $keys]);
    }

    /** Copies a role's description and permissions under a new name — never its assignments. */
    public function duplicateStaffRole(User $actor, int $staffRoleId, string $newName): int
    {
        $role = $this->roleForActorOrFail($actor, $staffRoleId);

        return $this->createStaffRole($actor, $newName, $this->bundle((int) $role->id), $role->description, (int) $role->school_id);
    }

    /** Deactivated roles stay visible and keep their assignments, but grant nothing and cannot be newly assigned. */
    public function setStaffRoleActive(User $actor, int $staffRoleId, bool $active): void
    {
        if (!$this->hasStatusColumn()) {
            throw new RoleManagementException('Role status is not available until the latest RBAC migration has run.');
        }

        $role = $this->roleForActorOrFail($actor, $staffRoleId);
        if ((bool) $role->is_active === $active) {
            return;
        }

        DB::table('staff_roles')->where('id', $role->id)->update(['is_active' => $active, 'updated_by' => $actor->id, 'updated_at' => now()]);

        $this->auditRole($actor, (int) $role->school_id, (int) $role->id, $active ? self::ROLE_ACTIVATED : self::ROLE_DEACTIVATED,
            ($active ? 'Activated' : 'Deactivated') . " staff role \"{$role->name}\"", ['is_active' => !$active], ['is_active' => $active]);
    }

    /** Deletes an unassigned role. Assigned roles must be unassigned (or deactivated) first. */
    public function deleteStaffRole(User $actor, int $staffRoleId): void
    {
        $role = $this->roleForActorOrFail($actor, $staffRoleId);
        $assigned = $this->assignmentCount((int) $role->id);

        if ($assigned > 0) {
            throw new RoleManagementException("This role is currently assigned to {$assigned} staff member" . ($assigned === 1 ? '' : 's')
                . '. Remove it from them first, or deactivate the role instead.');
        }

        $before = $this->bundle((int) $role->id);
        DB::transaction(function () use ($role) {
            DB::table('staff_role_permissions')->where('staff_role_id', $role->id)->delete();
            DB::table('staff_roles')->where('id', $role->id)->delete();
        });

        $this->auditRole($actor, (int) $role->school_id, (int) $role->id, self::ROLE_DELETED, "Deleted staff role \"{$role->name}\"",
            ['name' => $role->name, 'permissions' => $before], []);
    }

    public function assignStaffRole(User $actor, User $target, int $staffRoleId): void
    {
        $this->assertCanManage($actor, $target);
        $role = $this->staffRoleInSchoolOrFail($staffRoleId, (int) $target->school_id);
        if ($this->hasStatusColumn() && !$role->is_active) {
            throw new RoleManagementException("\"{$role->name}\" is deactivated and cannot be assigned.");
        }
        foreach ($this->bundle((int) $role->id) as $permission) {
            $this->assertCanDelegate($actor, $permission);
        }

        $before = $this->assignedRoleNames($target);
        if (DB::table('user_staff_roles')->where('user_id', $target->id)->where('staff_role_id', $role->id)->exists()) {
            return;
        }
        DB::table('user_staff_roles')->insert([
            'user_id' => $target->id, 'staff_role_id' => $role->id, 'school_id' => (int) $target->school_id,
            'assigned_by' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->auditUser($actor, $target, self::ROLE_ASSIGNED, "Assigned staff role \"{$role->name}\"", $before, $this->assignedRoleNames($target), 'staff_roles');
    }

    public function removeStaffRole(User $actor, User $target, int $staffRoleId): void
    {
        $this->assertCanManage($actor, $target);
        $role = $this->staffRoleInSchoolOrFail($staffRoleId, (int) $target->school_id);

        $before = $this->assignedRoleNames($target);
        DB::table('user_staff_roles')->where('user_id', $target->id)->where('staff_role_id', $role->id)->delete();

        $this->auditUser($actor, $target, self::ROLE_REMOVED, "Removed staff role \"{$role->name}\"", $before, $this->assignedRoleNames($target), 'staff_roles');
    }

    // ── Invariants ──────────────────────────────────────────────────────────

    public function assertCanManage(User $actor, User $target): void
    {
        $this->assertIsDelegator($actor);

        if ((int) $actor->id === (int) $target->id) {
            $this->deny('You cannot change your own permissions.');
        }

        $role = (int) $target->role_id;
        if (in_array($role, [PermissionService::SUPER_ADMIN, PermissionService::SCHOOL_ADMIN], true) || !$this->permissions->isStaffRole($role)) {
            $this->deny('Permissions can only be delegated to staff members.');
        }

        if (empty($target->school_id)) {
            $this->deny('The user does not belong to a school.');
        }

        if ((int) $actor->role_id === PermissionService::SCHOOL_ADMIN && (int) $actor->school_id !== (int) $target->school_id) {
            $this->deny('You can only manage staff of your own school.');
        }
    }

    public function assertCanDelegate(User $actor, string $permission): void
    {
        $this->assertIsDelegator($actor);

        if (!$this->permissions->exists($permission)) {
            $this->deny("Unknown permission: {$permission}.");
        }

        if (!$this->permissions->isDelegable($permission)) {
            $this->deny("{$permission} cannot be delegated.");
        }

        if (!$this->permissions->allows($actor, $permission)) {
            $this->deny("You do not hold {$permission}, so you cannot grant it.");
        }

        if ($this->permissions->isSensitive($permission) && !$this->isAdministrator($actor)) {
            $this->deny("{$permission} is sensitive and can only be granted by an administrator.");
        }
    }

    /** True when $actor may use the Roles & Permissions administration at all. */
    public function canAdminister(?User $actor): bool
    {
        return $actor !== null && $this->isAdministrator($actor) && $this->permissions->allows($actor, 'permissions.assign');
    }

    private function assertIsDelegator(User $actor): void
    {
        if (!$this->canAdminister($actor)) {
            $this->deny('You are not allowed to manage permissions.');
        }
    }

    private function isAdministrator(User $actor): bool
    {
        return in_array((int) $actor->role_id, [PermissionService::SUPER_ADMIN, PermissionService::SCHOOL_ADMIN], true)
            && $this->permissions->isActive($actor);
    }

    private function actingSchool(User $actor, ?int $schoolId): int
    {
        $this->assertIsDelegator($actor);

        if ((int) $actor->role_id === PermissionService::SCHOOL_ADMIN) {
            if ($schoolId !== null && $schoolId !== (int) $actor->school_id) {
                $this->deny('You can only manage staff roles of your own school.');
            }

            return (int) $actor->school_id;
        }

        if (empty($schoolId)) {
            $this->deny('The Super Administrator must name the school explicitly.');
        }

        return $schoolId;
    }

    /** A role the actor may manage: a School Admin only ever reaches roles of their own school. */
    private function roleForActorOrFail(User $actor, int $staffRoleId): object
    {
        $this->assertIsDelegator($actor);
        $query = DB::table('staff_roles')->where('id', $staffRoleId);
        if ((int) $actor->role_id === PermissionService::SCHOOL_ADMIN) {
            $query->where('school_id', (int) $actor->school_id);
        }
        $role = $query->first();
        if (!$role) {
            $this->deny('That staff role does not belong to your school.');
        }

        return $role;
    }

    private function staffRoleInSchoolOrFail(int $staffRoleId, int $schoolId): object
    {
        $role = DB::table('staff_roles')->where('id', $staffRoleId)->where('school_id', $schoolId)->first();
        if (!$role) {
            $this->deny('That staff role does not belong to the user\'s school.');
        }

        return $role;
    }

    /** Role names are unique per school and may not impersonate a system base role. */
    private function validName(string $name, int $schoolId, ?int $ignoreId = null): string
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '' || mb_strlen($name) > 100) {
            throw new RoleManagementException('Enter a role name of up to 100 characters.');
        }

        // A custom role must not look like a base identity people already hold. Only the protected
        // base roles are reserved: planned role names ("Examinations Officer", "Admissions Officer")
        // are exactly the responsibilities schools should model as custom roles.
        $reserved = ['admin', 'administrator'];
        foreach (SystemRole::idsWithStatus(SystemRole::STATUS_PROTECTED) as $id) {
            $system = SystemRole::find($id);
            $reserved[] = str_replace('_', ' ', $system['key']);
            foreach (explode('/', $system['name']) as $part) {
                $reserved[] = trim($part);
            }
        }
        if (in_array(mb_strtolower($name), array_map('mb_strtolower', $reserved), true)) {
            throw new RoleManagementException("\"{$name}\" is a system role name. Choose a name for the responsibility instead, e.g. \"Examinations Officer\".");
        }

        $clash = DB::table('staff_roles')->where('school_id', $schoolId)->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists();
        if ($clash) {
            throw new RoleManagementException("A role named \"{$name}\" already exists in this school.");
        }

        return $name;
    }

    /** The bundle a role may hold: registered, delegable, actor-held permissions plus their dependencies. */
    private function validBundle(User $actor, array $permissions): array
    {
        $keys = $this->permissions->withDependencies(array_values(array_unique(array_map('strval', $permissions))));
        foreach ($keys as $key) {
            $this->assertCanDelegate($actor, $key);
        }
        sort($keys);

        return $keys;
    }

    private function writeBundle(int $roleId, array $keys): void
    {
        foreach ($keys as $key) {
            DB::table('staff_role_permissions')->insert(['staff_role_id' => $roleId, 'permission' => $key, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function bundle(int $staffRoleId): array
    {
        return DB::table('staff_role_permissions')->where('staff_role_id', $staffRoleId)->orderBy('permission')->pluck('permission')->all();
    }

    public function assignmentCount(int $staffRoleId): int
    {
        return DB::table('user_staff_roles')->where('staff_role_id', $staffRoleId)->count();
    }

    private function hasStatusColumn(): bool
    {
        return Schema::hasColumn('staff_roles', 'is_active');
    }

    private function directPermissions(User $target): array
    {
        return DB::table('user_permissions')->where('user_id', $target->id)->orderBy('permission')->pluck('permission')->all();
    }

    private function assignedRoleNames(User $target): array
    {
        return DB::table('user_staff_roles')->join('staff_roles', 'staff_roles.id', '=', 'user_staff_roles.staff_role_id')
            ->where('user_staff_roles.user_id', $target->id)->orderBy('staff_roles.name')->pluck('staff_roles.name')->all();
    }

    private function auditUser(User $actor, User $target, string $action, string $what, array $before, array $after, string $field = 'permissions'): void
    {
        AuditLog::record($action, 'RBAC', "{$what} for user #{$target->id} ({$target->name}) by user #{$actor->id}", [
            'event_type' => 'AUTH',
            'school_id' => (int) $target->school_id,
            'record_type' => User::class,
            'record_id' => $target->id,
            'old_values' => [$field => $before],
            'new_values' => [$field => $after],
        ]);
    }

    private function auditRole(User $actor, int $schoolId, int $roleId, string $action, string $what, array $before, array $after): void
    {
        AuditLog::record($action, 'RBAC', "{$what} by user #{$actor->id}", [
            'event_type' => 'AUTH',
            'school_id' => $schoolId,
            'record_type' => 'staff_roles',
            'record_id' => $roleId,
            'old_values' => $before === [] ? null : (array_is_list($before) ? ['permissions' => $before] : $before),
            'new_values' => $after === [] ? null : (array_is_list($after) ? ['permissions' => $after] : $after),
        ]);
    }

    private function deny(string $message): never
    {
        throw new AuthorizationException($message);
    }
}
