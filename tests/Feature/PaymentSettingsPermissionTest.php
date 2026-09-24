<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Pre-RBAC cleanup B — school payment-gateway settings (admin/settings/payment).
 *
 * AdminMiddleware admits every staff role to admin/*, and admin_permission
 * only restricts users who have a per-user menu_permission list, so the POST
 * (which had no permission middleware at all) and the GET (which shows every
 * gateway's secret keys) were open to teachers, librarians, HR, etc.
 *
 * Both are now limited with the current mechanism: the role must be able to
 * see the "settings" section (role_can_see() over get_role_nav_permissions():
 * School Admin and Director today), and admin_permission still applies a
 * per-user menu restriction. Tenant scoping of update_id is Phase 2G.
 */
class PaymentSettingsPermissionTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $schoolA;
    private int $schoolB;
    private int $gatewayA;
    private int $gatewayB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        $this->withoutMiddleware(VerifyCsrfToken::class);

        foreach (['name', 'image', 'status', 'mode', 'payment_keys', 'school_id'] as $c) {
            if (!Schema::hasColumn('payment_methods', $c)) {
                Schema::table('payment_methods', fn (Blueprint $t) => $t->text($c)->nullable());
            }
        }
        foreach (['school_currency', 'currency_position', 'off_pay_ins_text', 'off_pay_ins_file'] as $c) {
            if (!Schema::hasColumn('schools', $c)) {
                Schema::table('schools', fn (Blueprint $t) => $t->text($c)->nullable());
            }
        }

        $this->schoolA = $this->makeSchool(['title' => 'School A', 'status' => 1]);
        $this->schoolB = $this->makeSchool(['title' => 'School B', 'status' => 1]);
        $this->gatewayA = $this->gateway($this->schoolA, 'SECRET-OF-A');
        $this->gatewayB = $this->gateway($this->schoolB, 'SECRET-OF-B');
    }

    private function gateway(int $school, string $secret): int
    {
        return (int) DB::table('payment_methods')->insertGetId([
            'name' => 'stripe', 'status' => 1, 'mode' => 'live', 'school_id' => $school,
            'payment_keys' => json_encode(['test_key' => 'x', 'test_secret_key' => 'x', 'public_live_key' => 'pk', 'secret_live_key' => $secret]),
        ]);
    }

    private function user(int $role, ?int $school = null, array $extra = []): User
    {
        return User::factory()->create($extra + ['role_id' => $role, 'school_id' => $school ?? $this->schoolA, 'account_status' => 'active']);
    }

    private function stripePayload(int $updateId, string $secret = 'ATTACKER'): array
    {
        return ['method' => 'stripe', 'update_id' => $updateId, 'status' => 1, 'mode' => 'live',
            'test_key' => 'x', 'test_secret_key' => 'x', 'public_live_key' => 'pk', 'secret_live_key' => $secret];
    }

    private function keysOf(int $gateway): string
    {
        return (string) DB::table('payment_methods')->where('id', $gateway)->value('payment_keys');
    }

    public function test_staff_roles_without_settings_access_cannot_change_payment_settings(): void
    {
        // Teacher, Accountant, Librarian, Warden, Admissions Officer, HR Manager, Procurement, Store Keeper, Receptionist, Examinations Officer
        foreach ([3, 4, 5, 10, 13, 15, 16, 17, 18, 19] as $role) {
            $actor = $this->user($role);

            $this->actingAs($actor)->post(route('admin.settings.payment_post'), $this->stripePayload($this->gatewayA))->assertForbidden();
            $this->actingAs($actor)->post(route('admin.settings.payment_post'), ['method' => 'currency', 'update_id' => $this->schoolA, 'school_currency' => 'EUR', 'currency_position' => 'right'])->assertForbidden();
            $this->actingAs($actor)->post(route('admin.settings.payment_post'), ['off_pay_ins_text' => 'Pay to attacker account'])->assertForbidden();

            $this->assertStringContainsString('SECRET-OF-A', $this->keysOf($this->gatewayA), "role {$role} changed gateway keys");
            $this->assertNull(DB::table('schools')->where('id', $this->schoolA)->value('school_currency'), "role {$role} changed currency");
            $this->assertNull(DB::table('schools')->where('id', $this->schoolA)->value('off_pay_ins_text'), "role {$role} changed offline instructions");
        }
    }

    public function test_staff_roles_without_settings_access_cannot_read_gateway_secrets(): void
    {
        foreach ([3, 4, 5, 15] as $role) {
            $response = $this->actingAs($this->user($role))->get(route('admin.settings.payment'));
            $response->assertForbidden();
            $this->assertStringNotContainsString('SECRET-OF-A', $response->getContent());
        }
    }

    public function test_school_admin_and_director_can_still_manage_their_own_payment_settings(): void
    {
        foreach ([2 => 'ADMIN-KEY', 14 => 'DIRECTOR-KEY'] as $role => $secret) {
            $actor = $this->user($role);

            $this->actingAs($actor)->post(route('admin.settings.payment_post'), $this->stripePayload($this->gatewayA, $secret))->assertRedirect();
            $this->assertStringContainsString($secret, $this->keysOf($this->gatewayA), "role {$role} could not update its gateway");
        }

        $this->actingAs($this->user(2))->post(route('admin.settings.payment_post'), ['method' => 'currency', 'update_id' => $this->schoolA, 'school_currency' => 'EUR', 'currency_position' => 'right']);
        $this->assertSame('EUR', DB::table('schools')->where('id', $this->schoolA)->value('school_currency'));
    }

    public function test_a_permitted_admin_still_cannot_touch_another_schools_settings(): void
    {
        $admin = $this->user(2);

        $this->actingAs($admin)->post(route('admin.settings.payment_post'), $this->stripePayload($this->gatewayB))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.settings.payment_post'), ['method' => 'currency', 'update_id' => $this->schoolB, 'school_currency' => 'EUR', 'currency_position' => 'right'])->assertNotFound();

        $this->assertStringContainsString('SECRET-OF-B', $this->keysOf($this->gatewayB));
        $this->assertEquals($this->schoolB, DB::table('payment_methods')->where('id', $this->gatewayB)->value('school_id'));
        $this->assertNull(DB::table('schools')->where('id', $this->schoolB)->value('school_currency'));
    }

    public function test_a_per_user_menu_restriction_still_applies_to_the_post(): void
    {
        // A School Admin whose menu_permission list does not include payment settings (admin_permission).
        $restricted = $this->user(2, null, ['menu_permission' => json_encode(['admin.dashboard'])]);

        $this->actingAs($restricted)->post(route('admin.settings.payment_post'), $this->stripePayload($this->gatewayA));

        $this->assertStringContainsString('SECRET-OF-A', $this->keysOf($this->gatewayA));

        // …while one whose list does include payment settings can still save them.
        $granted = $this->user(2, null, ['menu_permission' => json_encode(['admin.dashboard', 'admin.settings.payment'])]);
        $this->actingAs($granted)->post(route('admin.settings.payment_post'), $this->stripePayload($this->gatewayA, 'GRANTED-KEY'));
        $this->assertStringContainsString('GRANTED-KEY', $this->keysOf($this->gatewayA));
    }

    public function test_super_admin_is_still_kept_out_of_school_payment_settings(): void
    {
        $superAdmin = $this->user(1, null, ['school_id' => null]);

        $response = $this->actingAs($superAdmin)->post(route('admin.settings.payment_post'), $this->stripePayload($this->gatewayA));

        $this->assertTrue($response->isRedirect(), 'Super Admin is redirected away from the school admin portal, as before');
        $this->assertStringContainsString('SECRET-OF-A', $this->keysOf($this->gatewayA));
    }
}
