<?php

namespace App\Support\Permissions;

/**
 * RBAC Phase 3B — a role/permission change that is authorized but cannot be
 * applied as asked (duplicate role name, role still assigned, inactive role…).
 * Shown to the School Admin as an ordinary error, unlike an
 * AuthorizationException, which means "you may not do this at all".
 */
class RoleManagementException extends \RuntimeException
{
}
