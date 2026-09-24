<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Permissions\RoleNavigationLayout;
use Tests\TestCase;

/**
 * RBAC Phase 1 — CHARACTERIZATION, not specification.
 *
 * Pins the two role → navigation mappings as they are today:
 *  - RoleNavigationLayout: which layout shared views (notifications) extend.
 *  - get_role_nav_permissions(): the section allow-list admin/navigation.blade.php
 *    and layouts/app.blade.php apply when a user's menu_permission is empty.
 *
 * KNOWN CONFLICT: get_role_nav_permissions() follows the MultiStaffMiddleware
 * numbering (10 = Registrar-style sections, 11 = Bursar-style, 12 = HOD,
 * 13 = Admissions), while the operational role 10 is Warden. A Warden only
 * sees that map on admin/* pages, which AdminMiddleware lets them reach.
 */
class RbacNavigationCharacterizationTest extends TestCase
{
    public function test_role_navigation_layout_for_every_role_id(): void
    {
        $expected = [
            1 => 'superadmin.navigation', 2 => 'admin.navigation', 3 => 'teacher.navigation',
            4 => 'accountant.navigation', 5 => 'librarian.navigation', 6 => 'parent.navigation',
            7 => 'student.navigation', 10 => 'warden.navigation',
        ];

        foreach (range(1, 20) as $roleId) {
            $layout = $expected[$roleId] ?? 'admin.navigation';
            $this->assertSame($layout, RoleNavigationLayout::name(User::factory()->make(['role_id' => $roleId])), "role {$roleId}");
            $this->assertTrue(view()->exists($layout), "{$layout} view exists");
        }

        $this->assertSame('admin.navigation', RoleNavigationLayout::name(null));
    }

    public function test_role_nav_permission_map_is_unchanged(): void
    {
        $expected = [
            1  => ['all'],
            2  => ['all'],
            3  => ['dashboard', 'students', 'attendance', 'assignments', 'online_exams', 'live_classes',
                   'gradebook', 'routine', 'noticeboard', 'chat', 'academic_calendar', 'question_bank'],
            4  => ['dashboard', 'fees', 'expenses', 'payments', 'payroll', 'salary_structures',
                   'fee_structures', 'hostel_fee', 'reports'],
            5  => ['dashboard', 'library', 'noticeboard', 'chat'],
            6  => [],
            7  => [],
            8  => [],
            9  => [],
            10 => ['dashboard', 'students', 'admissions', 'hei_admissions', 'intake_sessions',
                   'admissions_agents', 'programmes', 'enrolment', 'transcripts', 'graduation',
                   'noticeboard', 'reports'],
            11 => ['dashboard', 'fees', 'expenses', 'payments', 'payroll', 'fee_structures',
                   'hostel_fee', 'reports', 'noticeboard'],
            12 => ['dashboard', 'students', 'attendance', 'assignments', 'online_exams',
                   'gradebook', 'question_bank', 'routine', 'academic_calendar',
                   'departments', 'noticeboard', 'chat', 'reports'],
            13 => ['dashboard', 'students', 'admissions', 'hei_admissions', 'intake_sessions',
                   'admissions_agents', 'programmes', 'noticeboard', 'chat'],
            14 => ['dashboard', 'reports', 'transcripts', 'graduation', 'programmes',
                   'students', 'staff', 'payroll', 'expenses', 'procurement', 'assets',
                   'noticeboard', 'settings'],
            15 => ['dashboard', 'staff', 'leave', 'leave_types', 'appraisal', 'payroll',
                   'salary_structures', 'attendance', 'departments', 'noticeboard', 'reports'],
            16 => ['dashboard', 'procurement', 'assets', 'asset_categories', 'noticeboard'],
            17 => ['dashboard', 'assets', 'asset_categories', 'inventory', 'noticeboard'],
            18 => ['dashboard', 'students', 'fees', 'payments', 'admissions',
                   'noticeboard', 'chat'],
            19 => ['dashboard', 'online_exams', 'exams', 'question_bank', 'gradebook',
                   'transcripts', 'results', 'noticeboard'],
            20 => [],
        ];

        foreach ($expected as $roleId => $sections) {
            $this->assertSame($sections, get_role_nav_permissions($roleId), "role {$roleId}");
        }
    }

    public function test_role_can_see_follows_the_map_with_all_as_a_wildcard(): void
    {
        $this->assertTrue(role_can_see(2, 'anything_at_all'));
        $this->assertTrue(role_can_see(3, 'online_exams'));
        $this->assertFalse(role_can_see(3, 'fees'));
        $this->assertTrue(role_can_see(15, 'leave'));
        $this->assertFalse(role_can_see(9, 'dashboard'));

        // KNOWN CONFLICT: role 10 (Warden) is given Registrar-style sections.
        $this->assertTrue(role_can_see(10, 'transcripts'));
        $this->assertFalse(role_can_see(10, 'hostel'));
    }
}
