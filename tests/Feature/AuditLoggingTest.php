<?php

namespace Tests\Feature;

use App\Http\Middleware\TrackModuleAccess;
use App\Listeners\Audit\LogFailedLogin;
use App\Listeners\Audit\LogLogout;
use App\Models\AuditLog;
use App\Models\Classes;
use App\Support\Audit\ClientInfo;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\AuditTestHelper;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use AuditTestHelper;

    private const DESKTOP_UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';
    private const ANDROID_UA = 'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Mobile Safari/537.36';
    private const IPHONE_UA  = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
    private const IPAD_UA    = 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAuditTestSchema();
        AuditLog::$loggedThisRequest = false;
    }

    private function bindRequest(string $ip, string $userAgent, string $method = 'GET', string $uri = '/dashboard'): void
    {
        $request = Request::create($uri, $method, [], [], [], [
            'REMOTE_ADDR'     => $ip,
            'HTTP_USER_AGENT' => $userAgent,
        ]);
        $this->app->instance('request', $request);
    }

    // ── ClientInfo (in-house UA parser) ─────────────────────────────────────

    public function test_client_info_detects_desktop_windows_chrome(): void
    {
        $info = ClientInfo::fromRequest(Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => self::DESKTOP_UA]));
        $this->assertSame('Desktop', $info['device_type']);
        $this->assertSame('Chrome', $info['browser']);
        $this->assertSame('Windows 10/11', $info['platform']);
    }

    public function test_client_info_detects_android_phone(): void
    {
        $info = ClientInfo::fromRequest(Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => self::ANDROID_UA]));
        $this->assertSame('Mobile', $info['device_type']);
        $this->assertSame('Android', $info['platform']);
    }

    public function test_client_info_detects_iphone(): void
    {
        $info = ClientInfo::fromRequest(Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => self::IPHONE_UA]));
        $this->assertSame('Mobile', $info['device_type']);
        $this->assertSame('iOS', $info['platform']);
    }

    public function test_client_info_detects_ipad_as_tablet_not_mobile(): void
    {
        $info = ClientInfo::fromRequest(Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => self::IPAD_UA]));
        $this->assertSame('Tablet', $info['device_type']);
        $this->assertSame('iOS', $info['platform']);
    }

    // ── Authentication events ────────────────────────────────────────────────

    public function test_successful_login_is_logged_with_ip_and_device(): void
    {
        $user = $this->makeUser(2, 1);
        $this->bindRequest('203.0.113.10', self::ANDROID_UA);

        // Auth::login() fires the real Login event through the guard (the
        // same path production traffic takes), exercising the actual
        // EventServiceProvider wiring end-to-end rather than hand-building
        // the event — auth()->user() must already be set when the listener
        // reads it, exactly as happens on a real request.
        Auth::login($user);

        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'LOGIN',
            'user_id'     => $user->id,
            'school_id'   => 1,
            'ip_address'  => '203.0.113.10',
            'device_type' => 'Mobile',
            'platform'    => 'Android',
            'status'      => 'success',
        ]);
    }

    public function test_failed_login_is_logged_without_password(): void
    {
        $this->bindRequest('198.51.100.20', self::DESKTOP_UA);

        (new LogFailedLogin())->handle(new Failed('web', null, [
            'email'    => 'nobody@example.com',
            'password' => 'super-secret-password',
        ]));

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'LOGIN_FAILED',
            'status'     => 'failed',
            'ip_address' => '198.51.100.20',
        ]);

        $row = AuditLog::where('action', 'LOGIN_FAILED')->firstOrFail();
        $this->assertStringContainsString('nobody@example.com', $row->description);
        $this->assertStringNotContainsString('super-secret-password', $row->description);
        $this->assertStringNotContainsString('super-secret-password', json_encode($row->toArray()));
    }

    public function test_logout_is_logged_using_event_payload_user(): void
    {
        $user = $this->makeUser(3, 1);
        $this->bindRequest('203.0.113.55', self::DESKTOP_UA);

        (new LogLogout())->handle(new Logout('web', $user));

        $this->assertDatabaseHas('audit_logs', [
            'action'    => 'LOGOUT',
            'user_id'   => $user->id,
            'school_id' => 1,
            'status'    => 'success',
        ]);
    }

    // ── Automatic CRUD tracking (AuditableObserver) ─────────────────────────

    public function test_observer_logs_create_update_delete_with_old_and_new_values(): void
    {
        $admin = $this->makeUser(2, 1);
        Auth::login($admin);
        $this->bindRequest('203.0.113.99', self::DESKTOP_UA);

        // Classes is already auto-observed globally by AuditServiceProvider
        // (registered in config/app.php) — no manual observe() call needed.
        $class = Classes::create(['name' => 'Grade 10', 'school_id' => 1]);
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'CREATE',
            'record_type' => Classes::class,
            'record_id'   => $class->id,
        ]);

        $class->update(['name' => 'Grade 11']);
        $updateRow = AuditLog::where('action', 'UPDATE')->where('record_id', $class->id)->firstOrFail();
        $this->assertSame(['name' => 'Grade 10'], $updateRow->old_values);
        $this->assertSame(['name' => 'Grade 11'], $updateRow->new_values);

        $countBeforeTouch = AuditLog::count();
        $class->touch(); // only updated_at changes — must NOT create a new row
        $this->assertSame($countBeforeTouch, AuditLog::count());

        $class->delete();
        $this->assertDatabaseHas('audit_logs', [
            'action'      => 'DELETE',
            'record_type' => Classes::class,
            'record_id'   => $class->id,
        ]);
    }

    // ── Automatic module/page access tracking ───────────────────────────────

    public function test_middleware_logs_meaningful_page_access_for_authenticated_user(): void
    {
        $user = $this->makeUser(2, 1);
        Auth::login($user);

        Route::get('/admin/programmes', fn() => 'ok')->name('admin.programmes.index');
        $request = Request::create('/admin/programmes', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.5', 'HTTP_USER_AGENT' => self::DESKTOP_UA,
        ]);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $this->app->instance('request', $request);

        // A real HTTP request starts with the flag false — reset it here
        // since, within one test method, makeUser()/Auth::login() above
        // already logged entries of their own (a separate prior request in
        // production would not share this test's static state).
        AuditLog::$loggedThisRequest = false;

        (new TrackModuleAccess())->terminate($request, response('ok', 200));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'VIEW',
            'module' => 'Programmes',
        ]);
    }

    public function test_middleware_tags_export_routes_distinctly(): void
    {
        $user = $this->makeUser(2, 1);
        Auth::login($user);

        Route::get('/admin/assets/export', fn() => 'ok')->name('admin.assets.export');
        $request = Request::create('/admin/assets/export', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.6', 'HTTP_USER_AGENT' => self::DESKTOP_UA,
        ]);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $this->app->instance('request', $request);
        AuditLog::$loggedThisRequest = false;

        (new TrackModuleAccess())->terminate($request, response('ok', 200));

        $this->assertDatabaseHas('audit_logs', ['action' => 'EXPORT', 'module' => 'Assets']);
    }

    public function test_middleware_skips_ajax_requests_and_guests(): void
    {
        Route::get('/admin/programmes', fn() => 'ok')->name('admin.programmes.index');
        $request = Request::create('/admin/programmes', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.7', 'HTTP_USER_AGENT' => self::DESKTOP_UA,
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $this->app->instance('request', $request);

        // Guest — no user logged in at all.
        (new TrackModuleAccess())->terminate($request, response('ok', 200));
        $this->assertSame(0, AuditLog::count());
    }

    public function test_middleware_does_not_duplicate_when_a_specific_action_was_already_logged(): void
    {
        $user = $this->makeUser(2, 1);
        Auth::login($user);

        Route::get('/admin/assets/delete/1', fn() => 'ok')->name('admin.assets.destroy');
        $request = Request::create('/admin/assets/delete/1', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.8', 'HTTP_USER_AGENT' => self::DESKTOP_UA,
        ]);
        $request->setRouteResolver(fn() => Route::getRoutes()->match($request));
        $this->app->instance('request', $request);

        // Simulate the controller having already recorded a specific DELETE
        // entry for this same request (legacy GET-based delete routes set
        // $loggedThisRequest via AuditLog::record()) — the middleware must
        // then add nothing further for this request.
        AuditLog::record('delete', 'Assets', 'Deleted asset: Projector');
        $countBeforeTerminate = AuditLog::count();

        (new TrackModuleAccess())->terminate($request, response('ok', 200));

        $this->assertSame($countBeforeTerminate, AuditLog::count(), 'middleware must not add a duplicate VIEW entry after a specific action was already logged');
    }
}
