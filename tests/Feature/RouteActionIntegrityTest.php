<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Pre-RBAC cleanup C/F — every registered route must point at a controller
 * method that exists.
 *
 * Five Warden hostel-room routes (create/store/edit/update/delete) were
 * declared without WardenController methods in any commit — every call was
 * a 500. The Warden room page is read-only (rooms are managed in the admin
 * portal, AdminController), so the dead routes were removed rather than new
 * Warden room management being invented. The same applied to
 * student.club.toggle_status (students do not manage club status).
 */
class RouteActionIntegrityTest extends TestCase
{
    use StaffModuleTestHelper;

    /**
     * Routes allowed to point at a missing controller method. Empty since Security
     * Phase 2I: the 14 routes the pre-RBAC audit left here were either restored
     * (student hostel application edit/update/delete) or removed as dead (the legacy
     * PayPal/Stripe/Razorpay/Paytm gateway layer, superseded by MarzPay + offline
     * payments). Any new broken route must fail this test.
     */
    private const KNOWN_BROKEN = [];

    public function test_every_route_action_resolves_to_an_existing_method(): void
    {
        $missing = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if ($action === 'Closure' || !str_contains($action, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $action);
            if (!class_exists($class) || !method_exists($class, $method)) {
                $missing[$route->uri()] = $route->uri() . ' -> ' . $action;
            }
        }

        $new = array_diff_key($missing, array_flip(self::KNOWN_BROKEN));
        $this->assertSame([], array_values($new), "Routes pointing at missing controller methods:\n" . implode("\n", $new));

        $fixed = array_diff(self::KNOWN_BROKEN, array_keys($missing));
        $this->assertSame([], array_values($fixed), 'These routes now work — remove them from KNOWN_BROKEN: ' . implode(', ', $fixed));
    }

    public function test_removed_dead_routes_are_gone(): void
    {
        foreach (['warden.hostel.create_room', 'warden.hostel.store_room', 'warden.hostel.edit_room',
                  'warden.hostel.update_room', 'warden.hostel.delete_room', 'student.club.toggle_status'] as $name) {
            $this->assertFalse(Route::has($name), "{$name} should no longer be registered");
        }
    }

    public function test_warden_room_urls_no_longer_error_and_the_room_list_still_works(): void
    {
        $this->bootStaffModuleTestSchema();
        $school = $this->makeSchool(['title' => 'Hostel School', 'status' => 1]);
        $warden = User::factory()->create(['role_id' => 10, 'school_id' => $school, 'account_status' => 'active']);

        foreach (['warden/hostel-room-create', 'warden/hostel-room-edit/1', 'warden/hostel-room-delete/1'] as $uri) {
            $this->actingAs($warden)->get($uri)->assertNotFound();
        }
        foreach (['warden/hostel-room-store', 'warden/hostel-room-update/1'] as $uri) {
            $this->actingAs($warden)->post($uri)->assertNotFound();
        }

        $this->assertTrue(Route::has('warden.hostel.room_list'));
    }
}
