<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Mirrors ProgrammeTenantIsolationTest for the new Course module — every
 * CourseController query is manually scoped by school_id (no global scope
 * enforces this), so this pins down that discipline the same way the
 * Programme test does.
 */
class CourseTenantIsolationTest extends TestCase
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

    public function test_admin_cannot_open_another_schools_course_edit_modal(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminB = $this->makeAdminUser($schoolB);

        $programmeInA = $this->makeProgramme($schoolA);
        $courseInA = $this->makeCourse($schoolA, $programmeInA, ['name' => 'Confidential School A Course']);

        $response = $this->actingAs($adminB)->get(route('admin.courses.open_modal', ['id' => $courseInA]));

        $response->assertStatus(404);
    }

    public function test_admin_can_still_open_their_own_schools_course_edit_modal(): void
    {
        $schoolA = $this->makeSchool();
        $adminA = $this->makeAdminUser($schoolA);

        $programme = $this->makeProgramme($schoolA);
        $courseId = $this->makeCourse($schoolA, $programme, ['name' => 'My Own Course']);

        $response = $this->actingAs($adminA)->get(route('admin.courses.open_modal', ['id' => $courseId]));

        $response->assertStatus(200);
        $response->assertSee('My Own Course');
    }

    public function test_admin_cannot_destroy_another_schools_course(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminB = $this->makeAdminUser($schoolB);

        $programmeInA = $this->makeProgramme($schoolA);
        $courseInA = $this->makeCourse($schoolA, $programmeInA);

        $response = $this->actingAs($adminB)->get(route('admin.courses.destroy', $courseInA));

        $response->assertStatus(404);
        $this->assertDatabaseHas('subjects', ['id' => $courseInA]);
    }

    public function test_k12_subject_rows_are_not_listed_as_courses(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        // A plain K-12 subject: class_id set, no programme_id — must never
        // surface in the Course list/CRUD, which only reads programme-linked rows.
        \Illuminate\Support\Facades\DB::table('subjects')->insert([
            'name' => 'K12 Math', 'class_id' => 1, 'school_id' => $schoolId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.courses.index'));

        $response->assertStatus(200);
        $response->assertDontSee('K12 Math');
    }
}
