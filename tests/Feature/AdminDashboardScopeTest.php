<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the fix that separated the School Admin dashboard from the
 * Super Admin (platform) dashboard — AdminController::adminDashboard()
 * used to query platform-wide tables (schools, subscriptions) with no
 * school_id filter at all, leaking every school's aggregate data to
 * every School Admin.
 */
class AdminDashboardScopeTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_admin_dashboard_renders_and_never_mentions_platform_widgets(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);

        // The old (buggy) view/controller exposed these Super-Admin-only
        // concepts on the School Admin's own dashboard — they must never
        // reappear here.
        $response->assertDontSee('Total Schools', false);
        $response->assertDontSee('Add School', false);
        $response->assertDontSee('Active Subscriptions', false);
        $response->assertDontSee('Pending Requests', false);

        // The school-scoped replacements must be present instead.
        $response->assertSee('Total Students', false);
        $response->assertSee('Teachers', false);
        $response->assertSee('Fee Collected This Month', false);
    }

    public function test_admin_dashboard_counts_are_scoped_to_the_admins_own_school(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();

        $adminA = $this->makeAdminUser($schoolA);

        // 3 students in school A, 5 in school B.
        User::factory()->count(3)->create(['role_id' => 7, 'school_id' => $schoolA]);
        User::factory()->count(5)->create(['role_id' => 7, 'school_id' => $schoolB]);

        $response = $this->actingAs($adminA)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // School A's admin must see 3 (their own), never 8 (the platform total).
        $response->assertSee('>3<', false);
        $response->assertDontSee('>8<', false);
    }
}
