<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\EnrollmentDefaults;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers EnrollmentDefaults::ensureRow() — added because Programme-track
 * students (AdmissionsController::createStudentFromAdmission(),
 * AdminController::studentCreate()) never got an Enrollment row at all,
 * which is what every teacher-facing roster actually queries. See the
 * class docblock on EnrollmentDefaults for the full "why".
 */
class EnrollmentDefaultsTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_creates_an_enrollment_row_with_the_class_sentinel_when_none_exists(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::create([
            'name' => 'Programme Student', 'email' => 'programme.student@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        EnrollmentDefaults::ensureRow($student->id, $schoolId);

        $this->assertDatabaseHas('enrollment', [
            'user_id' => $student->id,
            'school_id' => $schoolId,
            'class_id' => 0,
            'section_id' => 0,
        ]);
    }

    public function test_does_not_duplicate_an_existing_enrollment_row(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::create([
            'name' => 'Programme Student', 'email' => 'programme.student2@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);
        $classId = $this->makeClass($schoolId);
        DB::table('enrollment')->insert([
            'user_id' => $student->id,
            'class_id' => $classId,
            'section_id' => 1,
            'school_id' => $schoolId,
            'department_id' => 0,
            'session_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        EnrollmentDefaults::ensureRow($student->id, $schoolId);

        $this->assertSame(1, DB::table('enrollment')->where('user_id', $student->id)->count());
        // The real class assignment must survive — this must never overwrite
        // an existing, more specific Enrollment row with the 0 sentinel.
        $this->assertDatabaseHas('enrollment', [
            'user_id' => $student->id,
            'class_id' => $classId,
        ]);
    }
}
