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
}
