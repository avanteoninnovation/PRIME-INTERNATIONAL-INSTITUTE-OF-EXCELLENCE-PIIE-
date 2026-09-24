<?php

namespace Tests\Feature;

use App\Http\Controllers\ParentController;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Hardening — a parent may read marks ONLY for a student linked to them through
 * the existing relationship (users.parent_id, student role, same school).
 *
 * Live endpoint: GET parent/marks/list (parent.marks_list → marks_list), loaded by
 * AJAX from the Parent → Marks page. The unrouted duplicate marks_listc() is
 * held to the same rule so it can never become a hole if it is routed later.
 */
class ParentMarksAuthorizationTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $school;
    private User $parent;
    private User $ownChild;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        $this->school = $this->makeSchool();
        $this->parent = $this->user(6, $this->school);
        $this->ownChild = $this->user(7, $this->school, ['parent_id' => $this->parent->id, 'name' => 'Own Child']);
        $class = (int) DB::table('classes')->insertGetId(['name' => 'Class One', 'school_id' => $this->school]);
        DB::table('enrollment')->insert(['user_id' => $this->ownChild->id, 'class_id' => $class, 'section_id' => 0, 'school_id' => $this->school, 'session_id' => 1]);
    }

    private function user(int $role, int $school, array $extra = []): User
    {
        return User::factory()->create($extra + ['role_id' => $role, 'school_id' => $school, 'account_status' => 'active']);
    }

    private function marks(?User $as, array $query)
    {
        $request = $as ? $this->actingAs($as) : $this;

        return $request->get(route('parent.marks_list', $query), ['X-Requested-With' => 'XMLHttpRequest']);
    }

    public function test_own_child_is_allowed(): void
    {
        $this->marks($this->parent, ['student_id' => $this->ownChild->id])->assertOk();
    }

    public function test_own_child_without_enrollment_is_allowed(): void
    {
        $newChild = $this->user(7, $this->school, ['parent_id' => $this->parent->id]);

        $this->marks($this->parent, ['student_id' => $newChild->id])->assertOk();
    }

    public function test_another_family_in_the_same_school_is_denied(): void
    {
        $otherChild = $this->user(7, $this->school, ['parent_id' => $this->user(6, $this->school)->id, 'name' => 'Other Family Child']);

        $this->marks($this->parent, ['student_id' => $otherChild->id])->assertNotFound()->assertDontSee('Other Family Child');
    }

    public function test_a_child_in_another_school_is_denied(): void
    {
        $otherSchool = $this->makeSchool();
        // Even with parent_id pointing at this parent, a student of another school is refused.
        $foreign = $this->user(7, $otherSchool, ['parent_id' => $this->parent->id]);

        $this->marks($this->parent, ['student_id' => $foreign->id])->assertNotFound();
    }

    public function test_non_student_ids_invalid_and_missing_ids_get_a_controlled_response(): void
    {
        $teacherWithParentId = $this->user(3, $this->school, ['parent_id' => $this->parent->id]);

        foreach ([['student_id' => $teacherWithParentId->id], ['student_id' => 999999], ['student_id' => 'abc'], ['student_id' => ''], []] as $query) {
            $status = $this->marks($this->parent, $query)->getStatusCode();
            $this->assertContains($status, [404, 422], json_encode($query));
        }
    }

    public function test_unauthenticated_and_other_roles_are_denied(): void
    {
        $this->assertNotSame(200, $this->marks(null, ['student_id' => $this->ownChild->id])->getStatusCode());
        foreach ([3, 7] as $role) {
            $this->assertNotSame(200, $this->marks($this->user($role, $this->school), ['student_id' => $this->ownChild->id])->getStatusCode(), "role {$role}");
        }
    }

    public function test_the_unrouted_duplicate_applies_the_same_ownership_rule(): void
    {
        $this->assertFalse(collect(Route::getRoutes()->getRoutes())->contains(fn ($r) => str_ends_with($r->getActionName(), '@marks_listc')), 'marks_listc stays unrouted');

        $otherChild = $this->user(7, $this->school, ['parent_id' => $this->user(6, $this->school)->id]);
        $this->actingAs($this->parent);
        $this->expectException(ModelNotFoundException::class);
        app(ParentController::class)->marks_listc(Request::create('/', 'GET', ['student_id' => $otherChild->id]));
    }
}
