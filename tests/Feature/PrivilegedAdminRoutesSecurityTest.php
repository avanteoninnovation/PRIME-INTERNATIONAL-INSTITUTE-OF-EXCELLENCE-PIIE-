<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * RBAC Phase 2A — the four privileged administrator-management actions.
 *
 * AdminMiddleware deliberately admits every staff role (3, 4, 5, 9–19) so
 * they can use shared admin/* pages; that stays as-is. These four actions
 * must not ride along with it:
 *
 *  - admin.create                        create a School Administrator
 *  - admin.admin.reset_password          reset a School Administrator's password
 *  - admin.settings.permissions.save     save role permission sets
 *      -> School Administrator (role 2) only.
 *  - admin.admin.menu_permission_update  change another admin's permissions
 *      -> the school's primary administrator only (role 2 + school_role 1,
 *         the same condition admin_list.blade.php already uses to show the
 *         "Admin Permission" action), and never on their own account.
 *
 * Super Admin (role 1) never passed AdminMiddleware, so these routes were
 * never available to it; that is unchanged. Target lookups are scoped to the
 * authenticated user's school via AdminController::findStaffOrFail().
 */
class PrivilegedAdminRoutesSecurityTest extends TestCase
{
    use StaffModuleTestHelper;

    /** Staff roles AdminMiddleware admits that are NOT school administrators. */
    private const NON_ADMIN_STAFF = ['teacher' => 3, 'accountant' => 4, 'librarian' => 5, 'warden' => 10, 'hr manager' => 15];

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        Mail::fake();
        $this->schoolId = $this->makeSchool();
    }

    private function userWithRole(int $roleId, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => $roleId,
            'school_id' => $roleId === 1 ? null : $this->schoolId,
            'account_status' => 'active',
        ], $overrides));
    }

    private function primaryAdmin(array $overrides = []): User
    {
        return $this->userWithRole(2, array_merge(['school_role' => 1], $overrides));
    }

    private function createAdminPayload(string $email): array
    {
        return [
            'email' => $email, 'first_name' => 'New', 'last_name' => 'Admin', 'gender' => 'Female',
            'blood_group' => 'o+', 'birthday' => '01/01/1990', 'phone' => '0700000009', 'address' => '1 Test Street',
        ];
    }

    private function deniedRoles(): array
    {
        return self::NON_ADMIN_STAFF + ['parent' => 6, 'student' => 7, 'super admin' => 1];
    }

    // ── admin.create ─────────────────────────────────────────────────────────

    public function test_only_a_school_administrator_can_create_an_administrator(): void
    {
        foreach ($this->deniedRoles() as $label => $roleId) {
            $email = "created-by-role-{$roleId}@example.test";
            $this->actingAs($this->userWithRole($roleId))->post(route('admin.create'), $this->createAdminPayload($email));
            $this->assertFalse(User::where('email', $email)->exists(), "{$label} must not create an administrator");
        }

        $this->actingAs($this->userWithRole(2))->post(route('admin.create'), $this->createAdminPayload('by-admin@example.test'));
        $created = User::where('email', 'by-admin@example.test')->firstOrFail();
        $this->assertSame(2, (int) $created->role_id);
        $this->assertSame($this->schoolId, (int) $created->school_id);
    }

    public function test_created_administrator_always_joins_the_creators_school_regardless_of_request_input(): void
    {
        $otherSchool = $this->makeSchool(['title' => 'School B']);

        $this->actingAs($this->userWithRole(2))->post(route('admin.create'), $this->createAdminPayload('spoof@example.test') + [
            'school_id' => $otherSchool, 'role_id' => 1,
        ]);

        $created = User::where('email', 'spoof@example.test')->firstOrFail();
        $this->assertSame($this->schoolId, (int) $created->school_id);
        $this->assertSame(2, (int) $created->role_id);
    }

    public function test_denied_staff_are_sent_to_their_own_dashboard(): void
    {
        $response = $this->actingAs($this->userWithRole(3))->post(route('admin.create'), $this->createAdminPayload('x@example.test'));

        $response->assertRedirect(route('teacher.dashboard'));
    }

    // ── admin.admin.reset_password ───────────────────────────────────────────

    public function test_only_a_school_administrator_can_reset_an_administrators_password(): void
    {
        $target = $this->userWithRole(2);
        $originalHash = $target->password;

        foreach ($this->deniedRoles() as $label => $roleId) {
            $this->actingAs($this->userWithRole($roleId))->get(route('admin.admin.reset_password', $target->id));
            $this->assertSame($originalHash, $target->fresh()->password, "{$label} must not reset an administrator's password");
        }

        $this->actingAs($this->userWithRole(2))->get(route('admin.admin.reset_password', $target->id));
        $this->assertNotSame($originalHash, $target->fresh()->password);
    }

    public function test_an_administrator_cannot_reset_another_schools_administrator(): void
    {
        $otherSchool = $this->makeSchool(['title' => 'School B']);
        $foreignAdmin = User::factory()->create(['role_id' => 2, 'school_id' => $otherSchool, 'account_status' => 'active']);
        $originalHash = $foreignAdmin->password;

        $this->actingAs($this->primaryAdmin())->get(route('admin.admin.reset_password', $foreignAdmin->id));

        $this->assertSame($originalHash, $foreignAdmin->fresh()->password);
    }

    // ── admin.admin.menu_permission_update ───────────────────────────────────

    /**
     * KNOWN PRE-EXISTING BUG (not fixed in Phase 2A): menu_permission is not
     * in User::$fillable, so AdminController::menuPermissionUpdate()'s
     * $user->update([...]) is silently a no-op for everyone — the stored
     * value never changes. These tests therefore assert the authorization
     * outcome (denied users are sent to their own home by
     * PortalAccessDenial; the primary admin reaches the controller, which
     * redirects back) rather than a data change.
     */
    private function assertDeniedToOwnHome(\Illuminate\Testing\TestResponse $response, User $user, string $label): void
    {
        $this->assertSame(route(\App\Support\Permissions\RoleHomeRoute::name($user)), $response->headers->get('Location'), $label);
    }

    public function test_only_the_primary_administrator_can_change_another_administrators_permissions(): void
    {
        $target = $this->userWithRole(2);
        $payload = ['permissions' => ['admin.teacher']];

        foreach ($this->deniedRoles() + ['non-primary admin' => 2] as $label => $roleId) {
            $user = $this->userWithRole($roleId);
            $response = $this->actingAs($user)->post(route('admin.admin.menu_permission_update', $target->id), $payload);
            $this->assertDeniedToOwnHome($response, $user, "{$label} must not change an administrator's permissions");
            $this->assertNull($target->fresh()->menu_permission);
        }

        $response = $this->actingAs($this->primaryAdmin())->post(route('admin.admin.menu_permission_update', $target->id), $payload);
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(url('/'), $response->headers->get('Location'), 'primary admin reaches the controller (redirect back)');
        $this->assertSame('You have successfully updated user permissions.', session('message'));
    }

    public function test_an_administrator_cannot_change_their_own_permissions(): void
    {
        $primary = $this->primaryAdmin(['menu_permission' => json_encode(['admin.teacher'])]);

        $response = $this->actingAs($primary)->post(route('admin.admin.menu_permission_update', $primary->id), [
            'permissions' => ['admin.teacher', 'admin.admin', 'admin.settings.permissions'],
        ]);

        $this->assertDeniedToOwnHome($response, $primary, 'self-escalation');
        $this->assertNull(session('message'));
        $this->assertSame(['admin.teacher'], json_decode($primary->fresh()->menu_permission, true));
    }

    public function test_the_permission_form_is_not_shown_to_non_primary_users(): void
    {
        $target = $this->userWithRole(2);

        $this->actingAs($this->userWithRole(3))->get(route('admin.admin.menu_permission', $target->id))
            ->assertRedirect(route('teacher.dashboard'));
        $this->actingAs($this->userWithRole(2))->get(route('admin.admin.menu_permission', $target->id))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_the_primary_administrator_cannot_change_another_schools_administrator(): void
    {
        $otherSchool = $this->makeSchool(['title' => 'School B']);
        $foreignAdmin = User::factory()->create(['role_id' => 2, 'school_id' => $otherSchool, 'account_status' => 'active']);

        $response = $this->actingAs($this->primaryAdmin())->post(route('admin.admin.menu_permission_update', $foreignAdmin->id), [
            'permissions' => ['admin.teacher'],
        ]);

        // findStaffOrFail() scopes the target to the authenticated user's school.
        $this->assertSame(404, $response->getStatusCode());
        $this->assertNull($foreignAdmin->fresh()->menu_permission);
    }

    // ── admin.settings.permissions.save ──────────────────────────────────────

    public function test_only_a_school_administrator_can_save_role_permissions(): void
    {
        DB::table('roles')->insert(['role_id' => 3, 'name' => 'teacher', 'school_id' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('global_settings')->insert(['key' => 'role_perm_3', 'value' => json_encode(['view_online_exams']), 'created_at' => now(), 'updated_at' => now()]);
        $payload = ['perms' => [3 => ['edit_all_online_exams', 'publish_online_exams']]];

        foreach ($this->deniedRoles() as $label => $roleId) {
            $this->actingAs($this->userWithRole($roleId))->post(route('admin.settings.permissions.save'), $payload);
            $this->assertSame(['view_online_exams'], json_decode(DB::table('global_settings')->where('key', 'role_perm_3')->value('value'), true), "{$label} must not save role permissions");
        }

        $this->actingAs($this->userWithRole(2))->post(route('admin.settings.permissions.save'), $payload);
        $this->assertSame(['edit_all_online_exams', 'publish_online_exams'], json_decode(DB::table('global_settings')->where('key', 'role_perm_3')->value('value'), true));
    }

    // ── UI: privileged controls are hidden (the backend guard is what enforces) ──

    public function test_admin_list_only_shows_privileged_controls_to_administrators(): void
    {
        // The existing list never offers "Admin Permission" on its first row
        // ($key != 0), so the primary admin is created first.
        $primary = $this->primaryAdmin();
        $other = $this->userWithRole(2);

        $asAdmin = $this->actingAs($primary)->get(route('admin.admin'));
        $asAdmin->assertOk();
        $asAdmin->assertSee(route('admin.open_modal'), false);
        $asAdmin->assertSee(route('admin.admin.reset_password', ['id' => $other->id]), false);
        $asAdmin->assertSee(route('admin.admin.menu_permission', ['id' => $other->id]), false);

        $asTeacher = $this->actingAs($this->userWithRole(3))->get(route('admin.admin'));
        $asTeacher->assertOk();
        $asTeacher->assertDontSee(route('admin.open_modal'), false);
        $asTeacher->assertDontSee(route('admin.admin.reset_password', ['id' => $other->id]), false);
        $asTeacher->assertDontSee(route('admin.admin.menu_permission', ['id' => $other->id]), false);
    }

    // ── Shared admin pages stay open to the staff roles that use them ───────

    public function test_non_admin_staff_still_reach_ordinary_admin_pages(): void
    {
        foreach (['admin.admin', 'admin.teacher', 'admin.leave.index', 'admin.dashboard'] as $routeName) {
            $this->assertNotContains('school_admin', \Illuminate\Support\Facades\Route::getRoutes()->getByName($routeName)->middleware(), $routeName);
        }

        foreach (self::NON_ADMIN_STAFF as $label => $roleId) {
            $this->actingAs($this->userWithRole($roleId));
            $response = (new \App\Http\Middleware\AdminMiddleware())->handle(\Illuminate\Http\Request::create('/admin/admin'), fn () => new \Illuminate\Http\Response('reached'));
            $this->assertSame('reached', $response->getContent(), "{$label} still passes AdminMiddleware");
        }
    }
}
