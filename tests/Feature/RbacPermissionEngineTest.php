<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions\OnlineExamPermissionService;
use App\Support\Permissions\PermissionAssignmentService;
use App\Support\Permissions\PermissionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * RBAC Phase 3A — the permission engine and the assignment service.
 */
class RbacPermissionEngineTest extends TestCase
{
    use StaffModuleTestHelper;

    private const MIGRATION = 'database/migrations/2026_09_23_000003_create_rbac_tables.php';

    private int $schoolA;
    private int $schoolB;
    private PermissionService $perms;
    private PermissionAssignmentService $assign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        (require base_path(self::MIGRATION))->up();
        $this->schoolA = $this->makeSchool(['title' => 'School A', 'status' => 1]);
        $this->schoolB = $this->makeSchool(['title' => 'School B', 'status' => 1]);
        $this->perms = app(PermissionService::class);
        $this->assign = app(PermissionAssignmentService::class);
    }

    private function user(int $role, ?int $school = null, array $extra = []): User
    {
        return User::factory()->create($extra + ['role_id' => $role, 'school_id' => $school ?? $this->schoolA, 'account_status' => 'active']);
    }

    private function admin(?int $school = null): User
    {
        return $this->user(2, $school);
    }

    // ── Bypass and base-role compatibility ───────────────────────────────────

    public function test_super_admin_and_school_admin_hold_every_permission_explicitly(): void
    {
        foreach ([$this->user(1, null, ['school_id' => null]), $this->admin()] as $user) {
            foreach (array_keys(\App\Support\Permissions\PermissionRegistry::permissions()) as $key) {
                $this->assertTrue($this->perms->allows($user, $key), "role {$user->role_id}: {$key}");
            }
        }
    }

    public function test_students_and_parents_hold_no_staff_permissions_even_with_a_stray_grant(): void
    {
        foreach ([7, 6] as $role) {
            $user = $this->user($role);
            DB::table('user_permissions')->insert(['school_id' => $this->schoolA, 'user_id' => $user->id, 'permission' => 'finance.view']);
            $this->assertSame([], $this->perms->effectivePermissions($user), "role {$role}");
        }
    }

    public function test_base_roles_keep_what_they_can_do_today(): void
    {
        $teacher = $this->user(3);
        foreach (['students.view', 'academic.attendance', 'academic.gradebook', 'academic.assignments', 'communications.noticeboard', 'clubs.manage', 'online_exams.view', 'online_exams.mark', 'live_classes.create'] as $key) {
            $this->assertTrue($this->perms->allows($teacher, $key), "teacher: {$key}");
        }
        foreach (['finance.view', 'finance.settings', 'admissions.view', 'hostel.view', 'library.view', 'cms.manage', 'hr.appraisal', 'online_exams.publish', 'live_classes.manage_all', 'permissions.assign'] as $key) {
            $this->assertFalse($this->perms->allows($teacher, $key), "teacher must not: {$key}");
        }

        $accountant = $this->user(4);
        $this->assertTrue($this->perms->allows($accountant, 'finance.payments'));
        $this->assertTrue($this->perms->allows($accountant, 'finance.payroll'));
        $this->assertFalse($this->perms->allows($accountant, 'finance.settings'), 'ordinary finance staff do not configure gateways');

        $this->assertTrue($this->perms->allows($this->user(5), 'library.issue'));
        $this->assertFalse($this->perms->allows($this->user(5), 'finance.view'));
        $this->assertTrue($this->perms->allows($this->user(10), 'hostel.applications'));
        $this->assertFalse($this->perms->allows($this->user(10), 'hostel.manage'));
        $this->assertTrue($this->perms->allows($this->user(15), 'hr.appraisal'));
        $this->assertFalse($this->perms->allows($this->user(15), 'permissions.assign'));
        $this->assertTrue($this->perms->allows($this->user(14), 'finance.settings'), 'Director keeps payment settings (pre-RBAC cleanup)');
    }

    public function test_disabled_or_suspended_accounts_and_unknown_keys_are_denied(): void
    {
        $this->assertFalse($this->perms->allows($this->user(2, null, ['account_status' => 'disable']), 'students.view'));
        $this->assertFalse($this->perms->allows($this->user(3, null, ['staff_status' => 'suspended']), 'students.view'));
        $this->assertFalse($this->perms->allows($this->admin(), 'finance.everything'));
        $this->assertFalse($this->perms->allows(null, 'students.view'));
    }

    // ── Grants ───────────────────────────────────────────────────────────────

    public function test_a_direct_grant_adds_one_capability_without_changing_the_base_role(): void
    {
        $teacher = $this->user(3);
        $this->actingAs($admin = $this->admin());

        $this->assign->grant($admin, $teacher, 'finance.view');

        $this->assertTrue($this->perms->allows($teacher->fresh(), 'finance.view'));
        $this->assertFalse($this->perms->allows($teacher->fresh(), 'finance.invoices'), 'only what was granted');
        $this->assertSame(3, (int) $teacher->fresh()->role_id);
    }

    public function test_a_custom_staff_role_bundles_permissions_and_combines_with_direct_grants(): void
    {
        $this->actingAs($admin = $this->admin());
        $examOfficer = $this->assign->createStaffRole($admin, 'Examinations Officer', ['online_exams.view', 'online_exams.create', 'online_exams.publish', 'online_exams.results']);
        $librarian = $this->user(5);

        $this->assign->assignStaffRole($admin, $librarian, $examOfficer);
        $this->assign->grant($admin, $librarian, 'live_classes.create');

        $l = $librarian->fresh();
        foreach (['online_exams.view', 'online_exams.create', 'online_exams.publish', 'online_exams.results', 'live_classes.create', 'library.issue'] as $key) {
            $this->assertTrue($this->perms->allows($l, $key), $key);
        }
        foreach (['online_exams.mark', 'finance.view', 'admissions.view', 'hr.appraisal', 'cms.manage'] as $key) {
            $this->assertFalse($this->perms->allows($l, $key), $key);
        }
        // The exam module's own service sees the grant too (grants-only; its rules are unchanged).
        $this->assertTrue(app(OnlineExamPermissionService::class)->has($l, 'publish_online_exams'));
        $this->assertFalse(app(OnlineExamPermissionService::class)->has($l, 'mark_exam_answers'));
    }

    public function test_revoking_removes_only_that_capability(): void
    {
        $this->actingAs($admin = $this->admin());
        $teacher = $this->user(3);
        $this->assign->grant($admin, $teacher, 'finance.view');
        $this->assign->grant($admin, $teacher, 'library.view');
        $role = $this->assign->createStaffRole($admin, 'Hostel Officer', ['hostel.view']);
        $this->assign->assignStaffRole($admin, $teacher, $role);

        $this->assign->revoke($admin, $teacher, 'finance.view');
        $this->assign->removeStaffRole($admin, $teacher, $role);

        $t = $teacher->fresh();
        $this->assertFalse($this->perms->allows($t, 'finance.view'));
        $this->assertFalse($this->perms->allows($t, 'hostel.view'));
        $this->assertTrue($this->perms->allows($t, 'library.view'), 'unrelated grant intact');
        $this->assertTrue($this->perms->allows($t, 'academic.attendance'), 'base role intact');
        $this->assertNotNull(User::find($teacher->id), 'account intact');
    }

    public function test_grants_only_count_in_the_school_they_were_made_in(): void
    {
        $teacher = $this->user(3);
        // A grant row recorded under another school, and a custom role owned by another school.
        DB::table('user_permissions')->insert(['school_id' => $this->schoolB, 'user_id' => $teacher->id, 'permission' => 'finance.view']);
        $foreignRole = DB::table('staff_roles')->insertGetId(['school_id' => $this->schoolB, 'name' => 'B bundle']);
        DB::table('staff_role_permissions')->insert(['staff_role_id' => $foreignRole, 'permission' => 'hostel.manage']);
        DB::table('user_staff_roles')->insert(['school_id' => $this->schoolA, 'user_id' => $teacher->id, 'staff_role_id' => $foreignRole]);

        $this->assertFalse($this->perms->allows($teacher, 'finance.view'));
        $this->assertFalse($this->perms->allows($teacher, 'hostel.manage'));
    }

    public function test_without_the_rbac_tables_the_engine_still_works_from_base_roles(): void
    {
        (require base_path(self::MIGRATION))->down();

        $this->assertFalse(Schema::hasTable('user_permissions'));
        $this->assertTrue($this->perms->allows($this->user(3), 'academic.attendance'));
        $this->assertFalse($this->perms->allows($this->user(3), 'finance.view'));
        $this->assertTrue($this->perms->allows($this->admin(), 'finance.settings'));
    }

    public function test_gate_blade_and_user_helper_share_the_engine(): void
    {
        $teacher = $this->user(3);
        $this->assertTrue($teacher->hasPermission('academic.attendance'));
        $this->assertFalse($teacher->hasPermission('finance.view'));
        $this->assertTrue(Gate::forUser($teacher)->allows('permission', 'academic.attendance'));
        $this->assertFalse(Gate::forUser($teacher)->allows('permission', 'finance.view'));

        $this->actingAs($teacher);
        $html = \Illuminate\Support\Facades\Blade::render("@permission('academic.attendance') YES @endpermission @permission('finance.view') NO @endpermission");
        $this->assertStringContainsString('YES', $html);
        $this->assertStringNotContainsString('NO', $html);
    }

    // ── No privilege escalation ──────────────────────────────────────────────

    public function test_staff_can_never_grant_permissions_even_to_others_or_themselves(): void
    {
        $hr = $this->user(15);
        $teacher = $this->user(3);

        foreach ([[$hr, $teacher, 'hr.appraisal'], [$hr, $hr, 'finance.view'], [$teacher, $teacher, 'finance.view']] as [$actor, $target, $key]) {
            try {
                $this->assign->grant($actor, $target, $key);
                $this->fail("role {$actor->role_id} granted {$key}");
            } catch (AuthorizationException $e) {
                $this->assertStringContainsString('not allowed to manage permissions', $e->getMessage());
            }
        }
        $this->assertSame(0, DB::table('user_permissions')->count());
    }

    public function test_administrators_cannot_self_grant_cross_tenant_or_grant_reserved_or_unknown_permissions(): void
    {
        $adminA = $this->admin();
        $this->actingAs($adminA);
        $teacherA = $this->user(3);
        $teacherB = $this->user(3, $this->schoolB);

        $cases = [
            'self' => fn () => $this->assign->grant($adminA, $adminA, 'finance.view'),
            'other school' => fn () => $this->assign->grant($adminA, $teacherB, 'finance.view'),
            'to an admin' => fn () => $this->assign->grant($adminA, $this->admin(), 'finance.view'),
            'to a student' => fn () => $this->assign->grant($adminA, $this->user(7), 'finance.view'),
            'reserved key' => fn () => $this->assign->grant($adminA, $teacherA, 'permissions.assign'),
            'reserved in role' => fn () => $this->assign->createStaffRole($adminA, 'Shadow admin', ['roles.manage']),
            'unknown key' => fn () => $this->assign->grant($adminA, $teacherA, 'finance.everything'),
            'foreign role' => fn () => $this->assign->assignStaffRole($adminA, $teacherA, (int) DB::table('staff_roles')->insertGetId(['school_id' => $this->schoolB, 'name' => 'B role'])),
            'foreign school role' => fn () => $this->assign->createStaffRole($adminA, 'X', ['finance.view'], null, $this->schoolB),
        ];
        foreach ($cases as $label => $attempt) {
            try {
                $attempt();
                $this->fail("allowed: {$label}");
            } catch (AuthorizationException $e) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(0, DB::table('user_permissions')->count());
        $this->assertSame(0, DB::table('user_staff_roles')->count());
    }

    public function test_super_admin_delegates_only_with_an_explicit_school(): void
    {
        $super = $this->user(1, null, ['school_id' => null]);
        $this->actingAs($super);

        try {
            $this->assign->createStaffRole($super, 'No school', ['finance.view']);
            $this->fail('created a role without naming a school');
        } catch (AuthorizationException $e) {
            $this->assertStringContainsString('school explicitly', $e->getMessage());
        }

        $role = $this->assign->createStaffRole($super, 'Finance Officer', ['finance.view', 'finance.invoices'], null, $this->schoolB);
        $this->assign->assignStaffRole($super, $staffB = $this->user(17, $this->schoolB), $role);
        $this->assertTrue($this->perms->allows($staffB->fresh(), 'finance.invoices'));
        $this->assertEquals($this->schoolB, DB::table('user_staff_roles')->value('school_id'));
    }

    public function test_permission_changes_are_audited_with_before_and_after(): void
    {
        $this->actingAs($admin = $this->admin());
        $teacher = $this->user(3);

        $this->assign->grant($admin, $teacher, 'finance.view');
        $this->assign->revoke($admin, $teacher, 'finance.view');

        $rows = DB::table('audit_logs')->where('module', 'RBAC')->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertSame('PERMISSION_GRANTED', $rows[0]->action);
        $this->assertEquals($this->schoolA, $rows[0]->school_id);
        $this->assertEquals($teacher->id, $rows[0]->record_id);
        $this->assertEquals($admin->id, $rows[0]->user_id);
        $this->assertStringContainsString('"finance.view"', (string) $rows[0]->new_values);
        $this->assertStringNotContainsString('finance.view', (string) $rows[0]->old_values);
        $this->assertSame('PERMISSION_REVOKED', $rows[1]->action);
        $this->assertStringContainsString('finance.view', (string) $rows[1]->old_values);
    }

    // ── Migration ────────────────────────────────────────────────────────────

    public function test_the_migration_is_reversible_and_leaves_existing_role_data_alone(): void
    {
        DB::table('global_settings')->insert(['key' => 'role_perm_3', 'value' => json_encode(['view_online_exams'])]);
        $users = DB::table('users')->count();

        (require base_path(self::MIGRATION))->down();
        foreach (['user_permissions', 'staff_roles', 'staff_role_permissions', 'user_staff_roles'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table);
        }
        (require base_path(self::MIGRATION))->up();
        foreach (['user_permissions', 'staff_roles', 'staff_role_permissions', 'user_staff_roles'] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table);
            $this->assertContains("{$table}_" . ($table === 'staff_role_permissions' ? 'staff_role_id' : ($table === 'staff_roles' ? 'school_id' : 'school_id')) . '_index',
                collect(DB::select("PRAGMA index_list('{$table}')"))->pluck('name')->all(), $table);
        }

        $this->assertSame(json_encode(['view_online_exams']), DB::table('global_settings')->where('key', 'role_perm_3')->value('value'));
        $this->assertSame($users, DB::table('users')->count());
        $this->assertTrue(Schema::hasColumn('users', 'role_id') && Schema::hasColumn('users', 'menu_permission'));
    }
}
