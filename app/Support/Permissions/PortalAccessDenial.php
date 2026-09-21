<?php

namespace App\Support\Permissions;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * The single place every role middleware (AdminMiddleware, StudentMiddleware,
 * TeacherMiddleware, ...) now goes to decide what to show a user who failed
 * its role check — instead of each one collapsing three different, genuinely
 * distinct situations into the same "your account has been disabled"
 * redirect:
 *
 *  1. Not logged in at all.
 *  2. Actually disabled (users.account_status === 'disable') or a staff
 *     account restricted via User::isStaffPortalBlocked().
 *  3. Logged in, account perfectly fine, just the wrong role for this
 *     particular route group (by far the most common real-world trigger —
 *     a stale bookmark, a mistyped URL, a link meant for a different role).
 *
 * Case 3 used to show the exact same "your account has been disabled,
 * contact your administrator" message as case 2, which is simply false and
 * needlessly alarming. It now sends the user back to their own dashboard
 * instead, with an accurate "you don't have permission for that area"
 * message.
 */
class PortalAccessDenial
{
    /**
     * @param string|null $disabledRouteName Route name for this role's own
     *        "account disabled" page, if one exists (e.g. 'student.
     *        account_disable'). Pass null for roles with no dedicated view
     *        — falls back to the login page.
     */
    public static function redirect(?User $user, ?string $disabledRouteName = null): RedirectResponse
    {
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please log in to continue.');
        }

        if ($user->account_status === 'disable') {
            return redirect()->route($disabledRouteName ?: 'login')
                ->with('error', 'Your account has been disabled. Please contact your school administrator.');
        }

        if (method_exists($user, 'isStaffPortalBlocked') && $user->isStaffPortalBlocked()) {
            return redirect()->route($disabledRouteName ?: 'login')
                ->with('error', 'Your staff portal access has been restricted. Please contact your school administrator.');
        }

        return redirect()->route(RoleHomeRoute::name($user))
            ->with('error', 'You do not have permission to access that area.');
    }
}
