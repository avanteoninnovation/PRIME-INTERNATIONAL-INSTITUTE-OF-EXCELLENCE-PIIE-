<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\TenantConfiguration;
use App\Support\Staff\StaffProvisioningService;

/**
 * Staff → Add Staff: a launcher into the EXISTING staff creation workflows.
 *
 * It creates nothing itself. Each card opens the existing create form in the
 * same right-side modal the per-role list pages use, and that form posts to the
 * existing create handler (validation, password handling, credentials email and
 * users.role_id all stay where they are). Delegated access (custom roles, direct
 * permissions) is added afterwards in Roles & Permissions → Manage Access.
 *
 * The route carries the same 'school_admin:hr' guard as the existing create
 * routes, so only School Administrators and HR Managers reach it. The cards
 * mirror each existing create route's own guard; those guards remain the
 * authority when a form is opened or submitted.
 */
class StaffLauncherController extends Controller
{
    /**
     * The existing creation workflows, in menu order. 'admin_only' mirrors the
     * 'school_admin' guard on admin.open_modal / admin.create; the others carry
     * 'school_admin:hr'. Only staff types with an existing creation workflow
     * are offered.
     */
    private const TYPES = [
        'admin' => ['label' => 'Admin', 'role_id' => 2, 'icon' => 'bi-shield-lock', 'form' => 'admin.open_modal', 'list' => 'admin.admin', 'admin_only' => true,
            'description' => 'Oversee school administration and manage institution-wide operations.'],
        'teacher' => ['label' => 'Teacher', 'role_id' => 3, 'icon' => 'bi-person-video3', 'form' => 'admin.teacher.open_modal', 'list' => 'admin.teacher', 'admin_only' => false,
            'description' => 'Deliver teaching, guide students, and contribute to learning and assessment.'],
        'accountant' => ['label' => 'Accountant', 'role_id' => 4, 'icon' => 'bi-calculator', 'form' => 'admin.accountant.open_modal', 'list' => 'admin.accountant', 'admin_only' => false,
            'description' => 'Support the institution’s finances, including fees, invoices, and expenses.'],
        'librarian' => ['label' => 'Librarian', 'role_id' => 5, 'icon' => 'bi-book', 'form' => 'admin.librarian.open_modal', 'list' => 'admin.librarian', 'admin_only' => false,
            'description' => 'Help students and staff find and use books and other learning resources.'],
        'warden' => ['label' => 'Warden', 'role_id' => 10, 'icon' => 'bi-house-door', 'form' => 'admin.warden.create_form', 'list' => 'admin.warden', 'admin_only' => false,
            'description' => 'Support student accommodation and oversee hostel operations.'],
        'other' => ['label' => 'Other Staff', 'role_id' => 20, 'icon' => 'bi-person-badge', 'form' => 'admin.staff.other.create', 'list' => 'admin.rbac.staff.index', 'admin_only' => false,
            'description' => 'Add institutional staff whose main responsibility is not listed above.'],
    ];

    public function index()
    {
        $types = self::creatableTypes(auth()->user());
        abort_if($types === [], 403);

        $terminology = app(TenantConfiguration::class)->terminology();
        if (isset($types['teacher'], $terminology['teacher'])) {
            $types['teacher']['label'] = $terminology['teacher'];
        }

        return view('admin.staff.add_staff', ['types' => $types]);
    }

    /** The staff types $user may create through the existing workflows (used by the launcher and the Staff menu). */
    public static function creatableTypes(?User $user): array
    {
        $roleIds = StaffProvisioningService::creatableRoleIds($user);

        return array_filter(self::TYPES, fn (array $type) => in_array($type['role_id'], $roleIds, true));
    }
}
