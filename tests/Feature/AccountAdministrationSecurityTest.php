<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * RBAC Phase 2B — staff & administrator ACCOUNT administration.
 *
 * Entering the shared admin portal (AdminMiddleware, unchanged) is not the
 * same as administering other people's accounts. Until the future RBAC
 * delegation engine exists:
 *
 *  - School Administrator (role 2): full account administration within
 *    their own school.
 *  - HR Manager (15): may create, edit (profile / employment / staff_status)
 *    and delete Teacher / Accountant / Librarian / Warden records — HR's
 *    existing employment-management reach — but NOT their security identity:
 *    no login-email change, password reset/set, activation resend or
 *    account enable/disable, and nothing at all on administrator accounts.
 *  - Teacher (3), Accountant (4), Librarian (5), Warden (10) and other staff
 *    roles: no staff/administrator account administration.
 *  - Parent / Student never pass AdminMiddleware.
 *
 * Student and parent account management is out of scope (Phase 2B leaves
 * its role reach as-is) apart from closing cross-school password setting.
 */
class AccountAdministrationSecurityTest extends TestCase
{
    use StaffModuleTestHelper;

    private const STAFF_TARGETS = [
        'teacher'    => [3, 'create' => 'admin.teacher.create', 'update' => 'admin.teacher.update', 'delete' => 'admin.teacher.delete', 'reset' => 'admin.teacher.reset_password', 'resend' => 'admin.teacher.resend_activation', 'edit' => 'admin.teacher_edit_modal'],
        'accountant' => [4, 'create' => 'admin.accountant.create', 'update' => 'admin.accountant.update', 'delete' => 'admin.accountant.delete', 'reset' => 'admin.accountant.reset_password', 'resend' => 'admin.accountant.resend_activation', 'edit' => 'admin.accountant_edit_modal'],
        'librarian'  => [5, 'create' => 'admin.librarian.create', 'update' => 'admin.librarian.update', 'delete' => 'admin.librarian.delete', 'reset' => 'admin.librarian.reset_password', 'resend' => 'admin.librarian.resend_activation', 'edit' => 'admin.librarian_edit_modal'],
        'warden'     => [10, 'create' => 'admin.warden.create', 'update' => 'admin.warden.update', 'delete' => 'admin.warden.delete', 'reset' => 'admin.warden.reset_password', 'resend' => 'admin.warden.resend_activation', 'edit' => 'admin.warden_edit_modal'],
    ];

    private const ADMIN_TARGET = [2, 'update' => 'admin.update', 'delete' => 'admin.admin.delete', 'reset' => 'admin.admin.reset_password', 'resend' => 'admin.admin.resend_activation', 'edit' => 'admin.open_edit_modal'];

    /** Staff roles that pass AdminMiddleware but must not administer accounts. */
    private const NON_ADMIN_STAFF = ['teacher' => 3, 'accountant' => 4, 'librarian' => 5, 'warden' => 10];

    private int $schoolId;
    private int $n = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
        Mail::fake();
        $this->schoolId = $this->makeSchool();
    }

    private function user(int $roleId, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => $roleId,
            'school_id' => $roleId === 1 ? null : $this->schoolId,
            'account_status' => 'active',
            'user_information' => json_encode(['phone' => '111', 'photo' => '']),
        ], $overrides));
    }

    private function payload(string $email, array $extra = []): array
    {
        return array_merge([
            'email' => $email, 'first_name' => 'Pat', 'last_name' => 'Doe', 'gender' => 'Male', 'blood_group' => 'o+',
            'birthday' => '01/01/1990', 'phone' => '0700000001', 'address' => '1 Test Street',
        ], $extra);
    }

    private function freshEmail(): string
    {
        return 'account-' . (++$this->n) . '@example.test';
    }

    // ── Step 5: the admin.update takeover chain ──────────────────────────────

    public function test_teacher_cannot_take_over_a_school_admin_via_email_change_and_password_recovery(): void
    {
        Notification::fake();
        $admin = $this->user(2, ['email' => 'head@school.test']);
        $teacher = $this->user(3);

        $this->actingAs($teacher)->post(route('admin.update', $admin->id), $this->payload('attacker@evil.test'));
        $this->assertSame('head@school.test', $admin->fresh()->email, 'teacher must not change the admin login email');

        auth()->logout();
        $this->post(route('password.email'), ['email' => 'attacker@evil.test']);
        Notification::assertNotSentTo($admin->fresh(), ResetPassword::class);
    }

    // ── Administrator accounts: School Admin only ────────────────────────────

    public function test_only_a_school_admin_can_administer_administrator_accounts(): void
    {
        foreach (self::NON_ADMIN_STAFF + ['hr manager' => 15] as $label => $roleId) {
            $actor = $this->user($roleId);
            $target = $this->user(2);
            $hash = $target->password;

            $this->actingAs($actor)->post(route(self::ADMIN_TARGET['update'], $target->id), $this->payload($target->email, ['phone' => '999']));
            $this->assertStringNotContainsString('999', (string) $target->fresh()->user_information, "{$label}: edit admin");

            $this->actingAs($actor)->get(route(self::ADMIN_TARGET['resend'], $target->id));
            $this->assertSame($hash, $target->fresh()->password, "{$label}: resend admin activation");

            $this->actingAs($actor)->get(route('admin.account_disable', $target->id));
            $this->assertSame('active', $target->fresh()->account_status, "{$label}: disable admin");

            $this->actingAs($actor)->post(route('admin.user_password'), ['user_id' => $target->id, 'password' => 'owned-123']);
            $this->assertSame($hash, $target->fresh()->password, "{$label}: set admin password");

            $this->actingAs($actor)->get(route(self::ADMIN_TARGET['delete'], $target->id));
            $this->assertTrue(User::whereKey($target->id)->exists(), "{$label}: delete admin");

            $this->actingAs($actor)->get(route(self::ADMIN_TARGET['edit'], $target->id))
                ->assertRedirect(route(\App\Support\Permissions\RoleHomeRoute::name($actor)));
        }

        $admin = $this->user(2, ['school_role' => 1]);
        $target = $this->user(2);
        $this->actingAs($admin)->post(route('admin.update', $target->id), $this->payload('renamed-admin@example.test', ['phone' => '999']));
        $this->assertSame('renamed-admin@example.test', $target->fresh()->email);
        $this->assertStringContainsString('999', (string) $target->fresh()->user_information);

        $this->actingAs($admin)->get(route('admin.admin.delete', $target->id));
        $this->assertFalse(User::whereKey($target->id)->exists());
    }

    // ── Staff accounts ───────────────────────────────────────────────────────

    public function test_non_admin_staff_cannot_administer_other_staff_accounts(): void
    {
        foreach (self::STAFF_TARGETS as $targetLabel => $t) {
            foreach (self::NON_ADMIN_STAFF as $actorLabel => $roleId) {
                $actor = $this->user($roleId);
                $target = $this->user($t[0]);
                $hash = $target->password;
                $label = "{$actorLabel} -> {$targetLabel}";

                $email = $this->freshEmail();
                $this->actingAs($actor)->post(route($t['create']), $this->payload($email));
                $this->assertFalse(User::where('email', $email)->exists(), "{$label}: create");

                $this->actingAs($actor)->post(route($t['update'], $target->id), $this->payload($target->email, ['phone' => '999']));
                $this->assertStringNotContainsString('999', (string) $target->fresh()->user_information, "{$label}: edit");

                $this->actingAs($actor)->post(route($t['update'], $target->id), $this->payload('stolen@evil.test'));
                $this->assertNotSame('stolen@evil.test', $target->fresh()->email, "{$label}: email");

                foreach (['reset', 'resend'] as $action) {
                    $this->actingAs($actor)->get(route($t[$action], $target->id));
                    $this->assertSame($hash, $target->fresh()->password, "{$label}: {$action}");
                }

                $this->actingAs($actor)->post(route('admin.user_password'), ['user_id' => $target->id, 'password' => 'owned-123']);
                $this->assertSame($hash, $target->fresh()->password, "{$label}: set password");

                $this->actingAs($actor)->get(route('admin.account_disable', $target->id));
                $this->assertSame('active', $target->fresh()->account_status, "{$label}: disable");

                $this->actingAs($actor)->get(route($t['delete'], $target->id));
                $this->assertTrue(User::whereKey($target->id)->exists(), "{$label}: delete");
            }
        }
    }

    public function test_school_admin_administers_own_school_staff(): void
    {
        $admin = $this->user(2);

        foreach (self::STAFF_TARGETS as $label => $t) {
            $email = $this->freshEmail();
            $this->actingAs($admin)->post(route($t['create']), $this->payload($email));
            $created = User::where('email', $email)->firstOrFail();
            $this->assertSame($t[0], (int) $created->role_id, $label);

            $target = $this->user($t[0]);
            $hash = $target->password;
            $newEmail = $this->freshEmail();
            $this->actingAs($admin)->post(route($t['update'], $target->id), $this->payload($newEmail, ['phone' => '999']));
            $this->assertSame($newEmail, $target->fresh()->email, "{$label}: email");
            $this->assertStringContainsString('999', (string) $target->fresh()->user_information, "{$label}: edit");

            $this->actingAs($admin)->get(route($t['reset'], $target->id));
            $this->assertNotSame($hash, $target->fresh()->password, "{$label}: reset");

            $this->actingAs($admin)->get(route('admin.account_disable', $target->id));
            $this->assertSame('disable', $target->fresh()->account_status, "{$label}: disable");

            $this->actingAs($admin)->get(route($t['delete'], $target->id));
            $this->assertFalse(User::whereKey($target->id)->exists(), "{$label}: delete");
        }
    }

    // ── HR Manager: employment records yes, security identity no ─────────────

    public function test_hr_manager_keeps_staff_record_management_but_not_security_identity(): void
    {
        $hr = $this->user(15);

        foreach (self::STAFF_TARGETS as $label => $t) {
            $email = $this->freshEmail();
            $this->actingAs($hr)->post(route($t['create']), $this->payload($email));
            $this->assertTrue(User::where('email', $email)->exists(), "HR create {$label} (preserved)");

            $target = $this->user($t[0]);
            $hash = $target->password;
            $originalEmail = $target->email;

            $this->actingAs($hr)->post(route($t['update'], $target->id), $this->payload($originalEmail, ['phone' => '999', 'staff_status' => 'suspended']));
            $this->assertStringContainsString('999', (string) $target->fresh()->user_information, "HR edit {$label} profile (preserved)");
            $this->assertSame('suspended', $target->fresh()->staff_status, "HR staff_status {$label} (preserved)");

            $this->actingAs($hr)->post(route($t['update'], $target->id), $this->payload('hr-changed@example.test', ['phone' => '888']));
            $this->assertSame($originalEmail, $target->fresh()->email, "HR must not change {$label} login email");
            $this->assertStringNotContainsString('888', (string) $target->fresh()->user_information, "email-changing request is rejected as a whole");

            foreach (['reset', 'resend'] as $action) {
                $this->actingAs($hr)->get(route($t[$action], $target->id));
                $this->assertSame($hash, $target->fresh()->password, "HR {$action} {$label}");
            }

            $this->actingAs($hr)->post(route('admin.user_password'), ['user_id' => $target->id, 'password' => 'owned-123']);
            $this->assertSame($hash, $target->fresh()->password, "HR set password {$label}");

            $this->actingAs($hr)->get(route('admin.account_disable', $target->id));
            $this->assertSame('active', $target->fresh()->account_status, "HR account disable {$label}");

            $this->actingAs($hr)->get(route($t['delete'], $target->id));
            $this->assertFalse(User::whereKey($target->id)->exists(), "HR delete {$label} (preserved)");
        }
    }

    // ── Login email identity ─────────────────────────────────────────────────

    public function test_login_email_must_stay_unique_when_an_admin_changes_it(): void
    {
        // The live users table has no unique index on email (see the
        // 2014 create_users_table migration) — only this sqlite fixture does.
        // Drop it so the application check, not the fixture, is what's tested.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        $admin = $this->user(2, ['email' => 'head@school.test']);
        $teacher = $this->user(3);
        $originalEmail = $teacher->email;

        $this->actingAs($admin)->post(route('admin.teacher.update', $teacher->id), $this->payload('head@school.test'));
        $this->assertSame($originalEmail, $teacher->fresh()->email);
        $this->assertSame(1, User::where('email', 'head@school.test')->count());

        $other = $this->user(2);
        $this->actingAs($admin)->post(route('admin.update', $other->id), $this->payload($originalEmail));
        $this->assertNotSame($originalEmail, $other->fresh()->email);
    }

    public function test_unchanged_email_is_not_treated_as_an_email_change(): void
    {
        $hr = $this->user(15);
        $teacher = $this->user(3, ['email' => 'Mixed.Case@School.test']);

        $this->actingAs($hr)->post(route('admin.teacher.update', $teacher->id), $this->payload('Mixed.Case@School.test', ['phone' => '777']));

        $this->assertStringContainsString('777', (string) $teacher->fresh()->user_information);
    }

    // ── admin.user_password: tenant + credential protection ─────────────────

    public function test_no_one_can_set_a_password_outside_their_school_or_for_the_super_admin(): void
    {
        $otherSchool = $this->makeSchool(['title' => 'School B']);
        $foreignAdmin = User::factory()->create(['role_id' => 2, 'school_id' => $otherSchool, 'account_status' => 'active']);
        $superAdmin = $this->user(1);
        $foreignStudent = User::factory()->create(['role_id' => 7, 'school_id' => $otherSchool, 'account_status' => 'active']);

        foreach ([2, 3] as $roleId) {
            $actor = $this->user($roleId, ['school_role' => $roleId === 2 ? 1 : null]);
            foreach ([$foreignAdmin, $superAdmin, $foreignStudent] as $victim) {
                $hash = $victim->fresh()->password;
                $this->actingAs($actor)->post(route('admin.user_password'), ['user_id' => $victim->id, 'password' => 'owned-123']);
                $this->assertSame($hash, $victim->fresh()->password, "role {$roleId} -> user #{$victim->id}");
            }
        }
    }

    public function test_setting_a_password_requires_post(): void
    {
        $admin = $this->user(2);
        $teacher = $this->user(3);
        $hash = $teacher->password;

        $this->actingAs($admin)->get(route('admin.user_password', ['user_id' => $teacher->id, 'password' => 'via-get-123']));
        $this->assertSame($hash, $teacher->fresh()->password);

        $this->actingAs($admin)->post(route('admin.user_password'), ['user_id' => $teacher->id, 'password' => 'via-post-123']);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('via-post-123', $teacher->fresh()->password));
    }

    public function test_student_and_parent_account_actions_keep_their_current_same_school_reach(): void
    {
        // Student management is out of Phase 2B scope: non-admin staff who
        // could already set a same-school student/parent password or
        // enable/disable them still can — now only within their own school.
        $teacher = $this->user(3);
        $student = $this->user(7);
        $parent = $this->user(6);

        $this->actingAs($teacher)->post(route('admin.user_password'), ['user_id' => $student->id, 'password' => 'student-123']);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('student-123', $student->fresh()->password));

        $this->actingAs($teacher)->get(route('admin.account_disable', $parent->id));
        $this->assertSame('disable', $parent->fresh()->account_status);
    }

    // ── Escalation / movement / cross-school ─────────────────────────────────

    public function test_request_supplied_role_and_school_are_ignored(): void
    {
        $admin = $this->user(2);
        $otherSchool = $this->makeSchool(['title' => 'School B']);

        foreach (self::STAFF_TARGETS as $label => $t) {
            $email = $this->freshEmail();
            $this->actingAs($admin)->post(route($t['create']), $this->payload($email, ['role_id' => 1, 'school_id' => $otherSchool, 'school_role' => 1]));
            $created = User::where('email', $email)->firstOrFail();
            $this->assertSame($t[0], (int) $created->role_id, "{$label} create role");
            $this->assertSame($this->schoolId, (int) $created->school_id, "{$label} create school");

            $target = $this->user($t[0]);
            $this->actingAs($admin)->post(route($t['update'], $target->id), $this->payload($target->email, ['role_id' => 1, 'school_id' => $otherSchool, 'school_role' => 1]));
            $this->assertSame($t[0], (int) $target->fresh()->role_id, "{$label} update role");
            $this->assertSame($this->schoolId, (int) $target->fresh()->school_id, "{$label} update school");
            $this->assertNull($target->fresh()->school_role, "{$label} update school_role");
        }

        $self = $this->user(2);
        $this->actingAs($self)->post(route('admin.update', $self->id), $this->payload($self->email, ['role_id' => 1, 'school_role' => 1]));
        $this->assertSame(2, (int) $self->fresh()->role_id);
        $this->assertNull($self->fresh()->school_role);
    }

    public function test_school_admin_cannot_administer_another_schools_accounts(): void
    {
        $otherSchool = $this->makeSchool(['title' => 'School B']);
        $admin = $this->user(2);
        $foreignAdmin = User::factory()->create(['role_id' => 2, 'school_id' => $otherSchool, 'account_status' => 'active']);
        $foreignTeacher = User::factory()->create(['role_id' => 3, 'school_id' => $otherSchool, 'account_status' => 'active']);

        $this->actingAs($admin)->post(route('admin.update', $foreignAdmin->id), $this->payload('x@example.test'))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.teacher.update', $foreignTeacher->id), $this->payload('y@example.test'))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.teacher.delete', $foreignTeacher->id))->assertNotFound();
        $this->actingAs($admin)->get(route('admin.account_disable', $foreignTeacher->id));

        $this->assertNotSame('x@example.test', $foreignAdmin->fresh()->email);
        $this->assertNotSame('y@example.test', $foreignTeacher->fresh()->email);
        $this->assertSame('active', $foreignTeacher->fresh()->account_status);
    }

    // ── UI: controls follow the backend rules (the backend is what enforces) ──

    public function test_staff_list_controls_follow_account_administration_rules(): void
    {
        $listed = $this->user(3);
        $routes = [
            'create' => route('admin.teacher.open_modal'),
            'edit'   => route('admin.teacher_edit_modal', ['id' => $listed->id]),
            'delete' => route('admin.teacher.delete', ['id' => $listed->id]),
            'reset'  => route('admin.teacher.reset_password', ['id' => $listed->id]),
            'resend' => route('admin.teacher.resend_activation', ['id' => $listed->id]),
            'status' => route('admin.account_disable', ['id' => $listed->id]),
        ];
        $expectVisible = [
            'school admin' => [2, ['create', 'edit', 'delete', 'reset', 'resend', 'status']],
            'hr manager'   => [15, ['create', 'edit', 'delete']],
            'teacher'      => [3, []],
            'accountant'   => [4, []],
        ];

        foreach ($expectVisible as $label => [$roleId, $visible]) {
            $response = $this->actingAs($this->user($roleId))->get(route('admin.teacher'));
            $response->assertOk();
            foreach ($routes as $control => $url) {
                in_array($control, $visible, true)
                    ? $response->assertSee($url, false)
                    : $response->assertDontSee($url, false);
            }
        }
    }

    public function test_admin_list_account_controls_are_school_admin_only(): void
    {
        $primary = $this->user(2, ['school_role' => 1]);
        $listed = $this->user(2);
        $urls = [
            route('admin.open_edit_modal', ['id' => $listed->id]),
            route('admin.admin.delete', ['id' => $listed->id]),
            route('admin.admin.resend_activation', ['id' => $listed->id]),
            route('admin.account_disable', ['id' => $listed->id]),
        ];

        $asAdmin = $this->actingAs($primary)->get(route('admin.admin'));
        foreach ($urls as $url) {
            $asAdmin->assertSee($url, false);
        }

        $asHr = $this->actingAs($this->user(15))->get(route('admin.admin'));
        $asHr->assertOk();
        foreach ($urls as $url) {
            $asHr->assertDontSee($url, false);
        }
    }

    // ── Portal access itself is unchanged ────────────────────────────────────

    public function test_staff_roles_still_pass_admin_middleware_and_parents_students_do_not(): void
    {
        foreach ([3, 4, 5, 10, 15] as $roleId) {
            $this->actingAs($this->user($roleId));
            $response = (new \App\Http\Middleware\AdminMiddleware())->handle(\Illuminate\Http\Request::create('/admin/teacher'), fn () => new \Illuminate\Http\Response('reached'));
            $this->assertSame('reached', $response->getContent(), "role {$roleId}");
        }

        foreach ([6, 7] as $roleId) {
            $actor = $this->user($roleId);
            $target = $this->user(3);
            $this->actingAs($actor)->post(route('admin.teacher.update', $target->id), $this->payload('p@example.test'));
            $this->assertNotSame('p@example.test', $target->fresh()->email, "role {$roleId}");
        }
    }
}
