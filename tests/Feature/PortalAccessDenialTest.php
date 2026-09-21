<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\StudentMiddleware;
use App\Http\Middleware\TeacherMiddleware;
use App\Http\Middleware\WardenMiddleware;
use App\Models\User;
use App\Support\Permissions\RoleHomeRoute;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the fix for every role middleware (AdminMiddleware,
 * StudentMiddleware, TeacherMiddleware, ... 18 total) collapsing "logged in
 * but the wrong role for this route" into the exact same "your account has
 * been disabled" redirect and message as an actually disabled account. The
 * wrong-role case — by far the most common real-world trigger, e.g. a
 * student following a stale admin link — now sends the user to their own
 * dashboard with an accurate message instead.
 */
class PortalAccessDenialTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();

        \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->string('staff_status', 20)->nullable();
        });
    }

    private function fakeRequest(string $path, string $routeName): Request
    {
        $route = (new Route('GET', $path, []))->name($routeName);
        $request = Request::create('/' . $path, 'GET');
        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    public function test_role_home_route_maps_every_common_role_to_a_resolvable_route(): void
    {
        $cases = [7 => 'student.dashboard', 3 => 'teacher.dashboard', 2 => 'admin.dashboard', 6 => 'parent.dashboard'];

        foreach ($cases as $roleId => $expectedRoute) {
            $user = User::factory()->make(['role_id' => $roleId]);
            $this->assertSame($expectedRoute, RoleHomeRoute::name($user));
            // Must actually resolve — a typo here would only surface at
            // runtime for whichever role hits it first, in production.
            $this->assertTrue(RouteFacade::has($expectedRoute), "Route [{$expectedRoute}] must be registered.");
        }
    }

    public function test_a_student_hitting_an_admin_route_is_sent_to_their_own_dashboard_not_the_disabled_page(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId, 'account_status' => 'active']);
        $this->actingAs($student);

        $request = $this->fakeRequest('admin/dashboard', 'admin.dashboard');
        $response = (new AdminMiddleware())->handle($request, fn ($req) => new Response('reached'));

        $this->assertNotSame('reached', $response->getContent());
        $this->assertSame(route('student.dashboard'), $response->headers->get('Location'));
        $this->assertStringNotContainsString('disabled', session('error'));
        $this->assertStringContainsString('do not have permission', session('error'));
    }

    public function test_a_genuinely_disabled_admin_still_sees_the_accurate_disabled_message(): void
    {
        $schoolId = $this->makeSchool();
        $admin = User::factory()->create(['role_id' => 2, 'school_id' => $schoolId, 'account_status' => 'disable']);
        $this->actingAs($admin);

        $request = $this->fakeRequest('admin/dashboard', 'admin.dashboard');
        $response = (new AdminMiddleware())->handle($request, fn ($req) => new Response('reached'));

        $this->assertNotSame('reached', $response->getContent());
        $this->assertSame(route('admin.account_disableview'), $response->headers->get('Location'));
        $this->assertStringContainsString('has been disabled', session('error'));
    }

    public function test_a_suspended_teacher_sees_a_restricted_message_not_a_wrong_role_one(): void
    {
        $schoolId = $this->makeSchool();
        $teacher = User::factory()->create([
            'role_id' => 3, 'school_id' => $schoolId, 'account_status' => 'active', 'staff_status' => 'suspended',
        ]);
        $this->actingAs($teacher);

        $request = $this->fakeRequest('teacher/dashboard', 'teacher.dashboard');
        $response = (new TeacherMiddleware())->handle($request, fn ($req) => new Response('reached'));

        $this->assertNotSame('reached', $response->getContent());
        $this->assertSame(route('teacher.account_disable'), $response->headers->get('Location'));
        $this->assertStringContainsString('restricted', session('error'));
    }

    public function test_a_student_hitting_a_teacher_route_is_sent_home_correctly(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId, 'account_status' => 'active']);
        $this->actingAs($student);

        $request = $this->fakeRequest('teacher/dashboard', 'teacher.dashboard');
        $response = (new TeacherMiddleware())->handle($request, fn ($req) => new Response('reached'));

        $this->assertSame(route('student.dashboard'), $response->headers->get('Location'));
    }

    public function test_a_valid_student_still_reaches_their_own_dashboard(): void
    {
        $schoolId = $this->makeSchool();
        $student = User::factory()->create(['role_id' => 7, 'school_id' => $schoolId, 'account_status' => 'active']);
        $this->actingAs($student);

        $request = $this->fakeRequest('student/dashboard', 'student.dashboard');
        $response = (new StudentMiddleware())->handle($request, fn ($req) => new Response('reached'));

        $this->assertSame('reached', $response->getContent());
    }

    public function test_wardens_own_disabled_route_now_resolves_without_a_missing_route_crash(): void
    {
        // WardenMiddleware referenced route('warden.account_disable') with
        // no route ever registered for it — any non-warden hitting a warden
        // route, or a genuinely disabled warden, threw a hard
        // RouteNotFoundException (a 500) instead of showing any message.
        $this->assertTrue(RouteFacade::has('warden.account_disable'));

        $schoolId = $this->makeSchool();
        $warden = User::factory()->create(['role_id' => 10, 'school_id' => $schoolId, 'account_status' => 'disable']);
        $this->actingAs($warden);

        $request = $this->fakeRequest('warden/dashboard', 'warden.dashboard');
        $response = (new WardenMiddleware())->handle($request, fn ($req) => new Response('reached'));

        $this->assertNotSame('reached', $response->getContent());
        $this->assertSame(route('warden.account_disable'), $response->headers->get('Location'));
    }
}
