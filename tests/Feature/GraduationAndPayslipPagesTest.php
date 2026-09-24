<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Stability repair H7 + M4.
 *  - Graduation list: the controller passed $apps, the view reads $applications (HTTP 500).
 *  - Graduation "apply" modal listed role_id 4 (Accountant) as the students.
 *  - Teacher payslips: the controller passed $payslips, the view reads $payrolls (HTTP 500).
 */
class GraduationAndPayslipPagesTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $school;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        (require base_path('database/migrations/2026_07_02_130010_create_payroll_graduation_tables.php'))->up();
        $this->school = $this->makeSchool();
        $this->admin = $this->makeAdminUser($this->school);
    }

    public function test_graduation_list_renders_empty_and_with_applications(): void
    {
        $this->actingAs($this->admin)->get(route('admin.graduation.index'))->assertOk();

        $student = User::factory()->create(['role_id' => 7, 'school_id' => $this->school, 'name' => 'Graduating Student']);
        DB::table('graduation_applications')->insert(['school_id' => $this->school, 'student_id' => $student->id, 'ceremony_year' => date('Y'),
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->admin)->get(route('admin.graduation.index'))->assertOk()->assertSee('Graduating Student');
    }

    public function test_graduation_apply_modal_lists_students_not_accountants(): void
    {
        User::factory()->create(['role_id' => 7, 'school_id' => $this->school, 'name' => 'Real Student']);
        User::factory()->create(['role_id' => 4, 'school_id' => $this->school, 'name' => 'Some Accountant']);
        User::factory()->create(['role_id' => 7, 'school_id' => $this->makeSchool(), 'name' => 'Other School Student']);

        $this->actingAs($this->admin)->get(route('admin.graduation.open_modal'))->assertOk()
            ->assertSee('Real Student')->assertDontSee('Some Accountant')->assertDontSee('Other School Student');
    }

    public function test_teacher_payslips_render_empty_and_with_payslips(): void
    {
        $teacher = User::factory()->create(['role_id' => 3, 'school_id' => $this->school, 'account_status' => 'active']);
        $this->actingAs($teacher)->get(route('teacher.payroll.index'))->assertOk();

        DB::table('payroll')->insert(['school_id' => $this->school, 'staff_id' => $teacher->id, 'pay_period' => '2026-08-31',
            'basic_salary' => 1000, 'net_pay' => 950, 'status' => 'paid', 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($teacher)->get(route('teacher.payroll.index'))->assertOk()->assertSee('August 2026')->assertDontSee('No payslips available');
    }
}
