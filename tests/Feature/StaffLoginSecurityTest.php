<?php

namespace Tests\Feature;

use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * app\Http\Controllers\Auth\LoginController::login() fully overrides
 * AuthenticatesUsers's default login() method (custom role-based redirect
 * logic), which means Laravel's usual ThrottlesLogins lockout never
 * engages — this route had zero brute-force protection until a
 * throttle:5,1 middleware was added directly in the controller
 * constructor. Unlike applicant/login (already throttled), this one gates
 * every staff role including Super Admin, so it's worth a permanent
 * regression test rather than a one-off check.
 */
class StaffLoginSecurityTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $schoolId = $this->makeSchool();
        $this->makeAdminUser($schoolId);

        $statuses = [];
        for ($i = 1; $i <= 6; $i++) {
            $response = $this->post('/login', ['email' => 'nobody@nowhere.test', 'password' => 'wrong']);
            $statuses[] = $response->getStatusCode();
        }

        // First 5 are normal failed-login redirects; the 6th is throttled.
        $this->assertSame([302, 302, 302, 302, 302, 429], $statuses);
    }
}
