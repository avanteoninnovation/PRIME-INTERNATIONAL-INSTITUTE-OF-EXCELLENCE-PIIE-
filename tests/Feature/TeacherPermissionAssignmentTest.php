<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Stability repair H3 — Staff → Teacher Permission (class/section assignment).
 *
 * The real teacher_permissions table (migration 2022_07_24_134113) declares
 * marks, attendance and updated_at as NOT NULL integers with NO default. The
 * shared test schema gave them default(0), which hid that the first assignment
 * of a class/section only wrote the toggled flag — MySQL rejected the insert
 * (HTTP 500). These tests rebuild the table from the REAL migration so the
 * same constraints apply here as on MySQL.
 */
class TeacherPermissionAssignmentTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $school;
    private User $admin;
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        Schema::drop('teacher_permissions');
        (require base_path('database/migrations/2022_07_24_134113_create_teacher_permissions_table.php'))->up();

        $this->school = $this->makeSchool();
        $this->admin = $this->makeAdminUser($this->school);
        $this->teacher = User::factory()->create(['role_id' => 3, 'school_id' => $this->school]);
    }

    private function toggle(string $column, int $value = 1, array $overrides = [])
    {
        return $this->actingAs($this->admin)->get(route('admin.teacher.modify_permission', $overrides + [
            'class_id' => 5, 'section_id' => 7, 'teacher_id' => $this->teacher->id, 'column_name' => $column, 'value' => $value,
        ]));
    }

    private function row(): ?object
    {
        return DB::table('teacher_permissions')->where(['class_id' => 5, 'section_id' => 7, 'teacher_id' => $this->teacher->id])->first();
    }

    public function test_the_real_table_has_no_defaults_for_the_flags(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('teacher_permissions')->insert(['class_id' => 1, 'section_id' => 1, 'school_id' => $this->school, 'teacher_id' => 1, 'marks' => 1]);
    }

    public function test_first_marks_assignment_persists_both_flags_explicitly(): void
    {
        $this->toggle('marks')->assertOk();

        $row = $this->row();
        $this->assertNotNull($row, 'first assignment must be stored');
        $this->assertSame([1, 0], [(int) $row->marks, (int) $row->attendance]);
        $this->assertSame($this->school, (int) $row->school_id);
        $this->assertGreaterThan(0, (int) $row->updated_at);
    }

    public function test_first_attendance_assignment_persists_both_flags_explicitly(): void
    {
        $this->toggle('attendance')->assertOk();

        $row = $this->row();
        $this->assertSame([0, 1], [(int) $row->marks, (int) $row->attendance]);
    }

    public function test_later_toggles_update_only_the_chosen_flag(): void
    {
        $this->toggle('marks');
        $this->toggle('attendance');
        $this->assertSame([1, 1], [(int) $this->row()->marks, (int) $this->row()->attendance]);

        $this->toggle('marks', 0);
        $this->assertSame([0, 1], [(int) $this->row()->marks, (int) $this->row()->attendance]);
        $this->assertSame(1, DB::table('teacher_permissions')->count());
    }

    public function test_only_the_marks_and_attendance_flags_can_be_written(): void
    {
        $this->toggle('marks');

        foreach (['school_id', 'teacher_id', 'class_id', 'updated_at', 'id', ''] as $column) {
            $this->toggle($column, 999)->assertStatus(422);
        }

        $row = $this->row();
        $this->assertSame($this->school, (int) $row->school_id);
        $this->assertSame($this->teacher->id, (int) $row->teacher_id);
        $this->assertSame(1, DB::table('teacher_permissions')->count());
    }
}
