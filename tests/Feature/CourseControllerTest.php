<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the client's Max Marks Split model for Courses (Subject rows with
 * a programme_id): CATS + EXAM must sum to 100, Pass Mark must be 0-100,
 * and a Course with related Exams/Gradebook marks cannot be deleted.
 */
class CourseControllerTest extends TestCase
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

    private function payload(int $programmeId, array $overrides = []): array
    {
        return array_merge([
            'code'         => 'BBA 1102',
            'name'         => 'Communication Skills',
            'programme_id' => $programmeId,
            'credits'      => 3,
            'course_type'  => 'compulsory',
            'level'        => 'Bachelors',
            'cats_marks'   => 30,
            'exam_marks'   => 70,
            'pass_mark'    => 50,
        ], $overrides);
    }

    public function test_course_is_created_with_valid_marks_split(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), $this->payload($programmeId));

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('subjects', ['code' => 'BBA 1102', 'programme_id' => $programmeId, 'pass_mark' => 50]);
    }

    public function test_cats_and_exam_marks_must_sum_to_100(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), $this->payload($programmeId, [
            'cats_marks' => 40,
            'exam_marks' => 70,
        ]));

        $response->assertSessionHasErrors(['cats_marks']);
        $this->assertDatabaseMissing('subjects', ['code' => 'BBA 1102']);
    }

    public function test_pass_mark_must_be_within_0_and_100(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.courses.store'), $this->payload($programmeId, [
            'pass_mark' => 150,
        ]));

        $response->assertSessionHasErrors(['pass_mark']);
    }

    public function test_programme_id_must_belong_to_the_admins_school(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminA = $this->makeAdminUser($schoolA);
        $programmeInB = $this->makeProgramme($schoolB);

        $response = $this->actingAs($adminA)->post(route('admin.courses.store'), $this->payload($programmeInB));

        $response->assertSessionHasErrors(['programme_id']);
    }

    public function test_destroy_is_blocked_when_course_has_related_exam(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $courseId = $this->makeCourse($schoolId, $programmeId);

        \Illuminate\Support\Facades\DB::table('exams')->insert([
            'name' => 'CAT 1', 'subject_id' => $courseId, 'school_id' => $schoolId,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.courses.destroy', $courseId));

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['id' => $courseId]);
    }

    public function test_destroy_succeeds_when_course_has_no_related_records(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $courseId = $this->makeCourse($schoolId, $programmeId);

        $response = $this->actingAs($admin)->get(route('admin.courses.destroy', $courseId));

        $response->assertRedirect();
        $this->assertDatabaseMissing('subjects', ['id' => $courseId]);
    }

    public function test_export_endpoints_render_successfully(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $this->makeCourse($schoolId, $programmeId);

        $this->actingAs($admin)->get(route('admin.courses.export'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.courses.export_excel'))->assertStatus(200);
        $this->actingAs($admin)->get(route('admin.courses.print', ['inline' => 1]))->assertStatus(200);
    }
}
