<?php

namespace App\Support\Permissions;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * The seven `{role}/account-disable` routes in routes/web.php carry no role
 * middleware (a disabled/wrong-role user can't pass one to reach them), so
 * without this check a logged-in user of any role landing on — or linking
 * to — another role's disabled-account URL just saw that OTHER role's page
 * rendered for them. Sibling to PortalAccessDenial/RoleHomeRoute, but this
 * one sends the user to their own account-disable page specifically, not
 * their dashboard, since arriving here at all usually means something about
 * their account status needs their attention.
 */
class RoleAccountDisableRoute
{
    private const ROUTE_BY_ROLE = [
        1 => 'admin.account_disableview',
        2 => 'admin.account_disableview',
        3 => 'teacher.account_disable',
        4 => 'accountant.account_disable',
        5 => 'librarian.account_disable',
        6 => 'parent.account_disable',
        7 => 'student.account_disable',
        10 => 'warden.account_disable',
    ];

    /**
     * @param string $currentRouteName the route name this request landed on
     * @return RedirectResponse|null null means "let this request through"
     */
    public static function redirectIfMismatched(?User $user, string $currentRouteName): ?RedirectResponse
    {
        if (!$user) {
            return null;
        }

        $ownRouteName = self::ROUTE_BY_ROLE[(int) $user->role_id] ?? null;

        if ($ownRouteName && $ownRouteName !== $currentRouteName) {
            return redirect()->route($ownRouteName);
        }

        return null;
    }
}
