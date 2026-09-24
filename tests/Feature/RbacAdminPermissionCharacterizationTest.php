<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminPermission;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * RBAC Phase 1 — CHARACTERIZATION, not specification.
 *
 * Pins AdminPermission's handling of every menu_permission shape the Phase 0
 * audit found. The meanings are inconsistent across the app and are
 * DELIBERATELY not normalized here:
 *
 *   NULL / ''  -> allow every admin_permission route (fail-open)
 *   'null'     -> deny every admin_permission route
 *   '[]'       -> deny every admin_permission route
 *   key listed -> allow that route (+ its mapped child routes)
 *   not listed -> deny (redirect back)
 *
 * (Contrast: LiveClassPolicy treats 'null' as allow; OnlineExamPermissionService
 * treats every empty shape as "no grant, fall through" — see the sibling
 * characterization tests.)
 */
class RbacAdminPermissionCharacterizationTest extends TestCase
{
    private int $probe = 0;

    /** Registers a throwaway route carrying $routeName behind AdminPermission and hits it. */
    private function statusFor(?string $menuPermission, string $routeName): int
    {
        $path = '/_rbac-admin-permission-probe-' . (++$this->probe);
        Route::middleware(AdminPermission::class)
            ->get($path, fn () => response('ok', 200))
            ->name($routeName);

        $user = User::factory()->make(['role_id' => 2, 'menu_permission' => $menuPermission]);

        return $this->actingAs($user)->get($path)->getStatusCode();
    }

    public function test_null_menu_permission_allows_everything(): void
    {
        $this->assertSame(200, $this->statusFor(null, 'admin.teacher'));
        $this->assertSame(200, $this->statusFor(null, 'admin.exam_category.delete'));
    }

    public function test_empty_string_menu_permission_allows_everything(): void
    {
        $this->assertSame(200, $this->statusFor('', 'admin.teacher'));
    }

    public function test_string_null_menu_permission_denies_everything(): void
    {
        $this->assertSame(302, $this->statusFor('null', 'admin.teacher'));
    }

    public function test_empty_json_array_menu_permission_denies_everything(): void
    {
        $this->assertSame(302, $this->statusFor('[]', 'admin.teacher'));
    }

    public function test_listed_permission_allows_and_unlisted_permission_denies(): void
    {
        $menu = json_encode(['admin.teacher']);

        $this->assertSame(200, $this->statusFor($menu, 'admin.teacher'));
        $this->assertSame(302, $this->statusFor($menu, 'admin.librarian'));
    }

    public function test_parent_permission_covers_its_mapped_child_routes(): void
    {
        $menu = json_encode(['admin.exam_category']);

        $this->assertSame(200, $this->statusFor($menu, 'admin.exam_category'));
        $this->assertSame(200, $this->statusFor($menu, 'admin.exam_category.open_modal'));
        $this->assertSame(200, $this->statusFor($menu, 'admin.create.exam_category'));
        // A child of a different module is still denied.
        $this->assertSame(302, $this->statusFor($menu, 'admin.grade.delete'));
    }

    public function test_child_permission_alone_does_not_cover_its_parent_or_siblings(): void
    {
        $menu = json_encode(['admin.exam_category.open_modal']);

        $this->assertSame(200, $this->statusFor($menu, 'admin.exam_category.open_modal'));
        $this->assertSame(302, $this->statusFor($menu, 'admin.exam_category'));
        $this->assertSame(302, $this->statusFor($menu, 'admin.create.exam_category'));
    }

    public function test_online_exam_legacy_and_canonical_keys_are_interchangeable(): void
    {
        $this->assertSame(200, $this->statusFor(json_encode(['admin.online_exams']), 'admin.online_exams.index'));
        $this->assertSame(200, $this->statusFor(json_encode(['admin.online_exams']), 'admin.online_exams.edit'));
        $this->assertSame(200, $this->statusFor(json_encode(['admin.online_exams.index']), 'admin.online_exams'));
        $this->assertSame(200, $this->statusFor(json_encode(['admin.online_exams.index']), 'admin.online_exams.show'));
    }

    public function test_the_role_id_is_not_consulted_only_menu_permission_is(): void
    {
        // A NULL-permission teacher passes AdminPermission just like an admin.
        $path = '/_rbac-admin-permission-teacher';
        Route::middleware(AdminPermission::class)->get($path, fn () => response('ok', 200))->name('admin.teacher');

        $teacher = User::factory()->make(['role_id' => 3, 'menu_permission' => null]);

        $this->assertSame(200, $this->actingAs($teacher)->get($path)->getStatusCode());
    }
}
