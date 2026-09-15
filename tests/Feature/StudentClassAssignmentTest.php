<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\User;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers AdminController::studentUpdate() assigning a real class to a
 * Programme-track (HEI) student — this used to silently no-op:
 * Enrollment::where('user_id', $id)->update([...]) affects zero rows when
 * the student has no Enrollment row yet (the normal state for a
 * Programme-track student before EnrollmentDefaults::ensureRow() existed),
 * so the class picked on the shared admin/student/edit_student form
 * appeared to save but never actually took effect — the student stayed
 * invisible to that class's teacher regardless of what was picked.
 */
class StudentClassAssignmentTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    private function updatePayload(int $classId, int $sectionId): array
    {
        return [
            'name' => 'Test Student', 'email' => 'test.student@example.com',
            'gender' => 'male', 'blood_group' => 'O+', 'birthday' => '2000-01-01',
            'phone' => '123456', 'address' => 'Somewhere',
            'class_id' => $classId, 'section_id' => $sectionId,
        ];
    }

    public function test_assigning_a_class_to_a_student_with_no_enrollment_row_creates_one(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $classId = $this->makeClass($schoolId);
        $sectionId = (int) \Illuminate\Support\Facades\DB::table('sections')->insertGetId([
            'name' => 'A', 'class_id' => $classId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = User::create([
            'name' => 'Test Student', 'email' => 'test.student@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);

        $this->assertSame(0, Enrollment::where('user_id', $student->id)->count(), 'Precondition: student starts with no Enrollment row.');

        $response = $this->actingAs($admin)
            ->post(route('admin.student.update', ['id' => $student->id]), $this->updatePayload($classId, $sectionId));

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollment', [
            'user_id' => $student->id,
            'school_id' => $schoolId,
            'class_id' => $classId,
            'section_id' => $sectionId,
        ]);
    }

    public function test_reassigning_a_class_updates_the_existing_row_instead_of_duplicating_it(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $oldClassId = $this->makeClass($schoolId, ['name' => 'Old Class']);
        $newClassId = $this->makeClass($schoolId, ['name' => 'New Class']);
        $sectionId = (int) \Illuminate\Support\Facades\DB::table('sections')->insertGetId([
            'name' => 'A', 'class_id' => $newClassId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $student = User::create([
            'name' => 'Test Student', 'email' => 'reassign.student@example.com',
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
        ]);
        \Illuminate\Support\Facades\DB::table('enrollment')->insert([
            'user_id' => $student->id, 'class_id' => $oldClassId, 'section_id' => 1,
            'school_id' => $schoolId, 'department_id' => 0, 'session_id' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('admin.student.update', ['id' => $student->id]), array_merge(
            $this->updatePayload($newClassId, $sectionId),
            ['email' => 'reassign.student@example.com']
        ));

        $this->assertSame(1, Enrollment::where('user_id', $student->id)->count(), 'Must update in place, not create a second row.');
        $this->assertDatabaseHas('enrollment', [
            'user_id' => $student->id,
            'class_id' => $newClassId,
        ]);
    }
}
