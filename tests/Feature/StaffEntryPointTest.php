<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use App\Support\Permissions\PermissionAssignmentService;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Staff entry points: the dashboard uses the canonical admin sidebar
 * (admin.navigation, no longer the stale layouts.app copy), the Staff menu
 * links Staff Directory / Add Staff / Roles & Permissions, and Add Staff is
 * only a launcher into the existing per-role create workflows — same guards,
 * same forms, same POST handlers.
 */
class StaffEntryPointTest extends TestCase
{
    use StaffModuleTestHelper;

    private const HR_MANAGER = 15;

    /** The existing create forms and the handlers they post to. */
    private const WORKFLOWS = [
        'admin' => ['form' => 'admin.open_modal', 'post' => 'admin.create', 'role' => 2],
        'teacher' => ['form' => 'admin.teacher.open_modal', 'post' => 'admin.teacher.create', 'role' => 3],
        'accountant' => ['form' => 'admin.accountant.open_modal', 'post' => 'admin.accountant.create', 'role' => 4],
        'librarian' => ['form' => 'admin.librarian.open_modal', 'post' => 'admin.librarian.create', 'role' => 5],
        'warden' => ['form' => 'admin.warden.create_form', 'post' => 'admin.warden.create', 'role' => 10],
    ];

    private int $school;
    private int $otherSchool;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        (require base_path('database/migrations/2026_09_23_000003_create_rbac_tables.php'))->up();
        (require base_path('database/migrations/2026_09_23_000004_add_is_active_to_staff_roles.php'))->up();

        $this->school = $this->makeSchool(['title' => 'School A', 'status' => 1]);
        $this->otherSchool = $this->makeSchool(['title' => 'School B', 'status' => 1]);
        $this->admin = $this->user(2, ['school_role' => 1]);
    }

    private function user(int $role, array $extra = []): User
    {
        return User::factory()->create($extra + ['role_id' => $role, 'school_id' => $this->school, 'account_status' => 'active']);
    }

    /** The Staff submenu's link labels, in order, from a rendered admin page. */
    private function staffMenu(string $html): array
    {
        $start = strpos($html, '<span class="link_name">Staff</span>');
        $this->assertNotFalse($start, 'Staff menu rendered');
        $menu = substr($html, $start, strpos($html, '</ul>', $start) - $start);
        preg_match_all('/<li><a [^>]*><span>([^<]+)<\/span><\/a><\/li>/', $menu, $m);

        return array_map('html_entity_decode', $m[1]);
    }

    /** Every top-level sidebar label (link_name), in order. */
    private function sidebar(string $html): array
    {
        preg_match_all('/class="link_name">([^<]+)</', $html, $m);

        return array_map('html_entity_decode', $m[1]);
    }

    // ── Dashboard uses the canonical sidebar ────────────────────────────────

    public function test_dashboard_and_other_admin_pages_render_the_same_canonical_sidebar(): void
    {
        $dashboard = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
        $other = $this->actingAs($this->admin)->get(route('admin.teacher'))->assertOk();

        $this->assertSame($this->sidebar($other->getContent()), $this->sidebar($dashboard->getContent()));
        $this->assertContains('Roles & Permissions', $this->sidebar($dashboard->getContent()));
        $dashboard->assertSee(route('admin.rbac.roles.index'), false);

        // Dashboard content and its Font Awesome icons are still there.
        $dashboard->assertSee('Total Students', false)->assertSee('font-awesome/6.5.0', false)->assertSee('fas fa-users', false);
        $this->assertStringNotContainsString("@extends('layouts.app')", file_get_contents(resource_path('views/admin/dashboard.blade.php')));
    }

    public function test_school_admin_staff_menu_has_the_new_entries_then_every_existing_one(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertSame(
            ['Staff Directory', 'Add Staff', 'Roles & Permissions', 'Admin', 'Teacher', 'Accountant', 'Librarian', 'Warden', 'Teacher Permission', 'Designation'],
            $this->staffMenu($html)
        );
        foreach (['admin.rbac.staff.index', 'admin.staff.add', 'admin.rbac.roles.index', 'admin.teacher.permission'] as $route) {
            $this->assertStringContainsString('href="' . route($route) . '"', $html, $route);
        }
    }

    // ── Add Staff launcher ──────────────────────────────────────────────────

    public function test_school_admin_launcher_offers_all_five_existing_workflows(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.staff.add'))->assertOk()->getContent();

        preg_match_all('/data-staff-type="([a-z]+)"/', $html, $types);
        $this->assertSame(array_keys(self::WORKFLOWS), $types[1]);
        foreach (self::WORKFLOWS as $workflow) {
            // Opens the existing form in the same modal the list page uses.
            $this->assertStringContainsString("rightModal('" . route($workflow['form']) . "'", $html);
        }
        $this->assertStringContainsString(route('admin.rbac.staff.index'), $html);
    }

    public function test_each_launched_form_is_the_existing_form_posting_to_the_existing_handler(): void
    {
        foreach (self::WORKFLOWS as $key => $workflow) {
            $form = $this->actingAs($this->admin)->get(route($workflow['form']), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->getContent();
            $this->assertStringContainsString('action="' . route($workflow['post']) . '"', $form, $key);
        }

        // The existing handler still creates the base role it always did.
        $this->actingAs($this->admin)->post(route('admin.teacher.create'), [
            'first_name' => 'Benjamin', 'last_name' => 'Okoth', 'email' => 'benjamin@a.test', 'gender' => 'Male', 'blood_group' => 'a+',
            'birthday' => '1990-01-01', 'phone' => '0700', 'address' => 'Nairobi', 'password_mode' => 'manual', 'password' => 'secret-pass',
        ])->assertRedirect();
        $benjamin = User::where('email', 'benjamin@a.test')->first();
        $this->assertNotNull($benjamin);
        $this->assertSame(3, (int) $benjamin->role_id);
        $this->assertSame($this->school, (int) $benjamin->school_id);

        // …and the new staff member appears in the Staff Directory, ready for Manage Access.
        $this->actingAs($this->admin)->get(route('admin.rbac.staff.index'))->assertOk()->assertSee('Benjamin Okoth');
        $this->actingAs($this->admin)->get(route('admin.rbac.staff.show', $benjamin->id))->assertOk()->assertSee('Effective access');
    }

    public function test_hr_manager_gets_the_four_hr_workflows_but_never_admin_creation(): void
    {
        $hr = $this->user(self::HR_MANAGER);

        $html = $this->actingAs($hr)->get(route('admin.staff.add'))->assertOk()->getContent();
        preg_match_all('/data-staff-type="([a-z]+)"/', $html, $types);
        $this->assertSame(['teacher', 'accountant', 'librarian', 'warden'], $types[1]);
        $this->assertStringNotContainsString(route('admin.open_modal'), $html);

        // The existing guards still decide: HR may open the teacher form, never the admin form.
        $this->actingAs($hr)->get(route('admin.teacher.open_modal'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertNotSame(200, $this->actingAs($hr)->get(route('admin.open_modal'))->getStatusCode());
        $this->assertNotSame(200, $this->actingAs($hr)->post(route('admin.create'), ['email' => 'x@a.test'])->getStatusCode());
        $this->assertFalse(User::where('email', 'x@a.test')->exists());

        // HR is not an RBAC administrator: no Staff Directory / Roles & Permissions.
        $this->assertSame(403, $this->actingAs($hr)->get(route('admin.rbac.staff.index'))->getStatusCode());
    }

    public function test_other_staff_and_delegates_cannot_reach_the_launcher_or_see_add_staff(): void
    {
        $delegate = $this->user(17);
        $this->actingAs($this->admin);
        // Even a delegated staff.create grant does not open the School Admin / HR creation workflows.
        app(PermissionAssignmentService::class)->grantMany($this->admin, $delegate, ['staff.view', 'staff.create', 'staff.edit']);

        foreach ([3, 4, 5, 10, 19] as $role) {
            $this->assertLauncherDenied($this->user($role));
        }
        $this->assertLauncherDenied($delegate->fresh());
        $this->assertLauncherDenied($this->user(7));
        $this->assertLauncherDenied($this->user(6));
        $this->assertLauncherDenied($this->user(2, ['account_status' => 'disable']));

        // Delegated staff with staff.view see the Staff menu without the School Admin entries.
        $html = $this->actingAs($delegate->fresh())->get(route('admin.teacher'))->assertOk()->getContent();
        $menu = $this->staffMenu($html);
        $this->assertNotContains('Add Staff', $menu);
        $this->assertNotContains('Staff Directory', $menu);
        $this->assertNotContains('Roles & Permissions', $menu);
        $this->assertStringNotContainsString(route('admin.rbac.roles.index'), $html);
    }

    private function assertLauncherDenied(User $user): void
    {
        $response = $this->actingAs($user)->get(route('admin.staff.add'));
        $this->assertNotSame(200, $response->getStatusCode(), "role {$user->role_id} must not open Add Staff");
        $this->assertStringNotContainsString('data-staff-type', (string) $response->getContent());
    }

    // ── Staff parent menu follows its children ──────────────────────────────

    /** Staff submenu label => href, or null when the Staff parent is not rendered. */
    private function staffLinks(User $user, string $page = 'admin.teacher'): ?array
    {
        // Rendered on an admin page the user can open, so a missing menu is really a hidden menu.
        $response = $this->actingAs($user)->get(route($page));
        $this->assertSame(200, $response->getStatusCode(), "role {$user->role_id} must be able to open {$page}");
        $html = $response->getContent();
        $this->assertStringContainsString('<span class="link_name">Dashboard</span>', $html, 'admin sidebar rendered');
        $start = strpos($html, '<span class="link_name">Staff</span>');
        if ($start === false) {
            return null;
        }
        $menu = substr($html, $start, strpos($html, '</ul>', $start) - $start);
        preg_match_all('/<li><a [^>]*href="([^"]+)"[^>]*><span>([^<]+)<\/span><\/a><\/li>/', $menu, $m);

        return array_combine(array_map('html_entity_decode', $m[2]), $m[1]);
    }

    /** Every Staff child shown to $user really opens for $user (menu never promises what the backend refuses). */
    private function assertStaffLinksAreLegitimate(User $user, array $links): void
    {
        $this->assertNotEmpty($links, "role {$user->role_id}: a rendered Staff menu is never empty");
        foreach ($links as $label => $href) {
            $this->assertSame(200, $this->actingAs($user)->get($href)->getStatusCode(), "role {$user->role_id}: '{$label}' is shown, so it must open");
        }
    }

    private function bootAssetsTable(): void
    {
        foreach (['asset_categories', 'assets'] as $table) {
            \Illuminate\Support\Facades\Schema::create($table, function ($t) use ($table) {
                $t->id();
                $t->unsignedBigInteger('school_id');
                $t->string('name');
                if ($table === 'assets') {
                    $t->unsignedBigInteger('category_id')->nullable();
                    $t->string('condition')->default('good');
                }
                $t->timestamps();
            });
        }
    }

    public function test_hr_manager_sees_staff_menu_with_add_staff_but_no_admin_creation_or_rbac_entries(): void
    {
        $this->bootAssetsTable();
        $hr = $this->user(self::HR_MANAGER);
        $links = $this->staffLinks($hr);

        $this->assertNotNull($links, 'HR Manager sees the Staff menu');
        foreach (['Add Staff', 'Teacher', 'Accountant', 'Librarian', 'Warden'] as $entry) {
            $this->assertArrayHasKey($entry, $links);
        }
        $this->assertArrayNotHasKey('Staff Directory', $links);
        $this->assertArrayNotHasKey('Roles & Permissions', $links);
        $this->assertStaffLinksAreLegitimate($hr, $links);

        // The Admin entry (if shown) is the shared administrator directory, never admin creation.
        if (isset($links['Admin'])) {
            $this->actingAs($hr)->get($links['Admin'])->assertOk()->assertDontSee(route('admin.open_modal'), false);
        }
        $this->actingAs($hr)->get(route('admin.staff.add'))->assertOk()->assertDontSee('data-staff-type="admin"', false);
    }

    public function test_hr_manager_with_a_restrictive_legacy_menu_list_still_gets_add_staff(): void
    {
        // Previously the whole Staff section was hidden by the legacy keys even though Add Staff works.
        $this->bootAssetsTable();
        foreach (['2022_07_04_150637_create_leavelists_table', '2026_07_02_130005_fill_leavelists_columns', '2026_07_02_130006_create_leave_types_table'] as $migration) {
            (require base_path("database/migrations/{$migration}.php"))->up();
        }
        $hr = $this->user(self::HR_MANAGER, ['menu_permission' => json_encode(['admin.leave', 'admin.leave_types'])]);

        $links = $this->staffLinks($hr, 'admin.leave.index');
        $this->assertSame(['Add Staff' => route('admin.staff.add')], $links);
        $this->assertStaffLinksAreLegitimate($hr, $links);
    }

    public function test_school_admin_staff_links_are_all_legitimate(): void
    {
        $this->bootAssetsTable();
        $links = $this->staffLinks($this->admin);

        $this->assertSame(['Staff Directory', 'Add Staff', 'Roles & Permissions', 'Admin', 'Teacher', 'Accountant', 'Librarian', 'Warden', 'Teacher Permission', 'Designation'], array_keys($links));
        $this->assertStaffLinksAreLegitimate($this->admin, $links);
    }

    public function test_teacher_and_accountant_see_only_legitimate_staff_links_and_no_privileged_entries(): void
    {
        $this->bootAssetsTable();
        foreach ([3, 4] as $role) {
            $user = $this->user($role);
            $links = $this->staffLinks($user);
            if ($links === null) {
                continue;   // hidden entirely is fine
            }
            foreach (['Add Staff', 'Staff Directory', 'Roles & Permissions', 'Teacher Permission'] as $privileged) {
                $this->assertArrayNotHasKey($privileged, $links, "role {$role}");
            }
            $this->assertStaffLinksAreLegitimate($user, $links);
        }
    }

    public function test_staff_parent_is_hidden_when_no_child_is_available(): void
    {
        $this->bootAssetsTable();
        // Legacy per-user list without any Staff key, and no staff creation rights: nothing to show.
        foreach ([16, 17] as $role) {
            $user = $this->user($role, ['menu_permission' => json_encode(['admin.assets', 'admin.asset_categories'])]);
            $this->assertNull($this->staffLinks($user, 'admin.assets.index'), "role {$role}");
        }
    }

    public function test_delegated_rbac_access_drives_staff_children(): void
    {
        $this->bootAssetsTable();
        $keeper = $this->user(17);
        $this->assertArrayNotHasKey('Teacher Permission', $this->staffLinks($keeper) ?? []);

        $this->actingAs($this->admin);
        app(PermissionAssignmentService::class)->grant($this->admin, $keeper, 'staff.teacher_assignments');
        $links = $this->staffLinks($keeper->fresh());

        $this->assertArrayHasKey('Teacher Permission', $links);
        $this->assertArrayNotHasKey('Add Staff', $links, 'a grant never opens the School Admin / HR creation workflows');
        $this->assertStaffLinksAreLegitimate($keeper->fresh(), $links);

        // Direct URLs unchanged: still refused for the delegate.
        $this->assertNotSame(200, $this->actingAs($keeper->fresh())->get(route('admin.staff.add'))->getStatusCode());
        $this->assertSame(403, $this->actingAs($keeper->fresh())->get(route('admin.rbac.staff.index'))->getStatusCode());
    }

    // ── Staff Directory = RBAC Staff Access (no second list) ────────────────

    public function test_staff_directory_is_the_rbac_staff_access_screen_and_stays_tenant_scoped(): void
    {
        $this->user(3, ['name' => 'Anna Teacher']);
        User::factory()->create(['role_id' => 3, 'school_id' => $this->otherSchool, 'name' => 'Zed Foreign', 'account_status' => 'active']);
        $this->user(7, ['name' => 'Stu Student']);

        $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->getContent();
        $this->assertStringContainsString('href="' . route('admin.rbac.staff.index') . '"><span>Staff Directory', $html);

        $this->actingAs($this->admin)->get(route('admin.rbac.staff.index'))->assertOk()
            ->assertSee('Anna Teacher')->assertDontSee('Zed Foreign')->assertDontSee('Stu Student');
    }

    public function test_the_registry_route_map_is_unchanged_for_the_launcher(): void
    {
        // The launcher is guarded by the existing school_admin:hr middleware (EnforceRoutePermission defers
        // to it), so it needs no — and gets no — new RBAC permission or mapping.
        $this->assertArrayNotHasKey('admin.staff.add', PermissionRegistry::routes());
        $this->assertSame(0, DB::table('staff_roles')->count());
    }
}
