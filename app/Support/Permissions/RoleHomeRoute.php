<?php

namespace App\Support\Permissions;

use App\Models\User;

/**
 * Central role_id → home-dashboard route mapping, extracted from
 * Auth\LoginController::login() (used there to redirect right after a
 * successful login) so every role middleware's "wrong role for this area"
 * case can send the user back to their OWN dashboard too.
 *
 * Before this existed, every one of the ~18 role middleware classes
 * (AdminMiddleware, StudentMiddleware, TeacherMiddleware, ...) treated
 * "logged in but the wrong role for this route" exactly the same as "this
 * account has been disabled" — same redirect, same "your account has been
 * disabled" message. In practice the wrong-role case is the overwhelmingly
 * common one (a student following a stale/mistyped admin link, a teacher
 * clicking a bookmarked accountant page, etc.), so every one of those users
 * saw an alarming, false "your account has been disabled" notice for a
 * situation that had nothing to do with their account status at all.
 */
class RoleHomeRoute
{
    /**
     * @return string a route *name* — always resolvable via route(), never
     *                 a raw path, so a typo here fails loudly in tests
     *                 rather than silently 404ing in production.
     */
    public static function name(?User $user): string
    {
        if (!$user) {
            return 'login';
        }

        return match ((int) $user->role_id) {
            1 => 'superadmin.dashboard',
            2 => 'admin.dashboard',
            3 => 'teacher.dashboard',
            4 => 'accountant.dashboard',
            5 => 'librarian.dashboard',
            6 => 'parent.dashboard',
            7 => 'student.dashboard',
            10 => 'warden.dashboard',
            // 9 (Registrar) and 15 (HR Manager) have no dedicated dashboard
            // of their own yet — same fallback Auth\LoginController::login()
            // already uses for them.
            9 => 'admin.dashboard',
            15 => 'admin.leave.index',
            // 8 (Driver) has no portal built at all yet — no views, no
            // controller, no middleware. Auth\LoginController::login() sends
            // a driver login to a route ('driver.dashboard') that doesn't
            // exist, which would throw a hard RouteNotFoundException; login
            // itself is a separate fix, but this mapping must not repeat
            // that mistake, so it falls back to the generic landing page.
            8 => 'landingPage',
            // The remaining numbered staff roles (11 HOD, 12 Admissions, 13,
            // 14 Director, 16 Procurement, 17 Store Keeper, 18 Receptionist,
            // 19 Examinations) are all granted shared Admin access by
            // AdminMiddleware's $staffRoles list — same home as Admin.
            11, 12, 13, 14, 16, 17, 18, 19 => 'admin.dashboard',
            default => 'landingPage',
        };
    }
}
