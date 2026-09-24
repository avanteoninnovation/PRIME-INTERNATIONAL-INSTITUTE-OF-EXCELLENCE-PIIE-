<?php

namespace Tests\Feature;

use App\Models\StudentFeeManager;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the rebuilt Accountant/Bursar dashboard — real finance KPIs
 * (invoiced, collected, outstanding, collection rate, per-class/programme
 * breakdown) instead of the previous generic Students/Teachers/Parents/
 * Staff counts, which told a finance user nothing about money. Also
 * covers "Bursar" now being the same role as Accountant (role_id 4) with
 * its own /bursar/... URLs reaching the identical content, resolving the
 * previous contradiction where BursarMiddleware checked role_id 10 (Warden,
 * per RoleHomeRoute) and had no routes/views wired to it at all.
 */
class AccountantFinanceDashboardTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();

        // Needed by CommonController::get_student_details_by_id(), which
        // the dashboard's recent-payments/top-outstanding lists call per
        // invoice to resolve a student's display name. The roles table
        // itself is part of the shared schema (AdmissionsTestHelper).
        \Illuminate\Support\Facades\DB::table('roles')->insert(['role_id' => 7, 'name' => 'Student', 'school_id' => 0]);
    }

    private function makeAccountant(int $schoolId, string $email = 'accountant@example.com'): User
    {
        return User::create([
            'name' => 'Finance Officer', 'email' => $email,
            'password' => bcrypt('secret'), 'role_id' => 4, 'school_id' => $schoolId,
            'account_status' => 'active',
        ]);
    }

    private function makeStudent(int $schoolId, string $email): User
    {
        return User::create([
            'name' => 'Fee Student', 'email' => $email,
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);
    }

    public function test_dashboard_computes_totals_collected_and_outstanding_correctly(): void
    {
        $schoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);
        $studentA = $this->makeStudent($schoolId, 'kpi.a@example.com');
        $studentB = $this->makeStudent($schoolId, 'kpi.b@example.com');

        StudentFeeManager::create([
            'title' => 'Tuition', 'total_amount' => 1000, 'amount' => 1000, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $studentA->id, 'parent_id' => null,
            'payment_method' => 'cash', 'paid_amount' => 400, 'status' => 'unpaid',
            'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);
        StudentFeeManager::create([
            'title' => 'Tuition', 'total_amount' => 500, 'amount' => 500, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $studentB->id, 'parent_id' => null,
            'payment_method' => 'cash', 'paid_amount' => 500, 'status' => 'paid',
            'school_id' => $schoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);

        $response = $this->actingAs($accountant)->get(route('accountant.dashboard'));

        $response->assertOk();
        // invoiced = 1500, collected = 900, outstanding = 600
        $response->assertSee('1500');
        $response->assertSee('900');
        $response->assertSee('600');
    }

    public function test_a_bursar_url_reaches_the_same_dashboard_as_an_accountant(): void
    {
        $schoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);

        $response = $this->actingAs($accountant)->get(route('bursar.dashboard'));

        $response->assertOk();
        $response->assertSee('Finance Dashboard');
    }

    public function test_a_non_accountant_role_is_denied_the_bursar_route(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'notaccountant@example.com');

        $response = $this->actingAs($student)->get(route('bursar.dashboard'));

        $response->assertRedirect();
        $response->assertStatus(302);
    }

    public function test_finance_kpis_are_scoped_to_the_accountants_own_school(): void
    {
        $schoolId = $this->makeSchool();
        $otherSchoolId = $this->makeSchool();
        $accountant = $this->makeAccountant($schoolId);
        $otherStudent = $this->makeStudent($otherSchoolId, 'other.school@example.com');

        StudentFeeManager::create([
            'title' => 'Other School Fee', 'total_amount' => 99999, 'amount' => 99999, 'discounted_price' => 0,
            'class_id' => 0, 'programme_id' => null, 'student_id' => $otherStudent->id, 'parent_id' => null,
            'payment_method' => 'unpaid', 'paid_amount' => 0, 'status' => 'unpaid',
            'school_id' => $otherSchoolId, 'session_id' => 1, 'timestamp' => time(),
        ]);

        $response = $this->actingAs($accountant)->get(route('accountant.dashboard'));

        $response->assertOk();
        $response->assertDontSee('99999');
    }
}
