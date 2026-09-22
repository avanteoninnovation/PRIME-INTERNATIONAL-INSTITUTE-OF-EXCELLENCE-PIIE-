<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\FeeStructure;
use App\Models\StudentFeeManager;
use App\Models\StudentProfile;
use App\Models\User;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the new "Sync Invoices" bulk action — backfills StudentFeeManager
 * rows for students who never got any, either because they were admitted
 * before a FeeStructure existed, or added through a path that skips
 * StudentFeeInvoiceGenerator's create-time auto-invoicing. Reuses that same
 * idempotent generator, so this must never double-invoice a student who
 * already has one of the mandatory fees.
 */
class FeeInvoiceSyncTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    private function makeAccountant(int $schoolId): User
    {
        return User::create([
            'name' => 'Finance Officer', 'email' => 'sync.accountant.' . uniqid() . '@example.com',
            'password' => bcrypt('secret'), 'role_id' => 4, 'school_id' => $schoolId,
            'account_status' => 'active',
        ]);
    }

    private function makeStudent(int $schoolId, string $email): User
    {
        return User::create([
            'name' => 'Sync Student', 'email' => $email,
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);
    }

    public function test_sync_generates_class_based_invoices_for_enrolled_students_with_none(): void
    {
        $schoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);
        $classId = $this->makeClass($schoolId);
        $student = $this->makeStudent($schoolId, 'classsync@example.com');

        Enrollment::create(['user_id' => $student->id, 'class_id' => $classId, 'section_id' => 0, 'school_id' => $schoolId, 'session_id' => 1]);
        FeeStructure::create([
            'school_id' => $schoolId, 'name' => 'Tuition', 'fee_type' => 'tuition', 'amount' => 500,
            'is_mandatory' => 1, 'per_semester' => 1, 'class_id' => $classId,
        ]);

        $response = $this->actingAs($accountant)->post(route('accountant.fee_manager.sync.generate'), [
            'target_type' => 'class',
            'class_id' => $classId,
        ]);

        $response->assertRedirect(route('accountant.fee_manager.sync'));
        $this->assertDatabaseHas('student_fee_managers', [
            'student_id' => $student->id, 'class_id' => $classId, 'title' => 'Tuition', 'total_amount' => 500,
        ]);
    }

    public function test_sync_generates_programme_based_invoices_for_students_with_none(): void
    {
        $schoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $student = $this->makeStudent($schoolId, 'programmesync@example.com');

        StudentProfile::create(['user_id' => $student->id, 'school_id' => $schoolId, 'programme_id' => $programmeId]);
        FeeStructure::create([
            'school_id' => $schoolId, 'name' => 'Programme Fee', 'fee_type' => 'tuition', 'amount' => 800,
            'is_mandatory' => 1, 'per_semester' => 1, 'programme_id' => $programmeId,
        ]);

        $response = $this->actingAs($accountant)->post(route('accountant.fee_manager.sync.generate'), [
            'target_type' => 'programme',
            'programme_id' => $programmeId,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('student_fee_managers', [
            'student_id' => $student->id, 'programme_id' => $programmeId, 'title' => 'Programme Fee', 'total_amount' => 800,
        ]);
    }

    public function test_sync_is_idempotent_and_never_double_invoices(): void
    {
        $schoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);
        $classId = $this->makeClass($schoolId);
        $student = $this->makeStudent($schoolId, 'idempotent@example.com');

        Enrollment::create(['user_id' => $student->id, 'class_id' => $classId, 'section_id' => 0, 'school_id' => $schoolId, 'session_id' => 1]);
        FeeStructure::create([
            'school_id' => $schoolId, 'name' => 'Tuition', 'fee_type' => 'tuition', 'amount' => 500,
            'is_mandatory' => 1, 'per_semester' => 1, 'class_id' => $classId,
        ]);

        $this->actingAs($accountant)->post(route('accountant.fee_manager.sync.generate'), ['target_type' => 'class', 'class_id' => $classId]);
        $this->actingAs($accountant)->post(route('accountant.fee_manager.sync.generate'), ['target_type' => 'class', 'class_id' => $classId]);

        $this->assertSame(1, StudentFeeManager::where('student_id', $student->id)->count());
    }

    public function test_sync_does_not_recreate_an_invoice_a_student_already_has(): void
    {
        $schoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);
        $classId = $this->makeClass($schoolId);
        $student = $this->makeStudent($schoolId, 'alreadyinvoiced@example.com');

        Enrollment::create(['user_id' => $student->id, 'class_id' => $classId, 'section_id' => 0, 'school_id' => $schoolId, 'session_id' => 1]);
        $feeStructure = FeeStructure::create([
            'school_id' => $schoolId, 'name' => 'Tuition', 'fee_type' => 'tuition', 'amount' => 500,
            'is_mandatory' => 1, 'per_semester' => 1, 'class_id' => $classId,
        ]);
        StudentFeeManager::create([
            'title' => 'Tuition', 'total_amount' => 500, 'amount' => 500, 'discounted_price' => 0,
            'class_id' => $classId, 'programme_id' => null, 'student_id' => $student->id, 'parent_id' => null,
            'fee_structure_id' => $feeStructure->id, 'payment_method' => 'unpaid', 'paid_amount' => 0,
            'status' => 'unpaid', 'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);

        $this->actingAs($accountant)->post(route('accountant.fee_manager.sync.generate'), ['target_type' => 'class', 'class_id' => $classId]);

        $this->assertSame(1, StudentFeeManager::where('student_id', $student->id)->count());
    }

    public function test_sync_requires_a_class_id_when_targeting_a_class(): void
    {
        $schoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);

        $this->actingAs($accountant)->post(route('accountant.fee_manager.sync.generate'), ['target_type' => 'class'])
            ->assertSessionHasErrors('class_id');
    }

    public function test_a_student_cannot_access_the_sync_tool(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'notallowed@example.com');

        $this->actingAs($student)->get(route('accountant.fee_manager.sync'))->assertStatus(302);
    }
}
