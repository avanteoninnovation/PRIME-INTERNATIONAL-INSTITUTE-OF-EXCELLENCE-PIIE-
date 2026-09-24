<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Pre-RBAC cleanup A — the student mobile API (routes/api.php, Sanctum
 * bearer tokens). /login is the only public endpoint; every other endpoint
 * requires a token.
 *
 * The protected group used to be declared as ['middleware', ['auth:sanctum']]
 * (a list, not a 'middleware' key), so no auth middleware ran and anonymous
 * calls reached the controller. Logout also never revoked the token.
 */
class ApiAuthenticationTest extends TestCase
{
    use StaffModuleTestHelper;

    private const PROTECTED = [
        '/api/user_details', '/api/routine', '/api/attendance', '/api/subjects', '/api/syllabus_list',
        '/api/teacher_list', '/api/book_list', '/api/book_issue_list', '/api/exam_list', '/api/marks',
        '/api/profile_update', '/api/fee_list', '/api/logout', '/api/account_delete', '/api/change_profile_photo',
    ];

    private int $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        (require base_path('database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php'))->up();
        $this->school = $this->makeSchool(['title' => 'API School', 'status' => 1]);
    }

    private function student(array $overrides = []): User
    {
        return User::factory()->create($overrides + [
            'role_id' => 7, 'school_id' => $this->school, 'status' => 1,
            'email' => 'api.student.' . uniqid() . '@example.com', 'password' => bcrypt('secret-pass'),
        ]);
    }

    public function test_every_protected_endpoint_rejects_anonymous_requests(): void
    {
        $victim = $this->student();

        foreach (self::PROTECTED as $uri) {
            $this->postJson($uri, ['name' => 'Anon', 'email' => 'anon@example.com'])->assertStatus(401);
        }
        $this->getJson('/api/user')->assertStatus(401);

        $this->assertEquals(1, DB::table('users')->where('id', $victim->id)->value('status'), 'anonymous call changed an account');
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        $this->withHeader('Authorization', 'Bearer 999|not-a-real-token')->postJson('/api/user_details')->assertStatus(401);
    }

    public function test_login_stays_public_and_issues_a_token_that_authenticates(): void
    {
        $student = $this->student(['email' => 'api.login@example.com']);

        $login = $this->postJson('/api/login', ['email' => 'api.login@example.com', 'password' => 'secret-pass']);
        $login->assertStatus(201);
        $token = $login->json('token');
        $this->assertNotEmpty($token);

        $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/user')->assertOk()->assertJson(['id' => $student->id]);
        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/user_details')->assertSuccessful();
    }

    public function test_login_rejects_bad_credentials_and_non_students(): void
    {
        $this->student(['email' => 'api.bad@example.com']);
        $this->postJson('/api/login', ['email' => 'api.bad@example.com', 'password' => 'wrong'])->assertStatus(401);

        User::factory()->create(['role_id' => 3, 'school_id' => $this->school, 'status' => 1, 'email' => 'api.teacher@example.com', 'password' => bcrypt('secret-pass')]);
        $this->postJson('/api/login', ['email' => 'api.teacher@example.com', 'password' => 'secret-pass'])->assertStatus(400);
    }

    public function test_logout_revokes_the_token(): void
    {
        $student = $this->student();
        $token = $student->createToken('auth-token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/logout')->assertStatus(201);

        $this->assertSame(0, DB::table('personal_access_tokens')->where('tokenable_id', $student->id)->count(), 'token still stored after logout');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', "Bearer {$token}")->postJson('/api/user_details')->assertStatus(401);
    }
}
