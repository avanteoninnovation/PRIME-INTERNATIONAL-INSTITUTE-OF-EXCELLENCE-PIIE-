<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Hardening — the mobile pay-fee deep link used to carry the user's PASSWORD in
 * the URL (/web_redirect_to_pay_fee?auth=Basic base64(email:password:timestamp)).
 * It is replaced by POST /api/payment_link → a 5-minute temporary signed URL with a
 * single-use key. These tests prove no credential ever travels in a URL, and that
 * the handoff is owner-only, tamper-proof, expiring and not replayable.
 */
class PaymentHandoffSecurityTest extends TestCase
{
    use StaffModuleTestHelper;

    private const PASSWORD = 'Sup3r-Secret-Pw!';

    private int $school;
    private User $student;
    private int $feeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        $this->school = $this->makeSchool();
        $this->student = User::factory()->create(['role_id' => 7, 'school_id' => $this->school, 'account_status' => 'active',
            'email' => 'payer@example.test', 'password' => Hash::make(self::PASSWORD)]);
        $this->feeId = $this->fee($this->student, $this->school);
    }

    private function fee(User $student, int $school): int
    {
        return (int) DB::table('student_fee_managers')->insertGetId(['title' => 'Tuition', 'total_amount' => 100, 'class_id' => 1,
            'student_id' => $student->id, 'payment_method' => 'online', 'paid_amount' => 0, 'status' => 'unpaid', 'school_id' => $school]);
    }

    private function link(?int $feeId = null)
    {
        Sanctum::actingAs($this->student);

        return $this->postJson('/api/payment_link', ['fee_id' => $feeId ?? $this->feeId]);
    }

    /** Back to an unauthenticated browser on the web guard (Sanctum::actingAs switches the default guard). */
    private function asWebGuest(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->guard('web')->logout();
    }

    /** Relative path + query of an absolute signed URL. */
    private function path(string $url): string
    {
        $parts = parse_url($url);

        return $parts['path'] . '?' . $parts['query'];
    }

    public function test_a_student_gets_a_short_lived_signed_link_without_any_credential(): void
    {
        $response = $this->link()->assertCreated()->assertJsonStructure(['url', 'expires_at']);
        $url = $response->json('url');

        $this->assertStringContainsString('/web_pay_fee/', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('expires=', $url);
        foreach ([self::PASSWORD, 'payer@example.test', urlencode('payer@example.test'), base64_encode('payer@example.test'), 'auth='] as $secret) {
            $this->assertStringNotContainsString($secret, $url);
        }
        $this->assertStringNotContainsString(self::PASSWORD, $response->getContent());
    }

    public function test_the_link_is_owner_only_and_needs_an_api_login(): void
    {
        $this->postJson('/api/payment_link', ['fee_id' => $this->feeId])->assertUnauthorized();

        $otherStudent = User::factory()->create(['role_id' => 7, 'school_id' => $this->school]);
        $this->link($this->fee($otherStudent, $this->school))->assertNotFound();

        $foreignSchool = $this->makeSchool();
        $this->link($this->fee(User::factory()->create(['role_id' => 7, 'school_id' => $foreignSchool]), $foreignSchool))->assertNotFound();

        $this->link(999999)->assertNotFound();
        Sanctum::actingAs($this->student);
        $this->postJson('/api/payment_link', [])->assertStatus(422);
        $this->postJson('/api/payment_link', ['fee_id' => 'abc'])->assertStatus(422);
    }

    public function test_the_handoff_logs_the_student_in_once_and_opens_their_payment_page(): void
    {
        $path = $this->path($this->link()->json('url'));
        $this->asWebGuest();

        $first = $this->get($path);
        $first->assertRedirect(route('student.FeePayment', $this->feeId));
        $this->assertAuthenticatedAs($this->student);
        foreach ([self::PASSWORD, 'payer@example.test'] as $secret) {
            $this->assertStringNotContainsString($secret, (string) $first->headers->get('Location'));
        }

        // Browser refresh of the payment page keeps working on the web session.
        $this->get(route('student.FeePayment', $this->feeId))->assertOk();
        $this->get(route('student.FeePayment', $this->feeId))->assertOk();

        // Replay of the same link: no second login.
        $this->asWebGuest();
        $this->get($path)->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_tampered_and_expired_links_are_rejected(): void
    {
        $path = $this->path($this->link()->json('url'));
        $this->asWebGuest();

        $tampered = preg_replace('/signature=[a-f0-9]+/', 'signature=' . str_repeat('0', 64), $path);
        $this->get($tampered)->assertForbidden();
        $this->get(preg_replace('#/web_pay_fee/[A-Za-z0-9]+#', '/web_pay_fee/' . str_repeat('A', 64), $path))->assertForbidden();
        $this->assertGuest();

        $this->travel(6)->minutes();
        $this->get($path)->assertForbidden();
        $this->assertGuest();
    }

    public function test_a_fee_reassigned_after_issue_is_not_opened(): void
    {
        $path = $this->path($this->link()->json('url'));
        DB::table('student_fee_managers')->where('id', $this->feeId)->update(['student_id' => User::factory()->create(['role_id' => 7, 'school_id' => $this->school])->id]);
        $this->asWebGuest();

        $this->get($path)->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_the_legacy_password_link_never_authenticates_and_nothing_leaks(): void
    {
        Log::spy();
        $legacy = '/web_redirect_to_pay_fee?auth=' . urlencode('Basic ' . base64_encode('payer@example.test:' . self::PASSWORD . ':' . time())) . '&fee_id=' . $this->feeId;

        $response = $this->get($legacy);

        $response->assertRedirect(route('login'))->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringNotContainsString(self::PASSWORD, (string) $response->headers->get('Location'));
        $this->assertStringNotContainsString(self::PASSWORD, (string) $response->getContent());
        Log::shouldNotHaveReceived('info');
        foreach (['debug', 'warning', 'error', 'notice'] as $level) {
            Log::shouldNotHaveReceived($level, [\Mockery::on(fn ($m) => str_contains((string) $m, self::PASSWORD)), \Mockery::any()]);
        }
    }
}
