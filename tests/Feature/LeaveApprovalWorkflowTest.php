<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Leavelist;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\LeaveTestHelper;
use Tests\TestCase;

class LeaveApprovalWorkflowTest extends TestCase
{
    use LeaveTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootLeaveTestSchema();
        $this->withoutMiddleware(VerifyCsrfToken::class);
    }

    public function test_admin_can_approve_a_leave_request(): void
    {
        $admin = $this->makeUser(2, 1);
        $staff = $this->makeUser(3, 1);
        $leaveId = $this->makeLeave(['user_id' => $staff->id]);

        $response = $this->actingAs($admin)->post(route('admin.leave.approve', $leaveId), [
            'comment' => 'Enjoy your leave',
        ]);

        $response->assertRedirect();
        $leave = Leavelist::find($leaveId);
        $this->assertSame(Leavelist::STATUS_APPROVED, $leave->status);
        $this->assertSame('Enjoy your leave', $leave->admin_comment);
        $this->assertSame($admin->id, $leave->approved_by);
    }

    public function test_admin_can_approve_a_leave_request_without_a_comment(): void
    {
        $admin = $this->makeUser(2, 1);
        $leaveId = $this->makeLeave();

        $response = $this->actingAs($admin)->post(route('admin.leave.approve', $leaveId), []);

        $response->assertRedirect();
        $leave = Leavelist::find($leaveId);
        $this->assertSame(Leavelist::STATUS_APPROVED, $leave->status);
        $this->assertNull($leave->admin_comment);
    }

    public function test_admin_can_return_a_leave_request_with_comment(): void
    {
        $admin = $this->makeUser(2, 1);
        $leaveId = $this->makeLeave();

        $response = $this->actingAs($admin)->post(route('admin.leave.return', $leaveId), [
            'comment' => 'Please attach a supporting document',
        ]);

        $response->assertRedirect();
        $leave = Leavelist::find($leaveId);
        $this->assertSame(Leavelist::STATUS_RETURNED, $leave->status);
        $this->assertSame('Please attach a supporting document', $leave->admin_comment);
        $this->assertSame($admin->id, $leave->approved_by);
    }

    public function test_admin_cannot_return_without_a_comment(): void
    {
        $admin = $this->makeUser(2, 1);
        $leaveId = $this->makeLeave();

        $response = $this->actingAs($admin)->post(route('admin.leave.return', $leaveId), []);

        $response->assertSessionHasErrors('comment');
        $leave = Leavelist::find($leaveId);
        $this->assertSame('pending', $leave->status);
        $this->assertNull($leave->admin_comment);
    }

    public function test_admin_can_deny_a_leave_request_with_reason(): void
    {
        $admin = $this->makeUser(2, 1);
        $leaveId = $this->makeLeave();

        $response = $this->actingAs($admin)->post(route('admin.leave.reject', $leaveId), [
            'comment' => 'Insufficient staffing coverage on requested dates',
        ]);

        $response->assertRedirect();
        $leave = Leavelist::find($leaveId);
        $this->assertSame(Leavelist::STATUS_REJECTED, $leave->status);
        $this->assertSame('Insufficient staffing coverage on requested dates', $leave->admin_comment);
        $this->assertSame($admin->id, $leave->approved_by);
    }

    public function test_admin_cannot_deny_without_a_reason(): void
    {
        $admin = $this->makeUser(2, 1);
        $leaveId = $this->makeLeave();

        $response = $this->actingAs($admin)->post(route('admin.leave.reject', $leaveId), []);

        $response->assertSessionHasErrors('comment');
        $leave = Leavelist::find($leaveId);
        $this->assertSame('pending', $leave->status);
        $this->assertNull($leave->admin_comment);
    }

    public function test_hr_manager_can_approve_a_leave_request(): void
    {
        $hrManager = $this->makeUser(15, 1);
        $leaveId = $this->makeLeave();

        $response = $this->actingAs($hrManager)->post(route('admin.leave.approve', $leaveId), [
            'comment' => 'Approved',
        ]);

        $response->assertRedirect();
        $this->assertSame(Leavelist::STATUS_APPROVED, Leavelist::find($leaveId)->status);
    }

    public function test_unauthorized_staff_cannot_approve_return_or_deny(): void
    {
        $teacher = $this->makeUser(3, 1);
        $leaveId = $this->makeLeave(['user_id' => $teacher->id]);

        $approveResponse = $this->actingAs($teacher)->post(route('admin.leave.approve', $leaveId), ['comment' => 'self approved']);
        $returnResponse  = $this->actingAs($teacher)->post(route('admin.leave.return', $leaveId), ['comment' => 'x']);
        $rejectResponse  = $this->actingAs($teacher)->post(route('admin.leave.reject', $leaveId), ['comment' => 'x']);

        $approveResponse->assertRedirect(route('login'));
        $returnResponse->assertRedirect(route('login'));
        $rejectResponse->assertRedirect(route('login'));

        $leave = Leavelist::find($leaveId);
        $this->assertSame('pending', $leave->status);
        $this->assertNull($leave->approved_by);
    }

    public function test_employee_can_see_final_status_and_comment_after_denial(): void
    {
        $admin = $this->makeUser(2, 1);
        $staff = $this->makeUser(3, 1);
        $leaveId = $this->makeLeave(['user_id' => $staff->id]);

        $this->actingAs($admin)->post(route('admin.leave.reject', $leaveId), [
            'comment' => 'Not enough notice given',
        ]);

        // What the employee sees when viewing their leave request/history.
        $leave = Leavelist::where('user_id', $staff->id)->findOrFail($leaveId);
        $this->assertSame(Leavelist::STATUS_REJECTED, $leave->status);
        $this->assertSame('Not enough notice given', $leave->admin_comment);
    }

    public function test_employee_can_see_return_comment(): void
    {
        $admin = $this->makeUser(2, 1);
        $staff = $this->makeUser(3, 1);
        $leaveId = $this->makeLeave(['user_id' => $staff->id]);

        $this->actingAs($admin)->post(route('admin.leave.return', $leaveId), [
            'comment' => 'Please provide a medical certificate',
        ]);

        $leave = Leavelist::where('user_id', $staff->id)->findOrFail($leaveId);
        $this->assertSame(Leavelist::STATUS_RETURNED, $leave->status);
        $this->assertSame('Please provide a medical certificate', $leave->admin_comment);
    }

    public function test_action_records_who_performed_it_and_when(): void
    {
        $admin = $this->makeUser(2, 1);
        $leaveId = $this->makeLeave();

        $before = now();
        $this->actingAs($admin)->post(route('admin.leave.reject', $leaveId), [
            'comment' => 'Denied for testing',
        ]);

        $leave = Leavelist::find($leaveId);
        $this->assertSame($admin->id, $leave->approved_by);
        $this->assertTrue($leave->updated_at->greaterThanOrEqualTo($before->subSecond()));

        $auditEntry = DB::table('audit_logs')
            ->where('module', 'Leave')
            ->where('action', 'reject')
            ->latest('id')
            ->first();

        $this->assertNotNull($auditEntry);
        $this->assertSame($admin->id, $auditEntry->user_id);
        $this->assertSame($admin->name, $auditEntry->user_name);
        $this->assertStringContainsString('Denied for testing', $auditEntry->description);
        $this->assertNotNull($auditEntry->created_at);
    }

    public function test_admin_can_create_a_leave_type_and_is_redirected_not_shown_raw_json(): void
    {
        $admin = $this->makeUser(2, 1);

        $response = $this->actingAs($admin)->post(route('admin.leave_types.store'), [
            'name' => 'Sick Leave',
            'max_days' => 10,
            'is_paid' => 1,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leave_types', [
            'school_id' => 1,
            'name' => 'Sick Leave',
            'max_days' => 10,
        ]);
    }

    public function test_admin_apply_leave_routes_no_longer_exist(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.leave.open_modal'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.leave.store'));
    }
}
