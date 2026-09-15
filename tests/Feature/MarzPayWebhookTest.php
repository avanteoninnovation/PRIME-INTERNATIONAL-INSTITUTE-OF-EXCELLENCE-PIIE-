<?php

namespace Tests\Feature;

use App\Models\ApplicationPayment;
use App\Models\PaymentMethods;
use App\Models\StudentFeeManager;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * MarzPayWebhookController never trusts the posted body's status on its
 * own — it re-fetches the transaction from MarzPay before applying
 * anything. These tests fake that re-fetch rather than the webhook POST's
 * own status field, since that's what actually decides the outcome.
 */
class MarzPayWebhookTest extends TestCase
{
    use AdmissionsTestHelper;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
        $this->schoolId = $this->makeSchool();

        PaymentMethods::create([
            'name'         => 'marzpay',
            'image'        => 'marzpay.png',
            'status'       => 1,
            'mode'         => 'test',
            'school_id'    => $this->schoolId,
            'payment_keys' => json_encode([
                'sandbox_api_key'    => 'k',
                'sandbox_api_secret' => 's',
                'country'            => 'UG',
            ]),
        ]);
    }

    private function fakeVerifiedTransaction(string $status = 'successful'): void
    {
        Http::fake([
            'wallet.wearemarz.com/api/v1/collect-money/*' => Http::response([
                'status' => 'success',
                'data'   => [
                    'transaction' => ['uuid' => 'txn-1', 'status' => $status],
                    'collection'  => ['amount' => ['raw' => 5000]],
                ],
            ], 200),
        ]);
    }

    private function postWebhook(string $context, int $contextId, string $eventType = 'collection.completed'): void
    {
        $this->postJson(route('webhooks.marzpay'), [
            'event_type'  => $eventType,
            'transaction' => ['uuid' => 'txn-1', 'reference' => 'ref-1', 'status' => 'completed'],
            'collection'  => ['amount' => ['raw' => 5000]],
            'metadata'    => [['context' => $context], ['context_id' => $contextId]],
        ])->assertOk();
    }

    public function test_it_marks_a_tuition_fee_paid_when_marzpay_confirms_the_collection(): void
    {
        $fee = StudentFeeManager::create([
            'title' => 'Term Fee', 'total_amount' => 5000, 'amount' => 5000, 'class_id' => 0,
            'student_id' => 1, 'payment_method' => 'marzpay', 'paid_amount' => 0, 'status' => 'processing',
            'school_id' => $this->schoolId, 'gateway_reference' => 'txn-1',
        ]);

        $this->fakeVerifiedTransaction('successful');

        $this->postWebhook('tuition', $fee->id);

        $fee->refresh();
        $this->assertSame('paid', $fee->status);
        $this->assertEquals(5000, $fee->paid_amount);
        $this->assertSame('marzpay', $fee->payment_method);
    }

    public function test_a_redelivered_webhook_for_an_already_paid_fee_is_a_no_op(): void
    {
        $fee = StudentFeeManager::create([
            'title' => 'Term Fee', 'total_amount' => 5000, 'amount' => 5000, 'class_id' => 0,
            'student_id' => 1, 'payment_method' => 'marzpay', 'paid_amount' => 5000, 'status' => 'paid',
            'school_id' => $this->schoolId, 'gateway_reference' => 'txn-1',
        ]);

        $this->fakeVerifiedTransaction('successful');

        // Redeliver the same completed event a second time.
        $this->postWebhook('tuition', $fee->id);
        $this->postWebhook('tuition', $fee->id);

        $fee->refresh();
        $this->assertSame('paid', $fee->status);
        $this->assertEquals(5000, $fee->paid_amount);
    }

    public function test_it_marks_an_application_payment_paid_when_marzpay_confirms_the_collection(): void
    {
        $admission = $this->makeAdmission($this->schoolId);

        $payment = ApplicationPayment::create([
            'school_id' => $this->schoolId, 'admission_id' => $admission, 'method' => 'marzpay',
            'status' => ApplicationPayment::STATUS_PENDING, 'amount' => 5000, 'reference' => 'ref-1',
            'gateway_txn_id' => 'txn-1',
        ]);

        $this->fakeVerifiedTransaction('successful');

        $this->postWebhook('application', $payment->id);

        $payment->refresh();
        $this->assertSame(ApplicationPayment::STATUS_PAID, $payment->status);
        $this->assertNotNull($payment->paid_at);
    }

    public function test_it_does_not_mark_paid_when_marzpay_reports_the_collection_is_still_pending(): void
    {
        $fee = StudentFeeManager::create([
            'title' => 'Term Fee', 'total_amount' => 5000, 'amount' => 5000, 'class_id' => 0,
            'student_id' => 1, 'payment_method' => 'marzpay', 'paid_amount' => 0, 'status' => 'processing',
            'school_id' => $this->schoolId, 'gateway_reference' => 'txn-1',
        ]);

        $this->fakeVerifiedTransaction('processing');

        $this->postWebhook('tuition', $fee->id);

        $fee->refresh();
        $this->assertSame('processing', $fee->status);
    }

    public function test_it_ignores_a_malformed_webhook_without_crashing(): void
    {
        $this->postJson(route('webhooks.marzpay'), ['garbage' => true])->assertOk();
    }
}
