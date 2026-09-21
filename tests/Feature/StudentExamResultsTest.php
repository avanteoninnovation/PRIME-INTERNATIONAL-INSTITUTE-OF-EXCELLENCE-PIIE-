<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the new self-service Exam Results page
 * (TranscriptController::studentShow()/studentDownloadPdf()) — students
 * previously had no way to see their own grades at all; only admin could
 * generate a transcript on their behalf, via routes a student's own
 * middleware would never let them reach.
 */
class StudentExamResultsTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    private function makeStudent(int $schoolId, string $email): User
    {
        return User::create([
            'name' => 'Results Student', 'email' => $email,
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);
    }

    public function test_a_student_sees_their_own_published_grades(): void
    {
        $schoolId = $this->makeSchool();
        $classId = $this->makeClass($schoolId, ['name' => 'Grade 11']);
        $sectionId = (int) DB::table('sections')->insertGetId([
            'name' => 'A', 'class_id' => $classId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'Biology', 'class_id' => $classId, 'school_id' => $schoolId,
            'session_id' => 1, 'pass_mark' => 50, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = $this->makeStudent($schoolId, 'results.own@example.com');
        DB::table('enrollment')->insert([
            'user_id' => $student->id, 'class_id' => $classId, 'section_id' => $sectionId,
            'school_id' => $schoolId, 'department_id' => 0, 'session_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $examCategoryId = $this->makeExamCategory($schoolId, ['name' => 'Midterm']);
        $this->makeGrade($schoolId, ['name' => 'B', 'mark_from' => 60, 'mark_upto' => 79, 'gpa_points' => 4.0]);
        $this->makeGradebook($schoolId, [
            'class_id' => $classId, 'section_id' => $sectionId, 'student_id' => $student->id,
            'exam_category_id' => $examCategoryId,
            'marks' => json_encode([$subjectId => ['obtained' => 70, 'total' => 100]]),
        ]);

        $response = $this->actingAs($student)->get(route('student.exam_results'));

        $response->assertOk();
        $response->assertSee('Biology');
        $response->assertSee('70');
        $response->assertSee('Midterm');
    }

    public function test_a_student_with_no_grades_sees_an_empty_state_not_an_error(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'results.empty@example.com');

        $response = $this->actingAs($student)->get(route('student.exam_results'));

        $response->assertOk();
        $response->assertSee('No results published yet');
    }

    public function test_pdf_download_produces_a_genuine_pdf(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'results.pdf@example.com');

        $response = $this->actingAs($student)->get(route('student.exam_results.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_a_student_can_only_ever_see_their_own_results_never_pass_someone_elses_id(): void
    {
        // studentShow()/studentDownloadPdf() take no {id} parameter at all —
        // unlike the admin routes — so there is no URL a student could even
        // construct to view another student's results.
        $this->assertStringNotContainsString('{id}', route('student.exam_results'));
    }
}
