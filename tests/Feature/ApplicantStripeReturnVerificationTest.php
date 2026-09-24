<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\ApplicationPayment;
use Illuminate\Support\Facades\DB;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Security Phase 2I — applicant Stripe return (dormant gateway, reachable by
 * direct request when Stripe keys are configured).
 *
 * confirmStripe() used to retrieve whatever checkout session the browser
 * named in ?session_id= and settle the fee if that session was paid — so a
 * paid session from any other payment (another applicant's, or the same
 * applicant's other application) could settle this one. It now verifies the
 * session created for THIS payment (payments.gateway_txn_id) and checks the
 * reference, amount and currency, as confirmFlutterwave() already did.
 *
 * Stripe is mocked through the SDK's HTTP client; no real API calls are made.
 */
class ApplicantStripeReturnVerificationTest extends TestCase
{
    use AdmissionsTestHelper;

    private int $schoolId;

    /** @var array<string, array> session id => session JSON the mocked Stripe returns */
    private array $sessions = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
        $this->schoolId = $this->makeSchool(['title' => 'Prime International Institute of Excellence']);
        DB::table('global_settings')->insert(['key' => 'primary_school_id', 'value' => (string) $this->schoolId]);
        DB::table('payment_methods')->insert([
            'name' => 'stripe', 'status' => 1, 'mode' => 'test', 'school_id' => $this->schoolId,
            'payment_keys' => json_encode(['test_key' => 'pk_test_x', 'test_secret_key' => 'sk_test_x']),
        ]);

        $sessions = &$this->sessions;
        ApiRequestor::setHttpClient(new class($sessions) implements ClientInterface {
            private $sessions;
            public function __construct(array &$sessions) { $this->sessions = &$sessions; }
            public function request($method, $absUrl, $headers, $params, $hasFile)
            {
                $id = basename(parse_url($absUrl, PHP_URL_PATH));
                return isset($this->sessions[$id])
                    ? [json_encode($this->sessions[$id]), 200, []]
                    : [json_encode(['error' => ['message' => 'No such checkout.session']]), 404, []];
            }
        });
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    private function stripeSession(string $id, string $reference, int $amountMinor, string $status = 'paid', string $currency = 'ugx'): void
    {
        $this->sessions[$id] = ['id' => $id, 'object' => 'checkout.session', 'payment_status' => $status, 'client_reference_id' => $reference,
            'amount_total' => $amountMinor, 'currency' => $currency, 'payment_intent' => 'pi_' . $id];
    }

    /** Signs in an applicant with a pending Stripe payment of UGX 50,000 whose server-created session is $sessionId. */
    private function pendingPayment(string $sessionId, string $reference): ApplicationPayment
    {
        $applicant = $this->makeApplicant($this->schoolId);
        $this->be($applicant, 'applicant');
        $this->get(route('applicant.dashboard'));
        $admission = Admission::where('applicant_id', $applicant->id)->firstOrFail();
        $admission->update(['intake_session_id' => $this->makeIntakeSession($this->schoolId, ['application_fee' => 50000])]);

        return ApplicationPayment::create([
            'school_id' => $this->schoolId, 'admission_id' => $admission->id, 'applicant_id' => $applicant->id,
            'amount' => 50000, 'currency' => 'UGX', 'method' => 'stripe', 'status' => ApplicationPayment::STATUS_PENDING,
            'reference' => $reference, 'gateway_txn_id' => $sessionId,
        ]);
    }

    private function returnTo(ApplicationPayment $payment, string $sessionId)
    {
        return $this->get(route('applicant.payment.gateway.return', ['gateway' => 'stripe', 'payment' => $payment->id]) . '?session_id=' . $sessionId);
    }

    public function test_the_payments_own_paid_session_settles_the_fee(): void
    {
        $payment = $this->pendingPayment('cs_own', 'APPFEE-OWN');
        $this->stripeSession('cs_own', 'APPFEE-OWN', 5000000);

        $this->returnTo($payment, 'cs_own');

        $this->assertSame(ApplicationPayment::STATUS_PAID, $payment->fresh()->status);
        $this->assertSame('cs_own', $payment->fresh()->gateway_txn_id);
    }

    public function test_another_payments_paid_session_cannot_settle_this_fee(): void
    {
        $payment = $this->pendingPayment('cs_mine_unpaid', 'APPFEE-MINE');
        $this->stripeSession('cs_mine_unpaid', 'APPFEE-MINE', 5000000, 'unpaid');
        $this->stripeSession('cs_someone_elses', 'APPFEE-OTHER', 5000000);   // paid, but for another payment

        $this->returnTo($payment, 'cs_someone_elses');

        $this->assertNotSame(ApplicationPayment::STATUS_PAID, $payment->fresh()->status, 'fee settled with another payment\'s session');
        $this->assertNotSame(Admission::FEE_PAID, Admission::find($payment->admission_id)->fee_status);
    }

    public function test_a_session_for_the_wrong_reference_or_a_lower_amount_is_rejected(): void
    {
        $wrongRef = $this->pendingPayment('cs_wrong_ref', 'APPFEE-A');
        $this->stripeSession('cs_wrong_ref', 'APPFEE-SOMETHING-ELSE', 5000000);
        $this->returnTo($wrongRef, 'cs_wrong_ref');
        $this->assertNotSame(ApplicationPayment::STATUS_PAID, $wrongRef->fresh()->status);

        $cheap = $this->pendingPayment('cs_cheap', 'APPFEE-B');
        $this->stripeSession('cs_cheap', 'APPFEE-B', 100);   // UGX 1.00 instead of 50,000
        $this->returnTo($cheap, 'cs_cheap');
        $this->assertNotSame(ApplicationPayment::STATUS_PAID, $cheap->fresh()->status);
    }

    public function test_an_unpaid_session_does_not_settle_the_fee_and_a_repeat_return_does_not_duplicate(): void
    {
        $payment = $this->pendingPayment('cs_pending', 'APPFEE-C');
        $this->stripeSession('cs_pending', 'APPFEE-C', 5000000, 'unpaid');
        $this->returnTo($payment, 'cs_pending');
        $this->assertSame(ApplicationPayment::STATUS_FAILED, $payment->fresh()->status);

        $paid = $this->pendingPayment('cs_twice', 'APPFEE-D');
        $this->stripeSession('cs_twice', 'APPFEE-D', 5000000);
        $this->returnTo($paid, 'cs_twice');
        $this->returnTo($paid, 'cs_twice');
        $this->assertSame(1, ApplicationPayment::where('admission_id', $paid->admission_id)->where('status', ApplicationPayment::STATUS_PAID)->count());
    }

    public function test_another_applicant_cannot_use_the_return_url_of_someone_elses_payment(): void
    {
        $victim = $this->pendingPayment('cs_victim', 'APPFEE-V');
        $this->stripeSession('cs_victim', 'APPFEE-V', 5000000);

        $this->pendingPayment('cs_attacker', 'APPFEE-X');   // signs in as a different applicant
        $this->returnTo($victim, 'cs_victim')->assertRedirect(route('applicant.payment'));

        $this->assertSame(ApplicationPayment::STATUS_PENDING, $victim->fresh()->status);
    }
}
