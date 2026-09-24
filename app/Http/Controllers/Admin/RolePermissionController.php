<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Permissions\PermissionAssignmentService;
use App\Support\Permissions\PermissionRegistry;
use App\Support\Permissions\PermissionService;
use App\Support\Permissions\RoleManagementException;
use App\Support\Roles\SystemRole;
use App\Support\Staff\StaffStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC Phase 3B — Administration → Roles & Permissions (School Admin only).
 *
 * Every route is mapped in the permission registry to a non-delegable RBAC
 * permission (roles.view / roles.manage / users.assign_roles /
 * permissions.assign), so only School Administrators reach it; each action
 * also re-checks through PermissionAssignmentService, which enforces the
 * delegation invariants and writes the audit log.
 *
 * Tenant scope comes only from the signed-in administrator: custom roles and
 * staff are always resolved within their school (404 otherwise). Students,
 * parents and administrators are never manageable targets. Base roles
 * (users.role_id) are displayed, never changed.
 */
class RolePermissionController extends Controller
{
    /** Base roles shown as legacy/frozen (Phase 0/3A: conflicting historical meanings). */
    private const LEGACY_ROLES = [10, 11, 12];

    /** Never staff: Super Admin (platform), Parent, Student, reserved role 8. */
    private const NOT_STAFF = [1, 6, 7, 8];

    public function __construct(private PermissionService $permissions, private PermissionAssignmentService $assignments)
    {
    }

    // ── Custom roles ────────────────────────────────────────────────────────

    public function rolesIndex()
    {
        $this->authorizeAdministration();

        $roles = $this->schoolRoles()
            ->select('staff_roles.*')
            ->selectSub(DB::table('user_staff_roles')->selectRaw('count(*)')->whereColumn('user_staff_roles.staff_role_id', 'staff_roles.id'), 'users_count')
            ->selectSub(DB::table('staff_role_permissions')->selectRaw('count(*)')->whereColumn('staff_role_permissions.staff_role_id', 'staff_roles.id'), 'permissions_count')
            ->orderBy('staff_roles.name')
            ->get();

        return view('admin.rbac.roles.index', [
            'roles' => $roles,
            'templates' => PermissionRegistry::templates(),
            'hasStatus' => $this->hasStatus(),
        ]);
    }

    public function roleCreate(Request $request)
    {
        $this->authorizeAdministration();

        $template = PermissionRegistry::templates()[$request->query('template')] ?? null;
        // Templates are starting points only, and never pre-select a sensitive permission.
        $preselected = array_values(array_filter($template['permissions'] ?? [], fn ($key) => !$this->permissions->isSensitive($key)));

        return view('admin.rbac.roles.form', [
            'role' => null,
            'name' => old('name', $template['name'] ?? ''),
            'description' => old('description', $template['description'] ?? ''),
            'selected' => old('permissions', $preselected),
            'existing' => [],
            'groups' => $this->permissionGroups(),
            'requires' => PermissionRegistry::requires(),
        ]);
    }

    public function roleStore(Request $request)
    {
        $this->authorizeAdministration();
        $data = $this->validateRole($request);
        if ($response = $this->requireSensitiveAcknowledgement($request, $data['permissions'], [])) {
            return $response;
        }

        return $this->attempt($request, function () use ($data) {
            $id = $this->assignments->createStaffRole(auth()->user(), $data['name'], $data['permissions'], $data['description']);

            return redirect()->route('admin.rbac.roles.show', $id)->with('message', get_phrase('Role created.'));
        });
    }

    public function roleShow($id)
    {
        $this->authorizeAdministration();
        $role = $this->roleOrFail($id);
        $bundle = $this->assignments->bundle((int) $role->id);

        $staff = User::whereIn('id', DB::table('user_staff_roles')->where('staff_role_id', $role->id)->pluck('user_id'))
            ->where('school_id', auth()->user()->school_id)->orderBy('name')->get();

        return view('admin.rbac.roles.show', [
            'role' => $role,
            'groups' => $this->permissionGroups($bundle),
            'staff' => $staff,
            'hasStatus' => $this->hasStatus(),
        ]);
    }

    public function roleEdit($id)
    {
        $this->authorizeAdministration();
        $role = $this->roleOrFail($id);
        $bundle = $this->assignments->bundle((int) $role->id);

        return view('admin.rbac.roles.form', [
            'role' => $role,
            'name' => old('name', $role->name),
            'description' => old('description', $role->description),
            'selected' => old('permissions', $bundle),
            'existing' => $bundle,
            'groups' => $this->permissionGroups(),
            'requires' => PermissionRegistry::requires(),
        ]);
    }

    public function roleUpdate(Request $request, $id)
    {
        $this->authorizeAdministration();
        $role = $this->roleOrFail($id);
        $data = $this->validateRole($request);
        if ($response = $this->requireSensitiveAcknowledgement($request, $data['permissions'], $this->assignments->bundle((int) $role->id))) {
            return $response;
        }

        return $this->attempt($request, function () use ($role, $data) {
            $this->assignments->updateStaffRole(auth()->user(), (int) $role->id, $data['name'], $data['description'], $data['permissions']);

            return redirect()->route('admin.rbac.roles.show', $role->id)->with('message', get_phrase('Role updated.'));
        });
    }

    public function roleDuplicate(Request $request, $id)
    {
        $this->authorizeAdministration();
        $role = $this->roleOrFail($id);
        $name = trim((string) $request->input('name')) ?: $role->name . ' (copy)';

        return $this->attempt($request, function () use ($role, $name) {
            $copy = $this->assignments->duplicateStaffRole(auth()->user(), (int) $role->id, $name);

            return redirect()->route('admin.rbac.roles.edit', $copy)->with('message', get_phrase('Role duplicated. No staff were assigned to the copy.'));
        });
    }

    public function roleStatus(Request $request, $id)
    {
        $this->authorizeAdministration();
        $role = $this->roleOrFail($id);
        $active = $request->boolean('active');

        return $this->attempt($request, function () use ($role, $active) {
            $this->assignments->setStaffRoleActive(auth()->user(), (int) $role->id, $active);

            return redirect()->back()->with('message', $active ? get_phrase('Role activated.') : get_phrase('Role deactivated. It no longer grants any permission.'));
        });
    }

    public function roleDestroy(Request $request, $id)
    {
        $this->authorizeAdministration();
        $role = $this->roleOrFail($id);

        return $this->attempt($request, function () use ($role) {
            $this->assignments->deleteStaffRole(auth()->user(), (int) $role->id);

            return redirect()->route('admin.rbac.roles.index')->with('message', get_phrase('Role deleted.'));
        });
    }

    // ── Staff access ────────────────────────────────────────────────────────

    public function staffIndex(Request $request)
    {
        $this->authorizeAdministration();
        $school = (int) auth()->user()->school_id;

        $query = User::where('school_id', $school)->whereNotIn('role_id', self::NOT_STAFF);

        if ($search = trim((string) $request->query('q'))) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
        }
        if ($request->filled('base_role')) {
            $query->where('role_id', (int) $request->query('base_role'));
        }
        if ($request->filled('custom_role')) {
            $query->whereIn('id', DB::table('user_staff_roles')->where('school_id', $school)->where('staff_role_id', (int) $request->query('custom_role'))->select('user_id'));
        }
        if ($request->query('status') === 'active') {
            $query->where(fn ($q) => $q->whereNull('account_status')->orWhere('account_status', '!=', 'disable'))
                ->where(fn ($q) => $q->whereNull('staff_status')->orWhereNotIn('staff_status', StaffStatus::BLOCKED));
        } elseif ($request->query('status') === 'inactive') {
            $query->where(fn ($q) => $q->where('account_status', 'disable')->orWhereIn('staff_status', StaffStatus::BLOCKED));
        }

        $staff = $query->orderBy('name')->paginate(25)->withQueryString();
        $ids = $staff->pluck('id');

        $rolesByUser = $this->grantTablesExist()
            ? DB::table('user_staff_roles')->join('staff_roles', 'staff_roles.id', '=', 'user_staff_roles.staff_role_id')
                ->whereIn('user_staff_roles.user_id', $ids)->where('staff_roles.school_id', $school)
                ->get(['user_staff_roles.user_id', 'staff_roles.name', $this->hasStatus() ? 'staff_roles.is_active' : DB::raw('1 as is_active')])->groupBy('user_id')
            : collect();
        $directCounts = $this->grantTablesExist()
            ? DB::table('user_permissions')->whereIn('user_id', $ids)->where('school_id', $school)->selectRaw('user_id, count(*) as n')->groupBy('user_id')->pluck('n', 'user_id')
            : collect();

        return view('admin.rbac.staff.index', [
            'staff' => $staff,
            'rolesByUser' => $rolesByUser,
            'directCounts' => $directCounts,
            'baseRoles' => $this->baseRoleOptions($school),
            'customRoles' => $this->schoolRoles()->orderBy('name')->get(['id', 'name']),
            'legacyRoles' => self::LEGACY_ROLES,
            'filters' => $request->only(['q', 'base_role', 'custom_role', 'status']),
        ]);
    }

    public function staffShow($id)
    {
        $this->authorizeAdministration();
        $member = $this->staffOrFail($id);
        $school = (int) auth()->user()->school_id;

        $assigned = DB::table('user_staff_roles')->join('staff_roles', 'staff_roles.id', '=', 'user_staff_roles.staff_role_id')
            ->where('user_staff_roles.user_id', $member->id)->where('staff_roles.school_id', $school)
            ->orderBy('staff_roles.name')->get(['staff_roles.*']);
        $available = $this->schoolRoles()->whereNotIn('id', $assigned->pluck('id'))
            ->when($this->hasStatus(), fn ($q) => $q->where('is_active', true))->orderBy('name')->get();
        $direct = DB::table('user_permissions')->where('user_id', $member->id)->where('school_id', $school)->orderBy('permission')->pluck('permission')->all();

        $manageable = !in_array((int) $member->role_id, [PermissionService::SCHOOL_ADMIN], true) && (int) $member->id !== (int) auth()->id();

        return view('admin.rbac.staff.show', [
            'member' => $member,
            'baseRole' => SystemRole::name((int) $member->role_id) ?? ('Role ' . $member->role_id),
            'isLegacyRole' => in_array((int) $member->role_id, self::LEGACY_ROLES, true),
            'schoolName' => DB::table('schools')->where('id', $school)->value('title'),
            'assigned' => $assigned,
            'available' => $available,
            // Roles whose bundle holds a sensitive permission: assigning one asks for confirmation.
            'sensitiveRoleIds' => $available->filter(fn ($role) => array_filter($this->assignments->bundle((int) $role->id), fn ($key) => $this->permissions->isSensitive($key)))
                ->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'direct' => $direct,
            'effective' => $this->permissions->explain($member),
            'registry' => PermissionRegistry::permissions(),
            'modules' => PermissionRegistry::modules(),
            'groups' => $this->permissionGroups(),
            'requires' => PermissionRegistry::requires(),
            'manageable' => $manageable,
            'hasStatus' => $this->hasStatus(),
        ]);
    }

    public function staffAssignRole(Request $request, $id)
    {
        $this->authorizeAdministration();
        $member = $this->staffOrFail($id);
        $role = $this->roleOrFail($request->input('staff_role_id'));

        return $this->attempt($request, function () use ($member, $role) {
            $this->assignments->assignStaffRole(auth()->user(), $member, (int) $role->id);

            return redirect()->route('admin.rbac.staff.show', $member->id)->with('message', get_phrase('Role assigned.'));
        });
    }

    public function staffRemoveRole(Request $request, $id, $roleId)
    {
        $this->authorizeAdministration();
        $member = $this->staffOrFail($id);
        $role = $this->roleOrFail($roleId);

        return $this->attempt($request, function () use ($member, $role) {
            $this->assignments->removeStaffRole(auth()->user(), $member, (int) $role->id);

            return redirect()->route('admin.rbac.staff.show', $member->id)->with('message', get_phrase('Role removed.'));
        });
    }

    public function staffGrant(Request $request, $id)
    {
        $this->authorizeAdministration();
        $member = $this->staffOrFail($id);
        $keys = array_values(array_filter((array) $request->input('permissions', []), 'is_string'));
        if (!$keys) {
            return $this->fail($request, get_phrase('Select at least one permission.'));
        }
        $existing = DB::table('user_permissions')->where('user_id', $member->id)->pluck('permission')->all();
        if ($response = $this->requireSensitiveAcknowledgement($request, $keys, $existing)) {
            return $response;
        }

        return $this->attempt($request, function () use ($member, $keys) {
            $this->assignments->grantMany(auth()->user(), $member, $keys);

            return redirect()->route('admin.rbac.staff.show', $member->id)->with('message', get_phrase('Permissions granted.'));
        });
    }

    public function staffRevoke(Request $request, $id, $permission)
    {
        $this->authorizeAdministration();
        $member = $this->staffOrFail($id);

        return $this->attempt($request, function () use ($member, $permission) {
            $this->assignments->revoke(auth()->user(), $member, (string) $permission);

            return redirect()->route('admin.rbac.staff.show', $member->id)->with('message', get_phrase('Permission removed.'));
        });
    }

    public function staffRevokeAll(Request $request, $id)
    {
        $this->authorizeAdministration();
        $member = $this->staffOrFail($id);

        return $this->attempt($request, function () use ($member) {
            $this->assignments->revokeAll(auth()->user(), $member);

            return redirect()->route('admin.rbac.staff.show', $member->id)->with('message', get_phrase('All direct permissions removed.'));
        });
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /** Defence in depth behind the 'rbac' route map: only administrators manage access. */
    private function authorizeAdministration(): void
    {
        abort_unless($this->assignments->canAdminister(auth()->user()), 403);
        abort_unless($this->grantTablesExist(), 503, 'Roles & Permissions needs the RBAC database migration.');
    }

    private function schoolRoles()
    {
        return DB::table('staff_roles')->where('school_id', (int) auth()->user()->school_id);
    }

    private function roleOrFail($id): object
    {
        $role = is_numeric($id) ? $this->schoolRoles()->where('id', (int) $id)->first() : null;
        abort_unless($role, 404);

        return $role;
    }

    /** A staff member of the administrator's own school (never a student, parent or Super Admin). */
    private function staffOrFail($id): User
    {
        $member = User::where('school_id', (int) auth()->user()->school_id)->whereNotIn('role_id', self::NOT_STAFF)->find($id);
        abort_unless($member, 404);

        return $member;
    }

    private function validateRole(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);
        $data['permissions'] = array_values(array_unique($data['permissions'] ?? []));
        $data['description'] = $data['description'] ?? null;

        return $data;
    }

    /**
     * Sensitive permissions must be chosen deliberately: adding one that the
     * role/user did not already have needs the explicit acknowledgement box.
     * Non-delegable keys are never offered for acknowledgement: they fall
     * through to PermissionAssignmentService, which refuses them outright (403).
     */
    private function requireSensitiveAcknowledgement(Request $request, array $submitted, array $existing)
    {
        $added = array_diff($this->permissions->withDependencies($submitted), $existing);
        $sensitive = array_values(array_filter($added, fn ($key) => $this->permissions->exists($key)
            && $this->permissions->isDelegable($key) && $this->permissions->isSensitive($key)));

        if ($sensitive && !$request->boolean('acknowledge_sensitive')) {
            return $this->fail($request, get_phrase('Confirm that you intend to grant these sensitive permissions') . ': ' . implode(', ', $sensitive) . '.');
        }

        return null;
    }

    private function attempt(Request $request, callable $action)
    {
        try {
            return $action();
        } catch (RoleManagementException $e) {
            return $this->fail($request, $e->getMessage());
        }
    }

    private function fail(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()->back()->withInput()->with('error', $message);
    }

    /** Registry permissions grouped by module, flagged sensitive; optionally only $only. */
    private function permissionGroups(?array $only = null): array
    {
        $groups = [];
        foreach (PermissionRegistry::permissions() as $key => $definition) {
            if ($only !== null && !in_array($key, $only, true)) {
                continue;
            }
            $groups[$definition['module']]['label'] = PermissionRegistry::modules()[$definition['module']] ?? $definition['module'];
            $groups[$definition['module']]['permissions'][$key] = $definition;
        }

        return $groups;
    }

    private function baseRoleOptions(int $school): array
    {
        $options = [];
        foreach (User::where('school_id', $school)->whereNotIn('role_id', self::NOT_STAFF)->distinct()->orderBy('role_id')->pluck('role_id') as $roleId) {
            $options[(int) $roleId] = SystemRole::name((int) $roleId) ?? ('Role ' . $roleId);
        }

        return $options;
    }

    private function hasStatus(): bool
    {
        return Schema::hasColumn('staff_roles', 'is_active');
    }

    private function grantTablesExist(): bool
    {
        return Schema::hasTable('staff_roles') && Schema::hasTable('user_permissions') && Schema::hasTable('user_staff_roles') && Schema::hasTable('staff_role_permissions');
    }
}
