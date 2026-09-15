<?php

namespace Tests\Feature;

use App\Models\PaymentMethods;
use App\Support\Payments\MarzPayService;
use Illuminate\Support\Facades\Http;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class MarzPayServiceTest extends TestCase
{
    use AdmissionsTestHelper;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
        $this->schoolId = $this->makeSchool();
    }

    private function configureMarzpay(array $overrides = []): void
    {
        PaymentMethods::create(array_merge([
            'name'         => 'marzpay',
            'image'        => 'marzpay.png',
            'status'       => 1,
            'mode'         => 'test',
            'school_id'    => $this->schoolId,
            'payment_keys' => json_encode([
                'sandbox_api_key'    => 'sandbox_key_123',
                'sandbox_api_secret' => 'sandbox_secret_456',
                'live_api_key'       => 'live_key_123',
                'live_api_secret'    => 'live_secret_456',
                'country'            => 'UG',
            ]),
        ], $overrides));
    }

    public function test_it_reports_not_configured_when_no_row_exists(): void
    {
        $this->assertFalse(MarzPayService::isConfigured($this->schoolId));
    }

    public function test_it_reports_not_configured_when_row_is_disabled(): void
    {
        $this->configureMarzpay(['status' => 0]);

        $this->assertFalse(MarzPayService::isConfigured($this->schoolId));
    }

    public function test_it_is_configured_once_sandbox_keys_are_saved(): void
    {
        $this->configureMarzpay();

        $this->assertTrue(MarzPayService::isConfigured($this->schoolId));
    }

    public function test_initiate_mobile_money_collection_sends_basic_auth_and_correct_payload_shape(): void
    {
        $this->configureMarzpay();

        Http::fake([
            'wallet.wearemarz.com/api/v1/collect-money' => Http::response([
                'status' => 'success',
                'data'   => [
                    'transaction' => ['uuid' => 'txn-uuid-123', 'status' => 'processing'],
                ],
            ], 201),
        ]);

        $result = MarzPayService::initiateMobileMoneyCollection(
            $this->schoolId,
            '+256712345678',
            5000.0,
            'ref-abc-123',
            'Tuition fee: Term 1',
            'https://example.test/webhooks/marzpay',
            ['context' => 'tuition', 'context_id' => 42]
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('txn-uuid-123', $result['transaction_uuid']);

        Http::assertSent(function ($request) {
            $authHeader = $request->header('Authorization')[0] ?? '';
            $expected = 'Basic ' . base64_encode('sandbox_key_123:sandbox_secret_456');

            return $request->url() === MarzPayService::BASE_URL . '/collect-money'
                && $authHeader === $expected
                && $request['amount'] === 5000.0
                && $request['phone_number'] === '+256712345678'
                && $request['country'] === 'UG'
                // Metadata must be an array of single-field objects, not one flat object.
                && $request['metadata'] === [['context' => 'tuition'], ['context_id' => 42]];
        });
    }

    public function test_initiate_mobile_money_collection_fails_gracefully_when_marzpay_rejects_the_request(): void
    {
        $this->configureMarzpay();

        Http::fake([
            'wallet.wearemarz.com/api/v1/collect-money' => Http::response([
                'status'  => 'error',
                'message' => 'Reference already used.',
            ], 422),
        ]);

        $result = MarzPayService::initiateMobileMoneyCollection(
            $this->schoolId, '+256712345678', 5000.0, 'dupe-ref', 'desc', null, ['context' => 'tuition', 'context_id' => 1]
        );

        $this->assertFalse($result['ok']);
        $this->assertSame('Reference already used.', $result['error']);
    }

    public function test_initiate_mobile_money_collection_fails_when_not_configured(): void
    {
        $result = MarzPayService::initiateMobileMoneyCollection(
            $this->schoolId, '+256712345678', 5000.0, 'ref', 'desc', null, ['context' => 'tuition', 'context_id' => 1]
        );

        $this->assertFalse($result['ok']);
        $this->assertNull($result['transaction_uuid']);
    }

    public function test_get_collection_status_returns_the_transaction_data(): void
    {
        $this->configureMarzpay();

        Http::fake([
            'wallet.wearemarz.com/api/v1/collect-money/txn-uuid-123' => Http::response([
                'status' => 'success',
                'data'   => ['transaction' => ['uuid' => 'txn-uuid-123', 'status' => 'successful']],
            ], 200),
        ]);

        $data = MarzPayService::getCollectionStatus('txn-uuid-123', $this->schoolId);

        $this->assertSame('successful', $data['transaction']['status']);
    }

    public function test_get_collection_status_returns_null_when_not_configured(): void
    {
        $this->assertNull(MarzPayService::getCollectionStatus('txn-uuid-123', $this->schoolId));
    }
}
