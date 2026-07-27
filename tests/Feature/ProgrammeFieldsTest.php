<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the client-requested Level/Mode/Duration field spec on Programme:
 * both the client's preferred option lists and every pre-existing legacy
 * value must remain valid, and a Programme with related records can no
 * longer be hard-deleted out from under Courses/Admissions/Students.
 */
class ProgrammeFieldsTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code'   => 'BBA',
            'name'   => 'Bachelor of Business Administration',
            'level'  => 'Bachelors',
            'mode'   => 'ODEL',
            'duration' => '3 Years',
        ], $overrides);
    }

    public function test_new_client_preferred_level_and_mode_values_are_accepted(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.programmes.store'), $this->payload());

        $response->assertSessionDoesntHaveErrors(['level', 'mode']);
        $this->assertDatabaseHas('programmes', ['code' => 'BBA', 'level' => 'Bachelors', 'mode' => 'ODEL']);
    }

    public function test_legacy_level_and_mode_values_remain_valid(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.programmes.store'), $this->payload([
            'code' => 'MPAM',
            'level' => 'Degree',
            'mode' => 'fulltime',
        ]));

        $response->assertSessionDoesntHaveErrors(['level', 'mode']);
        $this->assertDatabaseHas('programmes', ['code' => 'MPAM', 'level' => 'Degree', 'mode' => 'fulltime']);
    }

    public function test_garbage_level_and_mode_values_are_rejected(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.programmes.store'), $this->payload([
            'level' => 'Not A Real Level',
            'mode' => 'Not A Real Mode',
        ]));

        $response->assertSessionHasErrors(['level', 'mode']);
    }

    public function test_destroy_is_blocked_when_programme_has_related_students(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $student = $this->makeAdminUser($schoolId); // any user row works as the FK target
        $this->makeStudentProfile($schoolId, $student->id, $programmeId);

        $response = $this->actingAs($admin)->get(route('admin.programmes.destroy', $programmeId));

        $response->assertRedirect();
        $this->assertDatabaseHas('programmes', ['id' => $programmeId]);
    }

    public function test_destroy_succeeds_when_programme_has_no_related_records(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $response = $this->actingAs($admin)->get(route('admin.programmes.destroy', $programmeId));

        $response->assertRedirect();
        $this->assertDatabaseMissing('programmes', ['id' => $programmeId]);
    }

    public function test_export_endpoints_render_successfully(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $this->makeProgramme($schoolId);

        $this->actingAs($admin)->get(route('admin.programmes.export'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.programmes.export_excel'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.programmes.print', ['inline' => 1]))->assertStatus(200);
    }
}
