<?php

namespace Tests\Feature;

use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Support\Permissions\OnlineExamAuthorizer;
use App\Support\Permissions\OnlineExamPermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

/**
 * RBAC Phase 1 — CHARACTERIZATION of school_id boundaries that are already
 * enforced today. Nothing new is enforced here.
 *
 * The permission layer itself (OnlineExamPermissionService::has()) is NOT
 * school-aware — a School Admin holds every exam permission regardless of
 * which school a resource belongs to. The boundary lives in the
 * authorizer/policies (sameSchool) and in school-scoped lookups. These tests
 * pin that split so a future permission layer can't be mistaken for a
 * tenant boundary. Live Class boundaries are pinned in
 * RbacLiveClassPolicyCharacterizationTest; HTTP-level staff/student/programme
 * isolation is covered by the existing StaffModuleTest,
 * StudentExportAndIsolationTest and ProgrammeTenantIsolationTest.
 */
class RbacTenantIsolationCharacterizationTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();

        DB::table('schools')->insert(['id' => 2, 'title' => 'School B', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_raw_permissions_are_not_school_aware_the_authorizer_is(): void
    {
        $schoolBAdmin = $this->makeUser(2, 2);
        $schoolAExam = OnlineExam::findOrFail($this->makeExam(['school_id' => 1]));

        $this->assertTrue(app(OnlineExamPermissionService::class)->has($schoolBAdmin, 'edit_all_online_exams'));
        $this->assertFalse(app(OnlineExamAuthorizer::class)->sameSchool($schoolBAdmin, (int) $schoolAExam->school_id));
        $this->assertFalse(app(OnlineExamAuthorizer::class)->canManageExam($schoolBAdmin, $schoolAExam));
    }

    public function test_school_b_users_cannot_operate_on_school_a_exam_questions(): void
    {
        $schoolAAuthor = $this->makeUser(3, 1);
        $examId = $this->makeExam(['school_id' => 1, 'created_by' => $schoolAAuthor->id, 'creator_id' => $schoolAAuthor->id]);
        $question = OnlineExamQuestion::findOrFail($this->makeQuestion($examId));

        $this->assertTrue(Gate::forUser($schoolAAuthor)->allows('view', $question));
        $this->assertTrue(Gate::forUser($this->makeUser(2, 1))->allows('view', $question));

        foreach ([2, 3] as $roleId) {
            $schoolBUser = $this->makeUser($roleId, 2);
            foreach (['view', 'update', 'delete'] as $ability) {
                $this->assertFalse(Gate::forUser($schoolBUser)->allows($ability, $question), "school B role {$roleId} / {$ability}");
            }
        }
    }

    public function test_teacher_subject_lookup_is_scoped_to_the_teachers_school(): void
    {
        $service = app(OnlineExamPermissionService::class);
        $schoolATeacher = $this->makeUser(3, 1);
        $schoolBClass = $this->makeClass(2);
        $schoolBSubject = $this->makeSubject(2, $schoolBClass);

        // Even with a matching class assignment row, the other school's subject is not found.
        DB::table('teacher_permissions')->insert([
            'teacher_id' => $schoolATeacher->id, 'school_id' => 2, 'class_id' => $schoolBClass, 'updated_at' => now(),
        ]);

        $this->assertFalse($service->teacherCanUseSubject($schoolATeacher, $schoolBSubject));

        $schoolAClass = $this->makeClass(1);
        $schoolASubject = $this->makeSubject(1, $schoolAClass);
        DB::table('teacher_permissions')->insert([
            'teacher_id' => $schoolATeacher->id, 'school_id' => 1, 'class_id' => $schoolAClass, 'updated_at' => now(),
        ]);
        $this->assertTrue($service->teacherCanUseSubject($schoolATeacher, $schoolASubject));
    }

    public function test_student_cannot_sit_or_see_another_schools_exam(): void
    {
        $schoolBStudent = $this->makeUser(7, 2);
        $schoolAExam = OnlineExam::findOrFail($this->makeExam(['school_id' => 1]));

        $this->assertFalse(Gate::forUser($schoolBStudent)->allows('view', $schoolAExam));
        $this->assertFalse(Gate::forUser($schoolBStudent)->allows('sit', $schoolAExam));
        $this->assertFalse(Gate::forUser($schoolBStudent)->allows('viewResult', $schoolAExam));
    }
}
