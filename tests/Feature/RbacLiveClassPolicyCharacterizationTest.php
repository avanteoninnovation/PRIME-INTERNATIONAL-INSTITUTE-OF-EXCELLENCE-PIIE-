<?php

namespace Tests\Feature;

use App\Models\LiveClass;
use App\Models\User;
use App\Policies\LiveClassPolicy;
use Tests\TestCase;

/**
 * RBAC Phase 1 — CHARACTERIZATION, not specification.
 *
 * Pins LiveClassPolicy exactly as it behaves today for every role_id, using
 * unsaved models (the policy never touches the database).
 *
 * KNOWN AUTHORIZATION CONFLICT (Phase 0) — pinned, NOT fixed:
 *   LiveClassPolicy's create()/canManageAll() lists [1, 2, 10, 12, 14] were
 *   written for the MultiStaffMiddleware numbering (10 = Registrar,
 *   12 = HOD, 14 = Director). Operationally role 10 is WARDEN, so a Warden
 *   can create live classes and edit/cancel/publish/delete EVERY live class
 *   in their school. A later, dedicated phase will correct this.
 *
 * Also pinned as-is: menu_permission NULL / '' / 'null' means ALLOW here
 * (unlike AdminPermission, where 'null' denies), and '[]' means DENY.
 */
class RbacLiveClassPolicyCharacterizationTest extends TestCase
{
    private const STAFF = [1, 2, 3, 4, 5, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
    private const CAN_CREATE = [1, 2, 3, 10, 12, 14];
    private const CAN_MANAGE_ALL = [1, 2, 10, 12, 14];
    private const CAN_MANAGE_PLATFORMS = [1, 2, 14];

    private LiveClassPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new LiveClassPolicy();
    }

    private function user(int $roleId, array $overrides = []): User
    {
        static $id = 100;

        $user = User::factory()->make(array_merge([
            'role_id' => $roleId,
            'school_id' => 1,
            'account_status' => 'active',
            'menu_permission' => null,
        ], $overrides));
        $user->id = ++$id;

        return $user;
    }

    private function liveClass(array $attributes = []): LiveClass
    {
        return (new LiveClass())->forceFill(array_merge([
            'id' => 1, 'school_id' => 1, 'teacher_id' => 999, 'created_by' => 999, 'is_published' => 1,
        ], $attributes));
    }

    public function test_role_matrix_with_null_menu_permission(): void
    {
        $someoneElsesClass = $this->liveClass();

        foreach (range(1, 20) as $roleId) {
            $user = $this->user($roleId);
            $isStaff = in_array($roleId, self::STAFF, true);

            $this->assertSame($isStaff || $roleId === 7, $this->policy->viewAny($user), "viewAny role {$roleId}");
            $this->assertSame(in_array($roleId, self::CAN_CREATE, true), $this->policy->create($user), "create role {$roleId}");
            $this->assertSame(in_array($roleId, self::CAN_MANAGE_ALL, true), $this->policy->update($user, $someoneElsesClass), "update role {$roleId}");
            $this->assertSame(in_array($roleId, self::CAN_MANAGE_ALL, true), $this->policy->delete($user, $someoneElsesClass), "delete role {$roleId}");
            $this->assertSame(in_array($roleId, self::CAN_MANAGE_ALL, true), $this->policy->publish($user, $someoneElsesClass), "publish role {$roleId}");
            $this->assertSame(in_array($roleId, self::CAN_MANAGE_PLATFORMS, true), $this->policy->managePlatforms($user), "managePlatforms role {$roleId}");
        }
    }

    public function test_known_authorization_conflict_warden_manages_every_live_class(): void
    {
        $warden = $this->user(10);
        $someoneElsesClass = $this->liveClass();

        $this->assertTrue($this->policy->create($warden));
        $this->assertTrue($this->policy->update($warden, $someoneElsesClass));
        $this->assertTrue($this->policy->cancel($warden, $someoneElsesClass));
        $this->assertTrue($this->policy->publish($warden, $someoneElsesClass));
        $this->assertTrue($this->policy->delete($warden, $someoneElsesClass));
        $this->assertFalse($this->policy->managePlatforms($warden));
    }

    public function test_teacher_only_manages_own_classes(): void
    {
        $teacher = $this->user(3);

        $this->assertTrue($this->policy->update($teacher, $this->liveClass(['teacher_id' => $teacher->id])));
        $this->assertTrue($this->policy->update($teacher, $this->liveClass(['teacher_id' => null, 'created_by' => $teacher->id])));
        $this->assertFalse($this->policy->update($teacher, $this->liveClass()));
        $this->assertTrue($this->policy->join($teacher, $this->liveClass()));
    }

    public function test_accountant_librarian_and_hr_can_view_and_join_but_not_create_or_manage_others(): void
    {
        foreach ([4, 5, 15] as $roleId) {
            $user = $this->user($roleId);
            $this->assertTrue($this->policy->viewAny($user), "role {$roleId}");
            $this->assertTrue($this->policy->join($user, $this->liveClass()), "role {$roleId}");
            $this->assertFalse($this->policy->create($user), "role {$roleId}");
            $this->assertFalse($this->policy->update($user, $this->liveClass()), "role {$roleId} on someone else's class");
            // update() checks staff + menu permission + ownership, not the
            // create() list — so a class assigned to them is still theirs to manage.
            $this->assertTrue($this->policy->update($user, $this->liveClass(['teacher_id' => $user->id])), "role {$roleId} as assigned owner");
        }
    }

    public function test_student_sees_only_published_classes_in_own_school(): void
    {
        $student = $this->user(7);

        $this->assertTrue($this->policy->view($student, $this->liveClass()));
        $this->assertFalse($this->policy->view($student, $this->liveClass(['is_published' => 0])));
        $this->assertFalse($this->policy->join($student, $this->liveClass(['school_id' => 2])));
        $this->assertFalse($this->policy->create($student));
        // The policy does not look at a student's account_status (StudentMiddleware does).
        $this->assertTrue($this->policy->viewAny($this->user(7, ['account_status' => 'disable'])));
    }

    public function test_menu_permission_shapes(): void
    {
        $cases = [
            'NULL'           => [null, true],
            'empty string'   => ['', true],
            "'null' string"  => ['null', true],
            "'[]'"           => ['[]', false],
            'key present'    => [json_encode(['admin.live_classes']), true],
            'key absent'     => [json_encode(['admin.teacher']), false],
        ];

        foreach ($cases as $label => [$menuPermission, $allowed]) {
            $admin = $this->user(2, ['menu_permission' => $menuPermission]);
            $this->assertSame($allowed, $this->policy->viewAny($admin), $label);
            $this->assertSame($allowed, $this->policy->update($admin, $this->liveClass()), $label);
        }

        // managePlatforms keys off admin.setting, not admin.live_classes.
        $this->assertFalse($this->policy->managePlatforms($this->user(2, ['menu_permission' => json_encode(['admin.live_classes'])])));
        $this->assertTrue($this->policy->managePlatforms($this->user(2, ['menu_permission' => json_encode(['admin.setting'])])));
    }

    public function test_disabled_staff_are_denied(): void
    {
        foreach ([2, 3, 10] as $roleId) {
            $this->assertFalse($this->policy->viewAny($this->user($roleId, ['account_status' => 'disable'])), "role {$roleId}");
        }
    }

    public function test_school_boundary_is_enforced_even_for_manage_all_roles(): void
    {
        $otherSchoolClass = $this->liveClass(['school_id' => 2]);

        foreach ([2, 10, 14] as $roleId) {
            $user = $this->user($roleId);
            $this->assertFalse($this->policy->view($user, $otherSchoolClass), "view role {$roleId}");
            $this->assertFalse($this->policy->update($user, $otherSchoolClass), "update role {$roleId}");
            $this->assertFalse($this->policy->join($user, $otherSchoolClass), "join role {$roleId}");
        }

        // Super admin has school_id NULL and so fails the per-class school check.
        $superAdmin = $this->user(1, ['school_id' => null]);
        $this->assertTrue($this->policy->viewAny($superAdmin));
        $this->assertFalse($this->policy->view($superAdmin, $this->liveClass()));
    }
}
