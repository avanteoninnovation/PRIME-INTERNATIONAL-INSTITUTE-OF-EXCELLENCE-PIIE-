<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Security Phase 2I — the legacy PayPal / Stripe / Razorpay / Paytm gateway layer.
 *
 * Evidence (see the Phase 2I report):
 *  - the checkout pages (student/parent fee, student hostel fee, admin subscription)
 *    include only the MarzPay partial and the offline-payment form;
 *  - the legacy gateway partials (paypal/stripe/razorpay/paytm.blade.php) are not
 *    included anywhere, and the gateway routes they post to (payment.paypal.pay,
 *    stripe.post, razorpay.payment.store, paytm.payment) are not registered;
 *  - none of the 11 callback/initiation controller methods ever existed in git history.
 * The layer was replaced by MarzPay (server-verified collection status + webhook)
 * and staff-approved offline payments, so its dead routes were removed.
 *
 * The two legacy hostel-fee callbacks that DID have methods marked an invoice paid
 * from URL data alone (no gateway verification) and had no caller at all; they were
 * removed with the rest of the layer.
 */
class LegacyPaymentGatewayRoutesTest extends TestCase
{
    use StaffModuleTestHelper;

    private const REMOVED = [
        'superadmin.paypal.subscription', 'superadmin.stripe.subscription', 'superadmin.razorpay.subscription',
        'superadmin.paytm.subscription', 'superadmin.paytm.callback',
        'admin_subscription_fee_success_payment', 'admin_subscription_fee_fail_payment',
        'parent.student_fee_success_payment', 'parent.student_fee_fail_payment',
        'student.student_fee_success_payment_student', 'student.student_fee_fail_payment_student',
        'student.student_hostel_fee_success_payment_student', 'student.student_hostel_fee_fail_payment_student',
    ];

    public function test_the_dead_legacy_gateway_routes_are_gone(): void
    {
        foreach (self::REMOVED as $name) {
            $this->assertFalse(Route::has($name), "{$name} should no longer be registered");
        }
    }

    public function test_a_student_cannot_mark_a_hostel_fee_paid_by_visiting_a_success_url(): void
    {
        $this->bootStaffModuleTestSchema();
        $school = $this->makeSchool(['title' => 'Hostel School', 'status' => 1]);
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $school, 'account_status' => 'active']);
        $fee = DB::table('hostel_fees')->insertGetId(['school_id' => $school, 'student_id' => $student->id, 'title' => 'Hostel Fee', 'amount' => 500, 'status' => 'unpaid']);

        $forged = rawurlencode(json_encode(['invoice_id' => $fee, 'payment_method' => 'stripe']));
        $this->actingAs($student)->get("/student/hostel_payment/success/{$forged}/anything");

        $this->assertSame('unpaid', DB::table('hostel_fees')->where('id', $fee)->value('status'), 'hostel fee marked paid without any payment');
        $this->assertNull(DB::table('hostel_fees')->where('id', $fee)->value('paid_amount'));
    }

    public function test_forged_legacy_callbacks_cannot_record_payments(): void
    {
        $this->bootStaffModuleTestSchema();
        $school = $this->makeSchool(['title' => 'Fee School', 'status' => 1]);
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $school, 'account_status' => 'active']);
        $forged = rawurlencode(json_encode(['invoice_id' => 1, 'payment_method' => 'paypal', 'amount' => 1]));

        foreach (["/student/payment/success/{$forged}/ok", "/parent/payment/success/{$forged}/ok", "/admin/subscription/payment/success/{$forged}/ok"] as $uri) {
            $this->actingAs($student)->get($uri)->assertNotFound();
        }
        $this->post('/subscription/paytm-callback/a/b/' . $forged)->assertNotFound();
        $this->post('/PayWithStripe/subscription')->assertNotFound();
    }
}
