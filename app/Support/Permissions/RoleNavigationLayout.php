<?php

namespace App\Support\Permissions;

use App\Models\User;

/**
 * role_id -> navigation layout view name, for the handful of views that are
 * genuinely shared across every role (currently just notifications/index) and
 * so can't @extends a single hardcoded layout the way every other view in
 * this app does. Sibling to RoleHomeRoute (dashboard *route*, not layout).
 */
class RoleNavigationLayout
{
    public static function name(?User $user): string
    {
        if (!$user) {
            return 'admin.navigation';
        }

        return match ((int) $user->role_id) {
            1 => 'superadmin.navigation',
            3 => 'teacher.navigation',
            5 => 'librarian.navigation',
            6 => 'parent.navigation',
            7 => 'student.navigation',
            10 => 'warden.navigation',
            4 => 'accountant.navigation',
            // Every other staff role (admin itself, plus registrar, HOD,
            // director, procurement, etc.) shares the Admin layout — same
            // grouping AdminMiddleware's $staffRoles already uses.
            default => 'admin.navigation',
        };
    }
}
