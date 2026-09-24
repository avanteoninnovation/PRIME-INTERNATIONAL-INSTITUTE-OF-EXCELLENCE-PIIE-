<?php

namespace App\Support\Staff;

use App\Models\StaffProfile;
use Illuminate\Support\Facades\Crypt;

/**
 * National Identification Number (NIN) handling — HIGHLY SENSITIVE.
 *
 *  - normalize(): upper-case, whitespace / hyphens / dots / slashes removed,
 *    so "cm 9001-2345 abcd" and "CM90012345ABCD" are the same person;
 *  - stored encrypted at rest (staff_profiles.nin_encrypted, Laravel Crypt:
 *    AES-256 with the application key, authenticated);
 *  - duplicates detected through nin_hash = HMAC-SHA256(normalized NIN) with a
 *    key derived from the application key — deterministic (so it can be
 *    indexed: unique per school_id + nin_hash) but useless without the key;
 *  - the value never appears in exception or validation messages, audit
 *    entries, directory queries or serialized models (both columns are hidden).
 *
 * Rotating APP_KEY makes existing encrypted NINs unreadable and their hashes
 * stale, exactly like every other Crypt value in the application.
 */
final class StaffNin
{
    public const MIN_LENGTH = 5;
    public const MAX_LENGTH = 30;

    public static function normalize(?string $nin): string
    {
        return strtoupper((string) preg_replace('/[\s\-\.\/]+/', '', (string) $nin));
    }

    /** True for a normalized value of 5–30 letters/digits. The message never echoes the input. */
    public static function isValid(?string $nin): bool
    {
        $normalized = self::normalize($nin);

        return (bool) preg_match('/^[A-Z0-9]{' . self::MIN_LENGTH . ',' . self::MAX_LENGTH . '}$/', $normalized);
    }

    public static function hash(string $nin): string
    {
        return hash_hmac('sha256', self::normalize($nin), self::hashKey());
    }

    /**
     * Records (or replaces) $profile's NIN. Refuses an invalid value or one already
     * recorded for ANOTHER staff member of the same school. Does not save the profile.
     */
    public static function assign(StaffProfile $profile, string $nin): void
    {
        if (!self::isValid($nin)) {
            throw new StaffRecordException('The NIN must be 5 to 30 letters or digits.');
        }

        $hash = self::hash($nin);
        $taken = StaffProfile::where('school_id', $profile->school_id)->where('nin_hash', $hash)
            ->when($profile->exists, fn ($q) => $q->where('id', '!=', $profile->id))
            ->exists();
        if ($taken) {
            throw new StaffRecordException('This NIN is already recorded for another staff member of this school.');
        }

        $profile->nin_encrypted = Crypt::encryptString(self::normalize($nin));
        $profile->nin_hash = $hash;
    }

    /** The full NIN. Callers must have checked staff.nin.view first (StaffRecordService::revealNin). */
    public static function decrypt(StaffProfile $profile): ?string
    {
        return $profile->nin_encrypted ? Crypt::decryptString($profile->nin_encrypted) : null;
    }

    /** e.g. "**********ABCD" — last 4 characters only; null when no NIN is recorded. */
    public static function masked(StaffProfile $profile): ?string
    {
        $nin = self::decrypt($profile);

        return $nin === null ? null : str_repeat('*', max(strlen($nin) - 4, 0)) . substr($nin, -4);
    }

    private static function hashKey(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        return hash_hmac('sha256', 'piie.staff.nin.v1', $key, true);
    }
}
