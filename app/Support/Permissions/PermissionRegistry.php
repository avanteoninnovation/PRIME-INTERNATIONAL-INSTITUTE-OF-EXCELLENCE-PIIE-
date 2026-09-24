<?php

namespace App\Support\Permissions;

/**
 * RBAC Phase 3A — read access to the authoritative permission registry
 * (registry.php in this directory).
 *
 * The registry is code, not configuration: this repository keeps
 * config/*.php out of version control (see .gitignore), and a permission
 * catalogue that silently went missing on deploy would deny every staff
 * member — so it lives here and ships with the application.
 */
class PermissionRegistry
{
    private static ?array $data = null;

    private static function data(): array
    {
        return self::$data ??= require __DIR__ . '/registry.php';
    }

    /** @return array<string, array{module: string, label: string, description: string, sensitive: bool, delegable: bool}> */
    public static function permissions(): array
    {
        return self::data()['permissions'];
    }

    /** @return array<string, string> */
    public static function modules(): array
    {
        return self::data()['modules'];
    }

    /** Navigation section (get_role_nav_permissions) → permissions it implies today. */
    public static function navSections(): array
    {
        return self::data()['nav_sections'];
    }

    /** Base role id → permissions it has through its own portal. */
    public static function portalGrants(): array
    {
        return self::data()['portal_grants'];
    }

    /** Admin-portal route-name pattern → required permission (first match wins). */
    public static function routes(): array
    {
        return self::data()['routes'];
    }

    /** Permission → permissions it requires (Phase 3B dependency handling). */
    public static function requires(): array
    {
        return self::data()['requires'] ?? [];
    }

    /** Suggested custom-role starting points (never sensitive; Phase 3B). */
    public static function templates(): array
    {
        return self::data()['templates'] ?? [];
    }

    /** RBAC key → OnlineExamPermissionService key. */
    public static function onlineExamKeys(): array
    {
        return self::data()['online_exam_keys'];
    }
}
