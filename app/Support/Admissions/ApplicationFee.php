<?php

namespace App\Support\Admissions;

use App\Models\Admission;
use App\Models\ApplicationPayment;
use App\Models\PaymentMethods;

/**
 * The application fee: how much, whether it is settled, and how it can be
 * paid.
 *
 * The amount comes from the chosen intake session, so an institution can run
 * a free "early bird" intake alongside a charged one without any code change.
 * An intake with no fee (or none chosen yet) means the payment step simply
 * does not apply — the wizard skips it rather than showing a zero-value
 * checkout.
 */
class ApplicationFee
{
    public static function amountFor(Admission $admission): float
    {
        $intake = $admission->relationLoaded('intakeSession')
            ? $admission->intakeSession
            : $admission->intakeSession()->first();

        return (float) ($intake->application_fee ?? 0);
    }

    public static function isRequired(Admission $admission): bool
    {
        return self::amountFor($admission) > 0;
    }

    public static function isSettled(Admission $admission): bool
    {
        if (! self::isRequired($admission)) {
            return true;
        }

        return $admission->isFeeSettled();
    }

    public static function currency(): string
    {
        return get_active_currency();
    }

    public static function format(float $amount): string
    {
        return self::currency() . ' ' . number_format($amount, 2);
    }

    /**
     * Recomputes `admissions.fee_status` from the payment rows.
     *
     * The column is a denormalised cache so the admissions queue can filter
     * and sort on fee state without joining; this is the one place allowed to
     * write it, and it is always derived, never set by hand.
     */
    public static function refreshStatus(Admission $admission): string
    {
        if (! self::isRequired($admission)) {
            $status = Admission::FEE_WAIVED;
        } else {
            $payments = $admission->payments()->get();

            if ($payments->contains(fn ($p) => $p->status === ApplicationPayment::STATUS_WAIVED)) {
                $status = Admission::FEE_WAIVED;
            } elseif ($payments->contains(fn ($p) => $p->status === ApplicationPayment::STATUS_PAID)) {
                $status = Admission::FEE_PAID;
            } elseif ($payments->contains(fn ($p) => $p->status === ApplicationPayment::STATUS_PENDING)) {
                $status = Admission::FEE_PENDING;
            } else {
                $status = Admission::FEE_UNPAID;
            }
        }

        if ($admission->fee_status !== $status) {
            $admission->forceFill(['fee_status' => $status])->save();
        }

        return $status;
    }

    /**
     * Payment options offered to applicants.
     *
     * Bank deposit is always available: it needs no third-party
     * configuration and is how most fees are actually paid at the
     * institutions this serves. Gateways appear only when a school has
     * switched them on and saved keys, so an applicant is never shown a
     * checkout button that cannot complete.
     *
     * Only gateways this deployment can actually take an applicant through
     * are listed — see App\Http\Controllers\Applicant\PaymentController.
     * Adding another means adding a branch there and an entry here, together;
     * advertising one without the other is a dead button on a payment page.
     */
    public const SUPPORTED_GATEWAYS = [
        'stripe' => ['label' => 'Card Payment (Visa / Mastercard)', 'icon' => 'bi-credit-card'],
    ];

    public static function availableMethods(int $schoolId): array
    {
        $methods = [
            [
                'key'         => 'offline',
                'label'       => get_phrase('Bank Deposit / Mobile Money'),
                'description' => get_phrase('Pay into the institution account, then upload your deposit slip or transaction message for confirmation.'),
                'icon'        => 'bi-bank',
            ],
        ];

        foreach (self::SUPPORTED_GATEWAYS as $name => $meta) {
            if (! self::gatewayIsConfigured($name, $schoolId)) {
                continue;
            }

            $methods[] = [
                'key'         => $name,
                'label'       => get_phrase($meta['label']),
                'description' => get_phrase('Pay online now and have your application marked as paid immediately.'),
                'icon'        => $meta['icon'],
            ];
        }

        return $methods;
    }

    public static function gatewayIsConfigured(string $gateway, int $schoolId): bool
    {
        if (! array_key_exists($gateway, self::SUPPORTED_GATEWAYS)) {
            return false;
        }

        $configured = PaymentMethods::where('name', $gateway)
            ->where('status', 1)
            ->where(function ($query) use ($schoolId) {
                $query->where('school_id', $schoolId)->orWhereNull('school_id');
            })
            ->first();

        return $configured && ! empty($configured->payment_keys);
    }

    /**
     * Bank details shown on the offline payment screen, edited by staff under
     * Admissions settings. Free text on purpose — account names, branch codes
     * and mobile-money short codes vary too much between countries to model.
     */
    public static function bankInstructions(): ?string
    {
        $instructions = get_settings('application_fee_bank_details');

        return $instructions !== null && $instructions !== '' ? $instructions : null;
    }
}
