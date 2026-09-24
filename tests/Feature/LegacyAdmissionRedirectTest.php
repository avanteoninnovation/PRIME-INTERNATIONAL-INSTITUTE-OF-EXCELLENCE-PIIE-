<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Batch 6 — the old "Admin → Students → Admission → Single Student
 * Admission" one-page form is retired in favour of the staff-entry wizard.
 * Its route/name stays so existing bookmarks and the "Create Student"
 * button (admin/student/student_list.blade.php) keep working; it now
 * redirects instead of rendering the old form. Bulk and Excel admission
 * — reached through the same shell page via a different {type} — are
 * untouched.
 */
class LegacyAdmissionRedirectTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    private function configurePrimarySchool(int $schoolId): void
    {
        DB::table('global_settings')->insert([
            'key' => 'primary_school_id',
            'value' => (string) $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_single_student_admission_route_redirects_into_the_wizard(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->get(route('admin.offline_admission.single', ['type' => 'single']));

        $response->assertRedirect(route('admin.hei_admissions.wizard.create'));
    }

    public function test_bare_offline_admission_route_also_redirects_into_the_wizard(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);
        $admin = $this->makeAdminUser($schoolId);

        // Two-hop: the bare route redirects to ?type=single, which itself
        // now redirects into the wizard.
        $response = $this->actingAs($admin)->get('/admin/offline_admission');

        $response->assertRedirect(route('admin.offline_admission.single', ['type' => 'single']));

        $second = $this->actingAs($admin)->get(route('admin.offline_admission.single', ['type' => 'single']));
        $second->assertRedirect(route('admin.hei_admissions.wizard.create'));
    }

    public function test_excel_upload_tab_still_renders_the_original_page(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->get(route('admin.offline_admission.single', ['type' => 'excel']));

        $response->assertStatus(200);
        $response->assertSee('Excel upload', false);
    }

    public function test_create_student_button_on_student_list_now_lands_in_the_wizard(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);
        $admin = $this->makeAdminUser($schoolId);

        // The button's href is route('admin.offline_admission.single', ['type' => 'single']) —
        // confirmed by resources/views/admin/student/student_list.blade.php.
        // This test exercises that exact route to prove the button's
        // destination now goes to the wizard without needing to touch the
        // student list view itself.
        $response = $this->actingAs($admin)->get(route('admin.offline_admission.single', ['type' => 'single']));

        $response->assertRedirect(route('admin.hei_admissions.wizard.create'));
    }
}
