<?php

namespace App\Support\Staff;

/**
 * users.staff_status — employment status of a staff member.
 *
 *   active      → portal login allowed
 *   on_leave    → portal login allowed
 *   suspended   → portal blocked (unchanged, pre-existing)
 *   inactive    → portal blocked (unchanged, pre-existing)
 *   terminated  → portal blocked
 *
 * NULL (legacy accounts created before the Staff Module) keeps working and is
 * never blocked. No existing value is rewritten by any migration.
 */
final class StaffStatus
{
    public const ACTIVE = 'active';
    public const ON_LEAVE = 'on_leave';
    public const SUSPENDED = 'suspended';
    public const INACTIVE = 'inactive';
    public const TERMINATED = 'terminated';

    public const ALL = [self::ACTIVE, self::ON_LEAVE, self::SUSPENDED, self::INACTIVE, self::TERMINATED];

    /** Statuses that block the staff portals (User::isStaffPortalBlocked()). */
    public const BLOCKED = [self::SUSPENDED, self::INACTIVE, self::TERMINATED];

    public static function blocksPortal(?string $status): bool
    {
        return in_array($status, self::BLOCKED, true);
    }
}
