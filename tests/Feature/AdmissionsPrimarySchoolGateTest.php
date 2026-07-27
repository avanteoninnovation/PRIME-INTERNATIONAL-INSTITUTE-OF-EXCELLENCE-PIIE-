<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers restricting the Admissions module (Admissions, Intake Sessions,
 * Agents) to only the one school the public Apply Now portal currently
 * belongs to (global_settings.primary_school_id) — other schools created
 * via Super Admin have no public application intake yet, so the module is
 * hidden from their nav and blocked at the route level.
 */
class AdmissionsPrimarySchoolGateTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_primary_schools_admin_sees_the_admissions_nav_and_can_access_it(): void
    {
        $primarySchoolId = $this->makeSchool();
        DB::table('global_settings')->insert(['key' => 'primary_school_id', 'value' => (string) $primarySchoolId, 'created_at' => now(), 'updated_at' => now()]);
        $admin = $this->makeAdminUser($primarySchoolId);

        $response = $this->actingAs($admin)->get(route('admin.hei_admissions.index'));

        $response->assertStatus(200);
        $response->assertSee('Admissions', false);
    }

    public function test_non_primary_schools_admin_is_redirected_away_from_admissions(): void
    {
        $primarySchoolId = $this->makeSchool();
        $otherSchoolId = $this->makeSchool();
        DB::table('global_settings')->insert(['key' => 'primary_school_id', 'value' => (string) $primarySchoolId, 'created_at' => now(), 'updated_at' => now()]);
        $admin = $this->makeAdminUser($otherSchoolId);

        $response = $this->actingAs($admin)->get(route('admin.hei_admissions.index'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_non_primary_schools_admin_is_blocked_from_intake_sessions_and_agents_too(): void
    {
        $primarySchoolId = $this->makeSchool();
        $otherSchoolId = $this->makeSchool();
        DB::table('global_settings')->insert(['key' => 'primary_school_id', 'value' => (string) $primarySchoolId, 'created_at' => now(), 'updated_at' => now()]);
        $admin = $this->makeAdminUser($otherSchoolId);

        $this->actingAs($admin)->get(route('admin.intake_sessions.index'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($admin)->get(route('admin.admissions_agents.index'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_missing_primary_school_setting_fails_open_rather_than_locking_everyone_out(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        // No primary_school_id row inserted at all.

        $response = $this->actingAs($admin)->get(route('admin.hei_admissions.index'));

        $response->assertStatus(200);
    }
}
