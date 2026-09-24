<?php

namespace Tests\Feature;

use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\User;
use App\Support\Permissions\OnlineExamPermissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

/**
 * RBAC Phase 1 — CHARACTERIZATION, not specification.
 *
 * Pins today's Online Exam authorization per role — the raw permission
 * matrix from OnlineExamPermissionService and the ownership/school outcomes
 * of the registered policies — so the RBAC work can't shift it by accident.
 * Uses OnlineExamTestHelper's defaults: role_perm_2/4/7/19 seeded as [],
 * role_perm_3 absent, every user's menu_permission NULL.
 *
 * Known behaviours pinned as-is (NOT fixed):
 *  - role_perm_3 cannot take anything away from a Teacher: the built-in
 *    teacher fallback grants its 9 permissions regardless, and a '!key'
 *    entry is only honoured by the seeder's merge, never at runtime.
 *  - role 19 (Examinations Officer) gets 4 raw permissions but no
 *    exam-level access, because every exam policy also requires
 *    edit_all_online_exams or authorship.
 *  - role 1 (school_id NULL) fails every exam-level sameSchool() check.
 */
class RbacOnlineExamAuthorizationCharacterizationTest extends TestCase
{
    use OnlineExamTestHelper;

    private const TEACHER_FALLBACK = [
        'view_online_exams', 'create_online_exams', 'edit_own_online_exams', 'delete_online_exams',
        'manage_exam_questions', 'view_exam_attempts', 'mark_exam_answers', 'view_exam_results',
        'review_exam_proctoring',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();

        DB::table('schools')->insert(['id' => 2, 'title' => 'Other School', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function granted(User $user): array
    {
        $service = app(OnlineExamPermissionService::class);

        return array_values(array_filter(OnlineExamPermissionService::KEYS, fn ($key) => $service->has($user, $key)));
    }

    public function test_raw_permission_matrix_per_role(): void
    {
        $expected = [
            1 => OnlineExamPermissionService::KEYS,
            2 => OnlineExamPermissionService::KEYS,
            3 => self::TEACHER_FALLBACK,
            7 => ['view_exam_results', 'sit_online_exams'],
            19 => ['view_online_exams', 'view_exam_attempts', 'view_exam_results', 'review_exam_proctoring'],
        ];

        foreach (range(1, 20) as $roleId) {
            $user = $this->makeUser($roleId, $roleId === 1 ? 0 : 1);
            $this->assertEqualsCanonicalizing($expected[$roleId] ?? [], $this->granted($user), "role {$roleId}");
        }
    }

    public function test_disabled_accounts_get_nothing_even_as_admin(): void
    {
        $this->assertSame([], $this->granted($this->makeUser(2, 1, 'disable')));
        $this->assertSame([], $this->granted($this->makeUser(3, 1, 'disable')));
    }

    public function test_unknown_permission_keys_are_denied_even_for_admin(): void
    {
        $this->assertFalse(app(OnlineExamPermissionService::class)->has($this->makeUser(2, 1), 'not_a_real_permission'));
    }

    public function test_known_behaviour_role_perm_cannot_restrict_a_teacher(): void
    {
        DB::table('global_settings')->insert([
            'key' => 'role_perm_3', 'value' => json_encode(['!delete_online_exams']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertEqualsCanonicalizing(self::TEACHER_FALLBACK, $this->granted($this->makeUser(3, 1)));
    }

    public function test_menu_permission_and_role_perm_can_only_add_permissions(): void
    {
        $accountant = $this->makeUser(4, 1);
        $accountant->menu_permission = json_encode(['admin.online_exams']);
        $service = app(OnlineExamPermissionService::class);
        $this->assertTrue($service->has($accountant, 'edit_all_online_exams'));
        $this->assertFalse($service->has($accountant, 'sit_online_exams'));

        DB::table('global_settings')->where('key', 'role_perm_4')->update(['value' => json_encode(['exams'])]);
        $legacyKeyAccountant = $this->makeUser(4, 1);
        $this->assertTrue($service->has($legacyKeyAccountant, 'view_online_exams'));
        $this->assertFalse($service->has($legacyKeyAccountant, 'manage_exam_settings'));
    }

    public function test_exam_policies_respect_ownership_role_and_school(): void
    {
        $author = $this->makeUser(3, 1);
        $otherTeacher = $this->makeUser(3, 1);
        $admin = $this->makeUser(2, 1);
        $student = $this->makeUser(7, 1);
        $examsOfficer = $this->makeUser(19, 1);
        $superAdmin = $this->makeUser(1, 0);
        $otherSchoolAdmin = $this->makeUser(2, 2);
        $otherSchoolStudent = $this->makeUser(7, 2);

        $exam = OnlineExam::findOrFail($this->makeExam(['created_by' => $author->id, 'creator_id' => $author->id]));

        $abilities = ['view', 'update', 'delete', 'publish', 'cancel', 'manageQuestions',
                      'viewAttempts', 'markAnswers', 'reviewProctoring', 'sit', 'viewResult'];

        $expected = [
            'author'             => [$author,             ['view', 'update', 'delete', 'manageQuestions', 'viewAttempts', 'markAnswers', 'reviewProctoring', 'viewResult']],
            'other teacher'      => [$otherTeacher,       []],
            'school admin'       => [$admin,              ['view', 'update', 'delete', 'publish', 'cancel', 'manageQuestions', 'viewAttempts', 'markAnswers', 'reviewProctoring', 'viewResult']],
            'student'            => [$student,            ['view', 'sit', 'viewResult']],
            'exams officer (19)' => [$examsOfficer,       []],
            'super admin'        => [$superAdmin,         []],
            'other school admin' => [$otherSchoolAdmin,   []],
            'other school student' => [$otherSchoolStudent, []],
        ];

        foreach ($expected as $label => [$user, $allowed]) {
            foreach ($abilities as $ability) {
                $this->assertSame(
                    in_array($ability, $allowed, true),
                    Gate::forUser($user)->allows($ability, $exam),
                    "{$label} / {$ability}"
                );
            }
        }

        $this->assertTrue(Gate::forUser($examsOfficer)->allows('viewAny', OnlineExam::class));
        $this->assertTrue(Gate::forUser($author)->allows('create', OnlineExam::class));
        $this->assertFalse(Gate::forUser($this->makeUser(4, 1))->allows('viewAny', OnlineExam::class));
    }

    public function test_submission_access_and_result_release_follow_ownership_and_school(): void
    {
        $author = $this->makeUser(3, 1);
        $otherTeacher = $this->makeUser(3, 1);
        $admin = $this->makeUser(2, 1);
        $student = $this->makeUser(7, 1);
        $otherStudent = $this->makeUser(7, 1);
        $otherSchoolAdmin = $this->makeUser(2, 2);

        $examId = $this->makeExam(['created_by' => $author->id, 'creator_id' => $author->id]);
        $submission = OnlineExamSubmission::findOrFail($this->makeSubmission([
            'online_exam_id' => $examId, 'student_id' => $student->id, 'status' => 'submitted',
        ]));

        // 'grade' is what publishResult() authorizes, i.e. result release.
        $expected = [
            'author'             => [$author,           ['view' => true,  'grade' => true]],
            'school admin'       => [$admin,            ['view' => true,  'grade' => true]],
            'other teacher'      => [$otherTeacher,     ['view' => false, 'grade' => false]],
            'own student'        => [$student,          ['view' => true,  'grade' => false]],
            'other student'      => [$otherStudent,     ['view' => false, 'grade' => false]],
            'other school admin' => [$otherSchoolAdmin, ['view' => false, 'grade' => false]],
        ];

        foreach ($expected as $label => [$user, $outcomes]) {
            foreach ($outcomes as $ability => $allowed) {
                $this->assertSame($allowed, Gate::forUser($user)->allows($ability, $submission), "{$label} / {$ability}");
            }
        }
    }
}
