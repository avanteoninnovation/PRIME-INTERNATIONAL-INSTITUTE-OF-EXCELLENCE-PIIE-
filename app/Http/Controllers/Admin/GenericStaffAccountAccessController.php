<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\GenericStaffPasswordSetupMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Mail\SafeMail;
use App\Support\Permissions\PermissionAssignmentService;
use App\Support\Roles\SystemRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class GenericStaffAccountAccessController extends Controller
{
    public function show($id, PermissionAssignmentService $assignments)
    {
        $this->authorizeAdministrator($assignments);
        $member = $this->genericStaffOrFail($id);

        return view('admin.rbac.staff.account_access', [
            'member' => $member,
            'setupPending' => (bool) $member->force_password_change || empty($member->password),
        ]);
    }

    public function sendSetupLink(Request $request, $id, PermissionAssignmentService $assignments)
    {
        $actor = $this->authorizeAdministrator($assignments);
        $member = $this->genericStaffOrFail($id);
        $broker = Password::broker('users');
        $repository = $broker->getRepository();

        if ($repository->recentlyCreatedToken($member)) {
            return redirect()->back()->with('error', get_phrase('A password setup link was sent recently. Please wait before sending another.'));
        }

        $token = $broker->createToken($member);
        $setupUrl = route('password.reset', ['token' => $token, 'email' => $member->email]);
        $delivered = SafeMail::send(
            $member->email,
            new GenericStaffPasswordSetupMail($member->name, $setupUrl),
            'generic-staff-password-setup'
        );

        if (!$delivered) {
            $broker->deleteToken($member);

            return redirect()->back()->with('error', get_phrase("We couldn't send the password setup email. Please check the institution's email settings and try again."));
        }

        AuditLog::record('STAFF_PASSWORD_SETUP_LINK_ISSUED', 'Staff & Students', 'Issued a password setup link for an Other Staff account.', [
            'school_id' => (int) $member->school_id,
            'record_type' => User::class,
            'record_id' => (int) $member->id,
        ]);

        return redirect()->back()
            ->with('message', get_phrase('A secure password setup link was sent to the staff member.'))
            ->with('setup_link_sent', true);
    }

    private function authorizeAdministrator(PermissionAssignmentService $assignments): User
    {
        $actor = auth()->user();
        abort_unless($actor
            && (int) $actor->role_id === SystemRole::SCHOOL_ADMIN
            && !empty($actor->school_id)
            && $assignments->canAdminister($actor), 403);

        return $actor;
    }

    private function genericStaffOrFail($id): User
    {
        return User::where('school_id', (int) auth()->user()->school_id)
            ->where('role_id', SystemRole::GENERIC_STAFF)
            ->findOrFail($id);
    }
}
