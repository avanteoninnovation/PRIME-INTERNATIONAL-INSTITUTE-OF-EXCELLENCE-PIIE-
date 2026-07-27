<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class AdminLayoutSmokeTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_admissions_index_renders_via_admin_navigation_layout(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->get(route('admin.hei_admissions.index'));

        $response->assertStatus(200);
        $response->assertSee('admin-sidebar.css', false);
    }
}
