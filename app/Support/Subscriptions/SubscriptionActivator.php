<?php

namespace App\Support\Subscriptions;

use App\Mail\SuperAdminAproved;
use App\Models\Package;
use App\Models\PaymentHistory;
use App\Models\School;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Activates a school's subscription from a settled PaymentHistory row.
 *
 * Extracted from SuperAdminController::subscriptionPaymentStatus() (the
 * manual "superadmin clicks approve" path) so the same logic can also run
 * automatically once a MarzPay webhook confirms payment — the effect is
 * identical either way, only the trigger differs.
 */
class SubscriptionActivator
{
    public static function activate(int $paymentHistoryId): void
    {
        $payment_history = PaymentHistory::find($paymentHistoryId);

        if (! $payment_history) {
            return;
        }

        $package = Package::find($payment_history->package_id);
        $school = School::find($payment_history->school_id);
        $school_email = $school?->email;

        $user = User::find($payment_history->user_id);

        if (strtolower($package->interval) == 'days') {
            $expire_date = strtotime('+' . $package->days . ' days', strtotime(date('Y-m-d H:i:s')));
        } elseif (strtolower($package->interval) == 'monthly') {
            $monthly = $package->days * 30;
            $expire_date = strtotime('+' . $monthly . ' days', strtotime(date('Y-m-d H:i:s')));
        } elseif (strtolower($package->interval) == 'yearly') {
            $yearly = $package->days * 365;
            $expire_date = strtotime('+' . $yearly . ' days', strtotime(date('Y-m-d H:i:s')));
        } elseif ($package->interval == 'life_time') {
            $expire_date = '0';
        }

        $subcription = Subscription::where('school_id', $payment_history->school_id)->orderBy('id', 'desc')->first();

        $today = date('Y-m-d');
        $today_time = strtotime($today);

        if (! empty($subcription)) {
            $uses_date = $subcription['date_added'] - $today_time;
            $upgrade_expireDate = $expire_date - $uses_date;
            $subscriptions_active = $subcription->active ?? 1;
            $students_limit = $subcription->studentLimit ?? ($subcription->student_limit ?? null);
            $upgradePrice = $payment_history->amount - $subcription->paid_amount;
        }

        $info = ['document_file' => $payment_history->document_image];
        $offline_tr_keys = json_encode($info);

        $createSubscription = function ($paidAmount, $finalExpireDate, $finalStudentLimit = null) use ($payment_history, $offline_tr_keys, $package) {
            $payload = [
                'package_id'       => $payment_history->package_id,
                'school_id'        => $payment_history->school_id,
                'paid_amount'      => $paidAmount,
                'payment_method'   => ucwords($payment_history->paid_by),
                'transaction_keys' => $offline_tr_keys,
                'expire_date'      => $finalExpireDate,
            ];

            if (Schema::hasColumn('subscriptions', 'date_added')) {
                $payload['date_added'] = strtotime(date('Y-m-d'));
            }

            if (Schema::hasColumn('subscriptions', 'active')) {
                $payload['active'] = '1';
            }

            if (Schema::hasColumn('subscriptions', 'status')) {
                $payload['status'] = '1';
            }

            if (Schema::hasColumn('subscriptions', 'studentLimit')) {
                $payload['studentLimit'] = $finalStudentLimit ?? ($package->studentLimit ?? null);
            } elseif (Schema::hasColumn('subscriptions', 'student_limit')) {
                $payload['student_limit'] = $finalStudentLimit ?? ($package->studentLimit ?? null);
            }

            return Subscription::create($payload);
        };

        if (empty($subcription) || $subcription['expire_date'] < $today_time) {
            $subscriptionsmail = $createSubscription($payment_history->amount, $expire_date, $package->studentLimit ?? null);
        } elseif ($subscriptions_active == '1' && $students_limit == 'Unlimited') {
            $subscriptionsmail = $createSubscription($upgradePrice, $upgrade_expireDate, 'Unlimited');
        } else {
            $subscriptionsmail = $createSubscription($upgradePrice, $upgrade_expireDate, $package->studentLimit ?? null);
        }

        PaymentHistory::where('id', $paymentHistoryId)->update(['status' => 'approve']);

        if (! empty($subcription)) {
            Subscription::where('id', $subcription->id)->update(['active' => 0]);
        }

        School::where('id', $payment_history->school_id)->update(['status' => 1]);

        if (! empty(get_settings('smtp_user')) && get_settings('smtp_pass') && get_settings('smtp_host') && get_settings('smtp_port') && $school_email) {
            Mail::to($school_email)->send(new SuperAdminAproved($subscriptionsmail));
        }
    }
}
