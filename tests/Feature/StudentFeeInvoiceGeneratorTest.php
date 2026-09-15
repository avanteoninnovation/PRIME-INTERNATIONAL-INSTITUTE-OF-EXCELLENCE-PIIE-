<?php

namespace Tests\Feature;

use App\Models\StudentFeeManager;
use App\Models\User;
use App\Support\StudentFeeInvoiceGenerator;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class StudentFeeInvoiceGeneratorTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_generates_an_invoice_from_the_programmes_mandatory_fee_structure(): void
    {
        $schoolId = $this->makeSchool();
        $programmeId = $this->makeProgramme($schoolId);
        $this->makeFeeStructure($schoolId, ['programme_id' => $programmeId, 'amount' => 750, 'name' => 'Tuition']);
        $student = User::create([
            'name' => 'Fee Student', 'email' => 'fee.student@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        $created = StudentFeeInvoiceGenerator::generateForStudent($student, $programmeId, $schoolId);

        $this->assertCount(1, $created);
        $this->assertSame(1, StudentFeeManager::where('student_id', $student->id)->count());
        $this->assertEquals(750, StudentFeeManager::where('student_id', $student->id)->first()->amount);
    }

    public function test_is_idempotent_when_called_more_than_once(): void
    {
        $schoolId = $this->makeSchool();
        $programmeId = $this->makeProgramme($schoolId);
        $this->makeFeeStructure($schoolId, ['programme_id' => $programmeId]);
        $student = User::create([
            'name' => 'Fee Student', 'email' => 'fee.student2@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        StudentFeeInvoiceGenerator::generateForStudent($student, $programmeId, $schoolId);
        $second = StudentFeeInvoiceGenerator::generateForStudent($student, $programmeId, $schoolId);

        $this->assertCount(0, $second, 'A second call must not create additional invoices.');
        $this->assertSame(1, StudentFeeManager::where('student_id', $student->id)->count());
    }

    public function test_a_non_mandatory_fee_structure_from_another_programme_is_not_billed(): void
    {
        $schoolId = $this->makeSchool();
        $programmeId = $this->makeProgramme($schoolId);
        $otherProgrammeId = $this->makeProgramme($schoolId, ['name' => 'Other Programme']);
        $this->makeFeeStructure($schoolId, ['programme_id' => $otherProgrammeId, 'name' => 'Other Programme Fee']);
        $student = User::create([
            'name' => 'Fee Student', 'email' => 'fee.student3@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        $created = StudentFeeInvoiceGenerator::generateForStudent($student, $programmeId, $schoolId);

        $this->assertCount(0, $created);
    }

    // ── generateForClassBasedStudent() — the class/section-track counterpart ──

    public function test_generates_an_invoice_from_the_classs_mandatory_fee_structure(): void
    {
        $schoolId = $this->makeSchool();
        $classId = $this->makeClass($schoolId);
        $this->makeFeeStructure($schoolId, ['class_id' => $classId, 'amount' => 500, 'name' => 'Tuition']);
        $student = User::create([
            'name' => 'Class Fee Student', 'email' => 'class.fee.student@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        $created = StudentFeeInvoiceGenerator::generateForClassBasedStudent($student, $classId, $schoolId);

        $this->assertCount(1, $created);
        $invoice = StudentFeeManager::where('student_id', $student->id)->first();
        $this->assertEquals(500, $invoice->amount);
        $this->assertEquals($classId, $invoice->class_id);
        $this->assertNull($invoice->programme_id);
    }

    public function test_class_based_generation_is_idempotent(): void
    {
        $schoolId = $this->makeSchool();
        $classId = $this->makeClass($schoolId);
        $this->makeFeeStructure($schoolId, ['class_id' => $classId]);
        $student = User::create([
            'name' => 'Class Fee Student', 'email' => 'class.fee.student2@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        StudentFeeInvoiceGenerator::generateForClassBasedStudent($student, $classId, $schoolId);
        $second = StudentFeeInvoiceGenerator::generateForClassBasedStudent($student, $classId, $schoolId);

        $this->assertCount(0, $second);
        $this->assertSame(1, StudentFeeManager::where('student_id', $student->id)->count());
    }

    public function test_a_fee_structure_scoped_to_another_class_is_not_billed(): void
    {
        $schoolId = $this->makeSchool();
        $classId = $this->makeClass($schoolId);
        $otherClassId = $this->makeClass($schoolId, ['name' => 'Other Class']);
        $this->makeFeeStructure($schoolId, ['class_id' => $otherClassId, 'name' => 'Other Class Fee']);
        $student = User::create([
            'name' => 'Class Fee Student', 'email' => 'class.fee.student3@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        $created = StudentFeeInvoiceGenerator::generateForClassBasedStudent($student, $classId, $schoolId);

        $this->assertCount(0, $created);
    }

    public function test_a_class_agnostic_mandatory_fee_structure_is_billed_to_any_class(): void
    {
        $schoolId = $this->makeSchool();
        $classId = $this->makeClass($schoolId);
        $this->makeFeeStructure($schoolId, ['class_id' => null, 'name' => 'School-Wide Fee', 'amount' => 200]);
        $student = User::create([
            'name' => 'Class Fee Student', 'email' => 'class.fee.student4@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        $created = StudentFeeInvoiceGenerator::generateForClassBasedStudent($student, $classId, $schoolId);

        $this->assertCount(1, $created);
    }

    public function test_no_class_id_generates_nothing(): void
    {
        $schoolId = $this->makeSchool();
        $this->makeFeeStructure($schoolId, ['class_id' => null]);
        $student = User::create([
            'name' => 'Class Fee Student', 'email' => 'class.fee.student5@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        $created = StudentFeeInvoiceGenerator::generateForClassBasedStudent($student, null, $schoolId);

        $this->assertCount(0, $created);
    }
}
