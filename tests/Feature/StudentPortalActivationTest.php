<?php

namespace Tests\Feature;

use App\Mail\StudentPortalActivationEmail;
use App\Models\AuditLog;
use App\Models\StudentFeeManager;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class StudentPortalActivationTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_rejected_application_does_not_create_a_student_account(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $admissionId = $this->makeAdmission($schoolId, [
            'email' => 'rejected.applicant@example.com',
            'status' => 'under_review',
        ]);

        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'rejected']);

        $this->assertNull(User::where('email', 'rejected.applicant@example.com')->first(), 'Rejected applications must never create a student account.');
    }

    public function test_accepted_but_not_enrolled_application_does_not_create_a_student_account(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $admissionId = $this->makeAdmission($schoolId, [
            'email' => 'accepted.only@example.com',
            'status' => 'under_review',
        ]);

        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'accepted']);

        $this->assertNull(User::where('email', 'accepted.only@example.com')->first(), 'Acceptance alone (without enrollment) must not create a portal account.');
    }

    public function test_enrolling_creates_one_forced_password_change_student_and_sends_activation_email(): void
    {
        Mail::fake();
        $schoolId = $this->makeSchool();
        $this->enableSmtpSettings();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId, ['name' => 'BSc Computer Science']);
        $intakeId = $this->makeIntakeSession($schoolId, ['name' => 'September 2026 Intake']);
        $admissionId = $this->makeAdmission($schoolId, [
            'programme_id' => $programmeId,
            'intake_session_id' => $intakeId,
            'email' => 'activate.me@example.com',
            'status' => 'accepted',
        ]);

        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'enrolled']);

        $this->assertSame(1, User::where('email', 'activate.me@example.com')->count(), 'Exactly one student account must be created.');

        $student = User::where('email', 'activate.me@example.com')->first();
        $this->assertSame(7, (int) $student->role_id);
        $this->assertTrue((bool) $student->force_password_change, 'A newly converted student must be forced to change their password.');

        Mail::assertSent(StudentPortalActivationEmail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email)
                && $mail->data['programme'] === 'BSc Computer Science'
                && $mail->data['intake'] === 'September 2026 Intake'
                && ! empty($mail->data['password']);
        });
        Mail::assertSent(StudentPortalActivationEmail::class, 1);
    }

    public function test_temporary_password_is_never_stored_in_plaintext(): void
    {
        Mail::fake();
        $schoolId = $this->makeSchool();
        $this->enableSmtpSettings();
        $admin = $this->makeAdminUser($schoolId);
        $admissionId = $this->makeAdmission($schoolId, [
            'email' => 'hash.check@example.com',
            'status' => 'accepted',
        ]);

        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'enrolled']);

        $student = User::where('email', 'hash.check@example.com')->first();

        $sentPassword = null;
        Mail::assertSent(StudentPortalActivationEmail::class, function ($mail) use (&$sentPassword) {
            $sentPassword = $mail->data['password'];
            return true;
        });

        $this->assertNotSame($sentPassword, $student->password, 'The password column must never equal the plaintext temporary password.');
        $this->assertTrue(Hash::check($sentPassword, $student->password), 'The stored hash must match the temporary password that was emailed.');
    }

    public function test_student_with_forced_password_change_is_redirected_away_from_dashboard(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create([
            'role_id' => 7,
            'school_id' => $schoolId,
            'account_status' => 'active',
            'force_password_change' => true,
        ]);

        $response = $this->actingAs($student)->get(route('student.dashboard'));

        $response->assertRedirect(route('student.password', 'edit'));
    }

    public function test_password_change_clears_flag_and_unblocks_the_middleware_gate(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create([
            'role_id' => 7,
            'school_id' => $schoolId,
            'account_status' => 'active',
            'force_password_change' => true,
            'password' => Hash::make('TempPass123'),
        ]);

        $updateResponse = $this->actingAs($student)->post(route('student.password', 'update'), [
            'old_password' => 'TempPass123',
            'new_password' => 'MyNewSecurePass1',
            'confirm_password' => 'MyNewSecurePass1',
        ]);
        $updateResponse->assertRedirect(route('student.password', 'edit'));

        $student->refresh();
        $this->assertFalse((bool) $student->force_password_change, 'force_password_change must be cleared after a successful password change.');

        // Verify the middleware itself now lets the request through, without
        // depending on rendering the real (heavily-widgeted) dashboard view —
        // that view's unrelated data dependencies aren't this feature's concern.
        $this->actingAs($student);
        $dashboardRoute = (new \Illuminate\Routing\Route('GET', 'student/dashboard', []))->name('student.dashboard');
        $dashboardRequest = \Illuminate\Http\Request::create('/student/dashboard', 'GET');
        $dashboardRequest->setRouteResolver(fn () => $dashboardRoute);

        $response = (new \App\Http\Middleware\StudentMiddleware())->handle($dashboardRequest, fn ($req) => new \Illuminate\Http\Response('dashboard-reached'));

        $this->assertSame('dashboard-reached', $response->getContent(), 'Once force_password_change is cleared, the middleware must let the request through to the dashboard.');
    }

    public function test_resending_activation_does_not_duplicate_user_profile_or_invoice(): void
    {
        Mail::fake();
        $schoolId = $this->makeSchool();
        $this->enableSmtpSettings();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $this->makeFeeStructure($schoolId, ['programme_id' => $programmeId, 'amount' => 500]);
        $admissionId = $this->makeAdmission($schoolId, [
            'programme_id' => $programmeId,
            'email' => 'resend.me@example.com',
            'status' => 'accepted',
        ]);

        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'enrolled']);
        $student = User::where('email', 'resend.me@example.com')->first();
        $originalHash = $student->password;

        // student completes activation first, so we can prove resend re-forces it
        $student->update(['force_password_change' => false]);

        $this->actingAs($admin)->get(route('admin.student.resend_activation', $student->id));

        $this->assertSame(1, User::where('email', 'resend.me@example.com')->count(), 'Resend must not create a duplicate user.');
        $this->assertSame(1, StudentProfile::where('user_id', $student->id)->count(), 'Resend must not create a duplicate profile.');
        $this->assertSame(1, StudentFeeManager::where('student_id', $student->id)->count(), 'Resend must not create a duplicate invoice.');

        $student->refresh();
        $this->assertTrue((bool) $student->force_password_change, 'Resend must force a password change again.');
        $this->assertNotSame($originalHash, $student->password, 'Resend must issue a new temporary password.');

        Mail::assertSent(StudentPortalActivationEmail::class, 2); // initial activation + resend
    }

    public function test_audit_logs_never_contain_the_plaintext_password(): void
    {
        Mail::fake();
        $schoolId = $this->makeSchool();
        $this->enableSmtpSettings();
        $admin = $this->makeAdminUser($schoolId);
        $admissionId = $this->makeAdmission($schoolId, [
            'email' => 'audit.safe@example.com',
            'status' => 'accepted',
        ]);

        $this->actingAs($admin)->post(route('admin.hei_admissions.status', $admissionId), ['status' => 'enrolled']);

        $student = User::where('email', 'audit.safe@example.com')->first();

        $sentPassword = null;
        Mail::assertSent(StudentPortalActivationEmail::class, function ($mail) use (&$sentPassword) {
            $sentPassword = $mail->data['password'];
            return true;
        });

        $logs = AuditLog::where('school_id', $schoolId)->get();
        $this->assertGreaterThan(0, $logs->count());

        foreach ($logs as $log) {
            $this->assertStringNotContainsString($sentPassword, (string) $log->description, 'Audit description must never contain the temporary password.');
            $this->assertStringNotContainsString($sentPassword, json_encode($log->old_values), 'old_values must never contain the temporary password.');
            $this->assertStringNotContainsString($sentPassword, json_encode($log->new_values), 'new_values must never contain the temporary password.');
        }
    }

    public function test_school_admin_cannot_resend_activation_for_another_schools_student(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminA = $this->makeAdminUser($schoolA);

        $studentInSchoolB = User::factory()->create([
            'role_id' => 7,
            'school_id' => $schoolB,
            'account_status' => 'active',
        ]);

        $response = $this->actingAs($adminA)->get(route('admin.student.resend_activation', $studentInSchoolB->id));

        $response->assertStatus(404);
    }
}
