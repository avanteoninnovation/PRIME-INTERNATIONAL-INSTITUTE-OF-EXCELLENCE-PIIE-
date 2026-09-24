<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Stability repair H2 — Settings → Permissions (legacy role matrix).
 *
 * The real roles table (migration 2022_05_16_051816) is a global list of system
 * roles: role_id, name, timestamps — no school_id. The controller filtered it by
 * a school_id that only the hand-written test schema had, so the page and its
 * save crashed with HTTP 500 on every real database. These tests rebuild roles
 * from the REAL migration.
 *
 * role_perm_{role_id} (global_settings) is, by the existing architecture, a
 * platform-wide list (RBAC Phase 3A/3B); the page keeps its tested semantics.
 */
class LegacyRolePermissionsPageTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $schoolA;
    private int $schoolB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        (require base_path('database/migrations/2026_09_23_000003_create_rbac_tables.php'))->up();
        Schema::drop('roles');
        (require base_path('database/migrations/2022_05_16_051816_create_roles_table.php'))->up();
        foreach ([1 => 'superadmin', 2 => 'admin', 3 => 'teacher', 4 => 'accountant', 5 => 'librarian', 6 => 'parent', 7 => 'student'] as $id => $name) {
            DB::table('roles')->insert(['role_id' => $id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->schoolA = $this->makeSchool();
        $this->schoolB = $this->makeSchool();
    }

    private function user(int $role, int $school): User
    {
        return User::factory()->create(['role_id' => $role, 'school_id' => $school, 'account_status' => 'active']);
    }

    public function test_the_real_roles_table_has_no_school_column(): void
    {
        $this->assertFalse(Schema::hasColumn('roles', 'school_id'));
    }

    public function test_school_admin_page_renders_the_system_roles(): void
    {
        DB::table('global_settings')->insert(['key' => 'role_perm_3', 'value' => json_encode(['view_online_exams'])]);

        $html = $this->actingAs($this->user(2, $this->schoolA))->get(route('admin.settings.permissions'))
            ->assertOk()->assertSee('Legacy role matrix')->getContent();

        // The matrix really lists the roles (SQLite silently returned none for the old school_id filter).
        foreach ([3, 4, 5] as $roleId) {
            $this->assertStringContainsString('name="perms[' . $roleId . '][]"', $html, "role {$roleId} row");
        }
        $this->assertMatchesRegularExpression('/name="perms\[3\]\[\]" value="view_online_exams"\s+checked/', $html, 'saved permission shown');
    }

    public function test_school_admin_can_still_save_and_only_role_perm_keys_of_real_roles_are_written(): void
    {
        $this->actingAs($this->user(2, $this->schoolA))
            ->post(route('admin.settings.permissions.save'), ['perms' => [3 => ['view_online_exams', 'mark_exam_answers'], 999 => ['everything']]])
            ->assertRedirect();

        $this->assertSame(['view_online_exams', 'mark_exam_answers'], json_decode(DB::table('global_settings')->where('key', 'role_perm_3')->value('value'), true));
        $this->assertFalse(DB::table('global_settings')->where('key', 'role_perm_999')->exists(), 'no setting for a role that does not exist');
        $this->assertSame(0, DB::table('global_settings')->where('key', 'not like', 'role_perm_%')->count());
    }

    public function test_staff_cannot_open_or_save_the_matrix(): void
    {
        DB::table('global_settings')->insert(['key' => 'role_perm_3', 'value' => json_encode(['view_online_exams'])]);

        foreach ([3, 4, 5] as $role) {
            $staff = $this->user($role, $this->schoolA);
            $this->assertSame(403, $this->actingAs($staff)->get(route('admin.settings.permissions'))->getStatusCode(), "role {$role}");
            $this->actingAs($staff)->post(route('admin.settings.permissions.save'), ['perms' => [3 => ['publish_online_exams']]]);
        }
        $this->assertSame(['view_online_exams'], json_decode(DB::table('global_settings')->where('key', 'role_perm_3')->value('value'), true));
    }

    public function test_the_page_exposes_no_other_schools_data(): void
    {
        User::factory()->create(['role_id' => 3, 'school_id' => $this->schoolB, 'name' => 'School B Secret Teacher']);
        DB::table('staff_roles')->insert(['school_id' => $this->schoolB, 'name' => 'School B Custom Role', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($this->user(2, $this->schoolA))->get(route('admin.settings.permissions'))
            ->assertOk()->assertDontSee('School B Secret Teacher')->assertDontSee('School B Custom Role');
    }
}
