<?php

namespace App\Support;

use App\Models\School;

/**
 * Resolves the school_id that the public (unauthenticated) application
 * form belongs to. This deployment serves exactly one institution's public
 * site, so resolution is a single configured value — never a dropdown, and
 * never derived from a logged-in user (there isn't one on this flow).
 *
 * Kept as one small, isolated entry point so that if another school later
 * needs its own domain-specific application portal, only this class needs
 * to change (e.g. to resolve by request host) — the public controller,
 * routes, and views never need to know how resolution works.
 */
class PublicTenantResolver
{
    public static function resolveSchoolId(): ?int
    {
        $configured = get_settings('primary_school_id');

        if (! empty($configured)) {
            return (int) $configured;
        }

        // No value configured yet — fall back to the first school on record
        // rather than failing the public form outright.
        return School::query()->orderBy('id')->value('id');
    }
}
