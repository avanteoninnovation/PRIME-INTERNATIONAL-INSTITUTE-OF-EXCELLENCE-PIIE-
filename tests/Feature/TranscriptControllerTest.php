<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers TranscriptController — a pre-existing but previously untested
 * feature. Extended this session to also surface Class/Section (class-track
 * students) and Year of Study (programme-track students) on the transcript,
 * alongside the fixes that make a student's class assignment and academic
 * record actually reach this point: EnrollmentDefaults::ensureRow() (every
 * student gets an Enrollment row) and AdminController::studentUpdate()'s
 * Enrollment::updateOrCreate() (a class picked on the edit form actually
 * persists).
 */
class TranscriptControllerTest extends TestCase
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
            'name' => 'Transcript Student', 'email' => $email,
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);
    }

    public function test_class_track_student_transcript_shows_class_section_and_marks(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $classId = $this->makeClass($schoolId, ['name' => 'Year 3 IT']);
        $sectionId = (int) DB::table('sections')->insertGetId([
            'name' => 'A', 'class_id' => $classId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $subjectId = (int) DB::table('subjects')->insertGetId([
            'name' => 'Networking', 'class_id' => $classId, 'school_id' => $schoolId,
            'session_id' => 1, 'pass_mark' => 50, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = $this->makeStudent($schoolId, 'class.track@example.com');
        DB::table('enrollment')->insert([
            'user_id' => $student->id, 'class_id' => $classId, 'section_id' => $sectionId,
            'school_id' => $schoolId, 'department_id' => 0, 'session_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $examCategoryId = $this->makeExamCategory($schoolId, ['name' => 'Final Exam']);
        $this->makeGrade($schoolId, ['name' => 'A', 'mark_from' => 80, 'mark_upto' => 100, 'gpa_points' => 5.0]);
        $this->makeGradebook($schoolId, [
            'class_id' => $classId, 'section_id' => $sectionId, 'student_id' => $student->id,
            'exam_category_id' => $examCategoryId,
            'marks' => json_encode([$subjectId => ['obtained' => 85, 'total' => 100]]),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transcripts.show', $student->id));

        $response->assertOk();
        $response->assertSee('Year 3 IT');
        $response->assertSee('Networking');
        $response->assertSee('85');
        $response->assertSee('A'); // grade letter
    }

    public function test_programme_track_student_transcript_shows_programme_and_year_of_study(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId, ['name' => 'Bachelor of IT']);
        $student = $this->makeStudent($schoolId, 'programme.track@example.com');
        DB::table('student_profiles')->insert([
            'user_id' => $student->id, 'school_id' => $schoolId, 'programme_id' => $programmeId,
            'year_of_study' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);
        // Given today's EnrollmentDefaults fix, this student also has an
        // Enrollment row, but with the "no class assigned" sentinel.
        DB::table('enrollment')->insert([
            'user_id' => $student->id, 'class_id' => 0, 'section_id' => 0,
            'school_id' => $schoolId, 'department_id' => 0, 'session_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.transcripts.show', $student->id));

        $response->assertOk();
        $response->assertSee('Bachelor of IT');
        $response->assertSee('2'); // year of study
    }

    public function test_transcript_shows_empty_state_when_no_records_exist(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $student = $this->makeStudent($schoolId, 'no.records@example.com');

        $response = $this->actingAs($admin)->get(route('admin.transcripts.show', $student->id));

        $response->assertOk();
        $response->assertSee('No academic records found for this student');
    }

    public function test_a_student_from_another_school_is_not_reachable(): void
    {
        $schoolId = $this->makeSchool();
        $otherSchoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $otherStudent = $this->makeStudent($otherSchoolId, 'other.school@example.com');

        $this->actingAs($admin)->get(route('admin.transcripts.show', $otherStudent->id))->assertStatus(404);
    }

    public function test_pdf_download_produces_a_genuine_pdf(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $student = $this->makeStudent($schoolId, 'pdf.download@example.com');

        $response = $this->actingAs($admin)->get(route('admin.transcripts.pdf', $student->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
