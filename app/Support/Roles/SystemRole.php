<?php

namespace App\Support\Roles;

/**
 * Canonical users.role_id map — the reconciled source of truth from the
 * RBAC Phase 0 audit, recorded in one place.
 *
 * PASSIVE BY DESIGN (RBAC Phase 1): this class is documentation and
 * reference data only. Nothing in the running application reads it yet —
 * every middleware, policy, controller, navigation map and login redirect
 * still uses its own hardcoded role_id checks, deliberately unchanged. It
 * makes no authorization decision and must not be wired into one until a
 * later phase does so behind its own characterization tests
 * (tests/Feature/Rbac*CharacterizationTest.php). Nothing here creates roles
 * rows or assigns roles to users.
 *
 * Why several IDs are not "normal" roles: the codebase carries four
 * conflicting numberings for 8–19 (install.sql, the *Middleware classes,
 * MultiStaffMiddleware / get_role_nav_permissions / LiveClassPolicy, and
 * AuditLog::ROLE_NAMES). The statuses below freeze the ambiguous IDs rather
 * than guess:
 *  - PROTECTED: seeded/operational, compatibility-sensitive; never renumber.
 *  - ACTIVE:    partially operational (some routes/logic depend on it).
 *  - PLANNED:   specialized staff role with code scaffolding but no live
 *               routes, users or roles row yet.
 *  - RESERVED:  seeded legacy meaning; never assign.
 *  - FROZEN:    conflicting meanings in code; never assign until resolved.
 */
class SystemRole
{
    public const SUPER_ADMIN = 1;
    public const SCHOOL_ADMIN = 2;
    public const TEACHER = 3;
    public const ACCOUNTANT = 4;
    public const LIBRARIAN = 5;
    public const PARENT = 6;
    public const STUDENT = 7;
    public const RESERVED_LEGACY_USER = 8;
    public const RESERVED_ALUMNI = 9;
    public const WARDEN = 10;
    public const FROZEN_CONFLICT_11 = 11;
    public const FROZEN_CONFLICT_12 = 12;
    public const ADMISSIONS_OFFICER = 13;
    public const DIRECTOR = 14;
    public const HR_MANAGER = 15;
    public const PROCUREMENT_OFFICER = 16;
    public const STORE_KEEPER = 17;
    public const RECEPTIONIST = 18;
    public const EXAMINATIONS_OFFICER = 19;

    public const STATUS_PROTECTED = 'protected';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PLANNED = 'planned';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_FROZEN = 'frozen';

    /**
     * Institutional names that are NOT separate roles. Bursar is this
     * school's name for the Accountant (see BursarMiddleware); HOD is a
     * Teacher carrying the "HOD" designation (users.designation_id), scoped
     * by department — not a role_id.
     */
    public const ALIASES = [
        'bursar' => ['role_id' => self::ACCOUNTANT],
        'hod' => ['role_id' => self::TEACHER, 'designation' => 'HOD'],
    ];

    /**
     * Roles the institution uses that have no safe existing role_id. Every
     * candidate (9, 10, 11, 12) already carries another meaning, and no new
     * ID is allocated in this phase.
     */
    public const UNALLOCATED = ['registrar'];

    private const ROLES = [
        self::SUPER_ADMIN => ['key' => 'super_admin', 'name' => 'Super Administrator', 'status' => self::STATUS_PROTECTED],
        self::SCHOOL_ADMIN => ['key' => 'school_admin', 'name' => 'School Administrator', 'status' => self::STATUS_PROTECTED],
        self::TEACHER => ['key' => 'teacher', 'name' => 'Teacher / Lecturer', 'status' => self::STATUS_PROTECTED],
        self::ACCOUNTANT => ['key' => 'accountant', 'name' => 'Accountant / Bursar', 'status' => self::STATUS_PROTECTED],
        self::LIBRARIAN => ['key' => 'librarian', 'name' => 'Librarian', 'status' => self::STATUS_PROTECTED],
        self::PARENT => ['key' => 'parent', 'name' => 'Parent', 'status' => self::STATUS_PROTECTED],
        self::STUDENT => ['key' => 'student', 'name' => 'Student', 'status' => self::STATUS_PROTECTED],
        // install.sql seeds 8 as "user"; AuditLog calls it Driver, the
        // landing page calls it Warden, LoginController targets a
        // driver.dashboard route that does not exist.
        self::RESERVED_LEGACY_USER => ['key' => 'reserved_legacy_user', 'name' => 'Reserved (legacy "user")', 'status' => self::STATUS_RESERVED],
        // install.sql seeds 9 as "alumni"; RegistrarMiddleware and
        // LoginController treat it as Registrar. Seeded meaning wins.
        self::RESERVED_ALUMNI => ['key' => 'reserved_alumni', 'name' => 'Reserved (Alumni)', 'status' => self::STATUS_RESERVED],
        // Operationally Warden (wardenCreate, WardenMiddleware, warden
        // portal); LiveClassPolicy / nav map still read 10 as Registrar.
        self::WARDEN => ['key' => 'warden', 'name' => 'Warden', 'status' => self::STATUS_PROTECTED],
        // HOD (HodMiddleware) vs Bursar (MultiStaff, nav map) vs Registrar (AuditLog).
        self::FROZEN_CONFLICT_11 => ['key' => 'frozen_conflict_11', 'name' => 'Frozen (conflicting meanings)', 'status' => self::STATUS_FROZEN],
        // Admissions (AdmissionsMiddleware) vs HOD (MultiStaff, nav map, LiveClassPolicy, AuditLog).
        self::FROZEN_CONFLICT_12 => ['key' => 'frozen_conflict_12', 'name' => 'Frozen (conflicting meanings)', 'status' => self::STATUS_FROZEN],
        self::ADMISSIONS_OFFICER => ['key' => 'admissions_officer', 'name' => 'Admissions Officer', 'status' => self::STATUS_PLANNED],
        self::DIRECTOR => ['key' => 'director', 'name' => 'Director', 'status' => self::STATUS_PLANNED],
        // hr_manager middleware gates the live leave-management routes.
        self::HR_MANAGER => ['key' => 'hr_manager', 'name' => 'HR Manager', 'status' => self::STATUS_ACTIVE],
        self::PROCUREMENT_OFFICER => ['key' => 'procurement_officer', 'name' => 'Procurement Officer', 'status' => self::STATUS_PLANNED],
        self::STORE_KEEPER => ['key' => 'store_keeper', 'name' => 'Store Keeper', 'status' => self::STATUS_PLANNED],
        self::RECEPTIONIST => ['key' => 'receptionist', 'name' => 'Receptionist', 'status' => self::STATUS_PLANNED],
        self::EXAMINATIONS_OFFICER => ['key' => 'examinations_officer', 'name' => 'Examinations Officer', 'status' => self::STATUS_PLANNED],
    ];

    /** @return array<int, array{key: string, name: string, status: string}> */
    public static function all(): array
    {
        return self::ROLES;
    }

    /** @return array{key: string, name: string, status: string}|null */
    public static function find(int $roleId): ?array
    {
        return self::ROLES[$roleId] ?? null;
    }

    public static function key(int $roleId): ?string
    {
        return self::ROLES[$roleId]['key'] ?? null;
    }

    public static function name(int $roleId): ?string
    {
        return self::ROLES[$roleId]['name'] ?? null;
    }

    public static function status(int $roleId): ?string
    {
        return self::ROLES[$roleId]['status'] ?? null;
    }

    public static function idForKey(string $key): ?int
    {
        foreach (self::ROLES as $roleId => $role) {
            if ($role['key'] === $key) {
                return $roleId;
            }
        }

        return null;
    }

    /** @return int[] */
    public static function idsWithStatus(string $status): array
    {
        return array_keys(array_filter(self::ROLES, fn ($role) => $role['status'] === $status));
    }
}
