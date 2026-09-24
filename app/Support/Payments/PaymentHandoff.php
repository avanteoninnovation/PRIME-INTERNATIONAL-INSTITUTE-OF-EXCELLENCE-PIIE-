<?php

namespace App\Support\Payments;

use App\Models\StudentFeeManager;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Hands a signed-in mobile-app (Sanctum) student over to the web payment page
 * WITHOUT any credential in the URL.
 *
 * Why this exists: payment gateways run in the browser, which needs a web
 * session, while the mobile app only holds an API token. The legacy deep link
 * (/web_redirect_to_pay_fee) bridged that gap by putting the user's plaintext
 * password in the query string; it no longer authenticates anyone.
 *
 * Instead the app asks the authenticated API for a link (POST /api/payment_link).
 * The link is a Laravel temporary SIGNED URL (tamper-proof, expires in 5 minutes)
 * carrying a random 64-character key whose server-side record (cache, same TTL)
 * names only the student and the fee. The key is SINGLE-USE: it is consumed under
 * an atomic lock, so a replayed or refreshed link cannot log anyone in again.
 */
final class PaymentHandoff
{
    public const TTL_MINUTES = 5;
    private const PREFIX = 'payment-handoff:';

    /** @return array{url: string, expires_at: string} */
    public static function issue(User $student, StudentFeeManager $fee): array
    {
        $key = Str::random(64);
        $expires = now()->addMinutes(self::TTL_MINUTES);

        Cache::put(self::PREFIX . $key, [
            'user_id' => (int) $student->id,
            'fee_id' => (int) $fee->id,
            'school_id' => (int) $student->school_id,
        ], $expires);

        return [
            'url' => URL::temporarySignedRoute('webPayFeeHandoff', $expires, ['handoff' => $key]),
            'expires_at' => $expires->toIso8601String(),
        ];
    }

    /** The handoff record, consumed exactly once; null when unknown, expired or already used. */
    public static function redeem(string $key): ?array
    {
        if (!preg_match('/^[A-Za-z0-9]{64}$/', $key)) {
            return null;
        }

        $record = Cache::lock(self::PREFIX . 'lock:' . $key, 10)->get(fn () => Cache::pull(self::PREFIX . $key));

        return is_array($record) ? $record : null;
    }
}
