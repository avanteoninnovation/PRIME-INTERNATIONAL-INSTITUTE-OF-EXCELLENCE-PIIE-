<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the dedicated CAT/EXAM marks screen for Programme-linked Courses:
 * ceilings from the Course's cats_marks/exam_marks are enforced, the total
 * and pass/fail are computed against pass_mark, and none of this touches
 * the pre-existing K-12 single-mark Gradebook path (AdminController::
 * markAdd / CommonController::markUpdate), which stores a bare scalar.
 */
class CourseMarksTest extends TestCase
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

    private function makeStudentUser(int $schoolId): \App\Models\User
    {
        return \App\Models\User::factory()->create(['role_id' => 7, 'school_id' => $schoolId, 'account_status' => 'active']);
    }

    public function test_marks_within_ceilings_are_saved_with_computed_total_and_pass_fail(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $courseId = $this->makeCourse($schoolId, $programmeId, ['cats_marks' => 30, 'exam_marks' => 70, 'pass_mark' => 50]);
        $student = $this->makeStudentUser($schoolId);
        $this->makeStudentProfile($schoolId, $student->id, $programmeId);

        $response = $this->actingAs($admin)->post(route('admin.courses.marks.update', $courseId), [
            'cats' => [$student->id => 25],
            'exam' => [$student->id => 58],
        ]);

        $response->assertRedirect();
        $gradebook = DB::table('gradebooks')->where('student_id', $student->id)->where('school_id', $schoolId)->first();
        $this->assertNotNull($gradebook);
        $marks = json_decode($gradebook->marks, true);
        $this->assertSame(25, $marks[$courseId]['cats']);
        $this->assertSame(58, $marks[$courseId]['exam']);
        $this->assertSame(83, $marks[$courseId]['obtained']);
        $this->assertSame(100, $marks[$courseId]['total']);
    }

    public function test_cat_mark_exceeding_ceiling_is_rejected(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $courseId = $this->makeCourse($schoolId, $programmeId, ['cats_marks' => 30, 'exam_marks' => 70]);
        $student = $this->makeStudentUser($schoolId);
        $this->makeStudentProfile($schoolId, $student->id, $programmeId);

        $response = $this->actingAs($admin)->post(route('admin.courses.marks.update', $courseId), [
            'cats' => [$student->id => 35],
            'exam' => [$student->id => 50],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseMissing('gradebooks', ['student_id' => $student->id]);
    }

    public function test_marks_page_shows_pass_and_fail_correctly(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $courseId = $this->makeCourse($schoolId, $programmeId, ['cats_marks' => 30, 'exam_marks' => 70, 'pass_mark' => 50]);

        $passingStudent = $this->makeStudentUser($schoolId);
        $this->makeStudentProfile($schoolId, $passingStudent->id, $programmeId);
        $failingStudent = $this->makeStudentUser($schoolId);
        $this->makeStudentProfile($schoolId, $failingStudent->id, $programmeId);

        $this->actingAs($admin)->post(route('admin.courses.marks.update', $courseId), [
            'cats' => [$passingStudent->id => 25, $failingStudent->id => 10],
            'exam' => [$passingStudent->id => 58, $failingStudent->id => 20],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.courses.marks', $courseId));

        $response->assertStatus(200);
        $response->assertSeeText('Pass');
        $response->assertSeeText('Fail');
    }

    public function test_saving_course_marks_does_not_affect_k12_gradebook_scalar_marks(): void
    {
        // A K-12 gradebook row (bare scalar mark for a class-based exam
        // category) must be untouched by the new Course-marks screen, which
        // only ever writes/reads its own school+session+null-class rows.
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $courseId = $this->makeCourse($schoolId, $programmeId);
        $k12Student = $this->makeStudentUser($schoolId);

        DB::table('gradebooks')->insert([
            'class_id' => 1, 'section_id' => 1, 'student_id' => $k12Student->id,
            'exam_category_id' => 1, 'marks' => json_encode([5 => 78]),
            'school_id' => $schoolId, 'session_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $hEIStudent = $this->makeStudentUser($schoolId);
        $this->makeStudentProfile($schoolId, $hEIStudent->id, $programmeId);
        $this->actingAs($admin)->post(route('admin.courses.marks.update', $courseId), [
            'cats' => [$hEIStudent->id => 20],
            'exam' => [$hEIStudent->id => 60],
        ]);

        $k12Row = DB::table('gradebooks')->where('student_id', $k12Student->id)->first();
        $this->assertSame(78, json_decode($k12Row->marks, true)[5]);
    }
}
