<?php

namespace Tests\Feature;

use App\Models\StudentFeeManager;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class AdmissionConversionTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_enrolling_an_admission_creates_a_student_with_the_correct_role_and_registration_number(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $intakeId = $this->makeIntakeSession($schoolId);
        $admissionId = $this->makeAdmission($schoolId, [
            'programme_id' => $programmeId,
            'intake_session_id' => $intakeId,
            'email' => 'convert.me@example.com',
            'status' => 'accepted',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'enrolled']);

        $response->assertStatus(302);

        $student = User::where('email', 'convert.me@example.com')->first();
        $this->assertNotNull($student, 'Admission conversion should create a User account.');
        $this->assertSame(7, (int) $student->role_id, 'Converted student must use the canonical student role_id (7), not 4.');
        $this->assertNotEmpty($student->code, 'A registration number (users.code) must be assigned.');

        $profile = StudentProfile::where('user_id', $student->id)->first();
        $this->assertNotNull($profile);
        $this->assertSame($programmeId, $profile->programme_id);
        $this->assertSame($intakeId, $profile->intake_session_id);
    }

    public function test_retrying_conversion_does_not_create_duplicate_accounts_profiles_or_invoices(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $this->makeFeeStructure($schoolId, ['programme_id' => $programmeId, 'amount' => 500]);
        $admissionId = $this->makeAdmission($schoolId, [
            'programme_id' => $programmeId,
            'email' => 'retry.me@example.com',
            'status' => 'accepted',
        ]);

        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'enrolled']);

        // Simulate a retried/duplicated request by moving the status back to
        // accepted and re-triggering the enrolled transition — this bypasses
        // the controller's oldStatus guard so we test createStudentFromAdmission's
        // OWN idempotency, not just the outer guard.
        DB::table('admissions')->where('id', $admissionId)->update(['status' => 'accepted']);
        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'enrolled']);

        $this->assertSame(1, User::where('email', 'retry.me@example.com')->count(), 'Must not create duplicate student accounts.');

        $student = User::where('email', 'retry.me@example.com')->first();
        $this->assertSame(1, StudentProfile::where('user_id', $student->id)->count(), 'Must not create duplicate student profiles.');
        $this->assertSame(1, StudentFeeManager::where('student_id', $student->id)->count(), 'Must not create duplicate fee invoices.');
    }

    public function test_registration_numbers_generated_in_bulk_are_unique(): void
    {
        $codes = [];
        for ($i = 0; $i < 25; $i++) {
            $code = student_code();
            $this->assertNotContains($code, $codes, 'student_code() must never repeat a registration number.');
            $codes[] = $code;

            User::create([
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => bcrypt('secret'),
                'code' => $code,
                'role_id' => 7,
                'school_id' => 1,
            ]);
        }
    }
}
