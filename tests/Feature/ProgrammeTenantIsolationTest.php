<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the fix for ProgrammeController::openModal() — it looked up a
 * programme by id with no school_id scope, letting one school's admin view
 * (though not edit/delete, which were already scoped) another school's
 * programme details via a direct request.
 */
class ProgrammeTenantIsolationTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('school_id');
            $table->timestamps();
        });
    }

    public function test_admin_cannot_open_another_schools_programme_edit_modal(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminB = $this->makeAdminUser($schoolB);

        $programmeInA = $this->makeProgramme($schoolA, ['name' => 'Confidential School A Programme']);

        $response = $this->actingAs($adminB)->get(route('admin.programmes.open_modal', ['id' => $programmeInA]));

        $response->assertStatus(404);
    }

    public function test_admin_can_still_open_their_own_schools_programme_edit_modal(): void
    {
        $schoolA = $this->makeSchool();
        $adminA = $this->makeAdminUser($schoolA);

        $programme = $this->makeProgramme($schoolA, ['name' => 'My Own Programme']);

        $response = $this->actingAs($adminA)->get(route('admin.programmes.open_modal', ['id' => $programme]));

        $response->assertStatus(200);
        $response->assertSee('My Own Programme');
    }
}
