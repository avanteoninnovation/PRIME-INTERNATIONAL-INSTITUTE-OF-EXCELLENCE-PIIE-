<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminPermission;
use App\Support\Permissions\OnlineExamPermissionService;
use Database\Seeders\OnlineExamPermissionSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamPermissionTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('role_id');
            $table->string('name');
            $table->unsignedBigInteger('school_id')->default(0);
        });

        DB::table('roles')->insert([
            ['role_id' => 1, 'name' => 'Super Admin', 'school_id' => 0],
            ['role_id' => 2, 'name' => 'Admin', 'school_id' => 1],
            ['role_id' => 3, 'name' => 'Teacher', 'school_id' => 1],
            ['role_id' => 7, 'name' => 'Student', 'school_id' => 1],
        ]);
    }

    public function test_online_exam_permission_seeder_merges_defaults_without_overwriting_custom_entries(): void
    {
        DB::table('global_settings')->where('key', 'role_perm_2')->update([
            'value' => json_encode(['custom_permission', '!publish_online_exams']),
            'updated_at' => now(),
        ]);

        (new OnlineExamPermissionSeeder())->run();

        $adminPermissions = json_decode((string) DB::table('global_settings')->where('key', 'role_perm_2')->value('value'), true);

        $this->assertIsArray($adminPermissions);
        $this->assertContains('custom_permission', $adminPermissions);
        $this->assertContains('view_online_exams', $adminPermissions);
        $this->assertContains('manage_exam_questions', $adminPermissions);
        $this->assertNotContains('publish_online_exams', $adminPermissions);
    }

    public function test_permission_service_accepts_legacy_and_canonical_menu_keys(): void
    {
        $admin = $this->makeUser(2, 1);
        $admin->menu_permission = json_encode(['admin.online_exams', 'admin.question_bank']);

        $service = app(OnlineExamPermissionService::class);

        $this->assertTrue($service->has($admin, 'view_online_exams'));
        $this->assertTrue($service->has($admin, 'manage_exam_questions'));
    }

    public function test_admin_permission_middleware_allows_online_exam_index_when_legacy_key_is_present(): void
    {
        $admin = $this->makeUser(2, 1);
        $admin->menu_permission = json_encode(['admin.online_exams']);
        $admin->save();

        Route::middleware(AdminPermission::class)
            ->get('/_test-online-exams', function () {
                return response('ok', 200);
            })
            ->name('admin.online_exams.index');

        $response = $this->actingAs($admin)->get('/_test-online-exams');

        $this->assertSame(200, $response->getStatusCode());
    }
}
