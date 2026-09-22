<?php

namespace Tests\Feature;

use App\Models\FeeStructure;
use App\Models\StudentFeeManager;
use App\Models\User;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the student-facing "Fee Manager" bug: the default (no filter
 * applied) view hard-coded a trailing 30-day window, so any invoice older
 * than that silently never showed up and the page said "No data found"
 * even for a student with real, unpaid fees. Also covers the previously
 * broken @include('student.fee_manager.list') partial, which actually
 * rendered an unrelated Hostel Fee table (a misplaced file, not a variant
 * of the invoice list) — removed in favor of inlining the invoice table
 * directly into student_fee_manager.blade.php.
 */
class StudentFeeManagerViewTest extends TestCase
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
            'name' => 'Fee Student', 'email' => $email,
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);
    }

    public function test_a_student_sees_an_invoice_older_than_30_days_by_default(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'oldinvoice@example.com');

        StudentFeeManager::create([
            'title' => 'Tuition — Semester 1', 'total_amount' => 500000, 'amount' => 500000,
            'discounted_price' => 0, 'class_id' => 0, 'programme_id' => null,
            'student_id' => $student->id, 'parent_id' => null, 'payment_method' => 'unpaid',
            'paid_amount' => 0, 'status' => 'unpaid', 'school_id' => $schoolId, 'session_id' => 1,
            'timestamp' => strtotime('-90 days'),
        ]);

        $response = $this->actingAs($student)->get(route('student.fee_manager.list'));

        $response->assertOk();
        $response->assertSee('Tuition — Semester 1');
        $response->assertDontSee('No invoices found for this filter');
    }

    public function test_the_balance_due_and_invoice_counts_are_correct(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'balance@example.com');

        StudentFeeManager::create([
            'title' => 'Tuition', 'total_amount' => 1000, 'amount' => 1000, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $student->id, 'parent_id' => null,
            'payment_method' => 'unpaid', 'paid_amount' => 300, 'status' => 'unpaid',
            'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);
        StudentFeeManager::create([
            'title' => 'Library Fee', 'total_amount' => 200, 'amount' => 200, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $student->id, 'parent_id' => null,
            'payment_method' => 'cash', 'paid_amount' => 200, 'status' => 'paid',
            'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);

        $response = $this->actingAs($student)->get(route('student.fee_manager.list'));

        $response->assertOk();
        // Balance due = (1000-300) + (200-200) = 700
        $response->assertSee('700');
    }

    public function test_a_student_never_sees_another_students_invoices(): void
    {
        $schoolId = $this->makeSchool();
        $studentA = $this->makeStudent($schoolId, 'feea@example.com');
        $studentB = $this->makeStudent($schoolId, 'feeb@example.com');

        StudentFeeManager::create([
            'title' => 'Student B Only Fee', 'total_amount' => 100, 'amount' => 100, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $studentB->id, 'parent_id' => null,
            'payment_method' => 'unpaid', 'paid_amount' => 0, 'status' => 'unpaid',
            'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);

        $response = $this->actingAs($studentA)->get(route('student.fee_manager.list'));

        $response->assertOk();
        $response->assertDontSee('Student B Only Fee');
        $response->assertSee('No invoices found for this filter');
    }

    public function test_a_student_with_no_invoices_at_all_sees_the_empty_state_not_an_error(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'noinvoices@example.com');

        $response = $this->actingAs($student)->get(route('student.fee_manager.list'));

        $response->assertOk();
        $response->assertSee('No invoices found for this filter');
    }

    public function test_invoices_from_two_different_fee_structures_both_show_their_name(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'twofees@example.com');

        $tuition = FeeStructure::create([
            'school_id' => $schoolId, 'name' => 'Tuition Fee', 'fee_type' => 'tuition',
            'amount' => 500, 'is_mandatory' => 1, 'per_semester' => 1,
        ]);
        $library = FeeStructure::create([
            'school_id' => $schoolId, 'name' => 'Library Fee', 'fee_type' => 'other',
            'amount' => 50, 'is_mandatory' => 0, 'per_semester' => 1,
        ]);

        StudentFeeManager::create([
            'title' => 'Tuition Fee', 'total_amount' => 500, 'amount' => 500, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $student->id, 'parent_id' => null,
            'fee_structure_id' => $tuition->id, 'payment_method' => 'unpaid', 'paid_amount' => 0,
            'status' => 'unpaid', 'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);
        StudentFeeManager::create([
            'title' => 'Library Fee', 'total_amount' => 50, 'amount' => 50, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $student->id, 'parent_id' => null,
            'fee_structure_id' => $library->id, 'payment_method' => 'unpaid', 'paid_amount' => 0,
            'status' => 'unpaid', 'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);

        $response = $this->actingAs($student)->get(route('student.fee_manager.list'));

        $response->assertOk();
        $response->assertSee('Tuition Fee');
        $response->assertSee('Library Fee');
    }
}
