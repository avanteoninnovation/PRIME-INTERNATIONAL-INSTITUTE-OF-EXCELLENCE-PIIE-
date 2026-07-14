<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class TeacherOnlineExamTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();

        DB::table('global_settings')->where('key', 'role_perm_3')->delete();
        DB::table('global_settings')->insert([
            'key' => 'role_perm_3',
            'value' => json_encode([
                'view_online_exams',
                'create_online_exams',
                'edit_own_online_exams',
                'manage_exam_questions',
                'view_exam_attempts',
                'view_exam_results',
                'mark_exam_answers',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeTeacherWithPermission(int $schoolId = 1): User
    {
        $teacher = $this->makeUser(3, $schoolId);

        DB::table('teacher_permissions')->insert([
            'class_id' => $this->makeClass($schoolId),
            'section_id' => 1,
            'school_id' => $schoolId,
            'teacher_id' => $teacher->id,
            'marks' => 1,
            'attendance' => 1,
            'updated_at' => now(),
        ]);

        return $teacher;
    }

    public function test_teacher_online_exam_route_exists(): void
    {
        $this->assertTrue(Route::has('teacher.online_exams.index'));
    }

    public function test_teacher_sees_only_own_school_exams(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);
        $class1 = $this->makeClass(1);
        $subject1 = $this->makeSubject(1, $class1);

        $ownExam = $this->makeExam([
            'school_id' => 1,
            'class_id' => $class1,
            'subject_id' => $subject1,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
            'title' => 'Own School Exam',
        ]);

        $class2 = $this->makeClass(2);
        $subject2 = $this->makeSubject(2, $class2);
        $this->makeExam([
            'school_id' => 2,
            'class_id' => $class2,
            'subject_id' => $subject2,
            'title' => 'Other School Exam',
        ]);

        $response = $this->actingAs($teacher)->get(route('teacher.online_exams.index'));

        $response->assertOk();
        $response->assertSee('Own School Exam');
        $response->assertDontSee('Other School Exam');
    }

    public function test_teacher_cannot_edit_another_teachers_exam_without_edit_all_permission(): void
    {
        $teacherA = $this->makeTeacherWithPermission(1);
        $teacherB = $this->makeTeacherWithPermission(1);

        $classId = $this->makeClass(1);
        $subjectId = $this->makeSubject(1, $classId);
        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'created_by' => $teacherA->id,
            'creator_id' => $teacherA->id,
        ]);

        $this->actingAs($teacherB)
            ->get(route('teacher.online_exams.edit', $examId))
            ->assertStatus(403);
    }

    public function test_teacher_can_add_valid_mcq(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);
        $classId = DB::table('teacher_permissions')->where('teacher_id', $teacher->id)->value('class_id');
        $subjectId = $this->makeSubject(1, $classId);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->post(route('teacher.online_exams.questions.store', $examId), [
            'question' => 'What is 2+2?',
            'type' => 'multiple_choice',
            'option_a' => '4',
            'option_b' => '5',
            'correct_ans' => 'A',
            'marks' => 2,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('online_exam_questions', [
            'online_exam_id' => $examId,
            'question' => 'What is 2+2?',
        ]);
    }

    public function test_invalid_mcq_is_rejected(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);
        $classId = DB::table('teacher_permissions')->where('teacher_id', $teacher->id)->value('class_id');
        $subjectId = $this->makeSubject(1, $classId);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
        ]);

        $response = $this->actingAs($teacher)->from('/teacher/online-exams/' . $examId . '/questions')
            ->post(route('teacher.online_exams.questions.store', $examId), [
                'question' => 'Broken question',
                'type' => 'multiple_choice',
                'option_a' => 'Only one',
                'marks' => 1,
            ]);

        $response->assertSessionHasErrors();
    }

    public function test_teacher_can_import_question_bank_snapshot_and_future_changes_do_not_mutate_exam_question(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);
        $classId = DB::table('teacher_permissions')->where('teacher_id', $teacher->id)->value('class_id');
        $subjectId = $this->makeSubject(1, $classId);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
        ]);

        $bankId = DB::table('question_banks')->insertGetId([
            'school_id' => 1,
            'subject_id' => $subjectId,
            'question' => 'Original bank question',
            'type' => 'mcq',
            'option_a' => 'A',
            'option_b' => 'B',
            'correct_ans' => 'A',
            'marks' => 3,
            'difficulty' => 'easy',
            'created_by' => $teacher->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)->post(route('teacher.online_exams.question_bank.import', $examId), [
            'question_bank_ids' => [$bankId],
        ])->assertRedirect();

        $imported = DB::table('online_exam_questions')->where('online_exam_id', $examId)->first();
        $this->assertNotNull($imported);
        $this->assertSame('Original bank question', $imported->question);

        DB::table('question_banks')->where('id', $bankId)->update([
            'question' => 'Changed bank question',
            'updated_at' => now(),
        ]);

        $importedAgain = DB::table('online_exam_questions')->where('id', $imported->id)->first();
        $this->assertSame('Original bank question', $importedAgain->question);
    }

    public function test_teacher_without_publish_permission_submits_for_review(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);

        DB::table('global_settings')->where('key', 'role_perm_3')->update([
            'value' => json_encode([
                'view_online_exams',
                'create_online_exams',
                'edit_own_online_exams',
                'manage_exam_questions',
            ]),
            'updated_at' => now(),
        ]);

        $classId = DB::table('teacher_permissions')->where('teacher_id', $teacher->id)->value('class_id');
        $subjectId = $this->makeSubject(1, $classId);
        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'total_marks' => 2,
            'pass_mark' => 1,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
        ]);
        $this->makeQuestion($examId, ['marks' => 2]);

        $this->actingAs($teacher)
            ->post(route('teacher.online_exams.submit_review', $examId))
            ->assertRedirect();

        $this->assertDatabaseHas('online_exams', [
            'id' => $examId,
            'workflow_state' => 'pending_review',
        ]);
    }

    public function test_teacher_with_publish_permission_can_publish_and_publish_fails_on_marks_mismatch(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);

        DB::table('global_settings')->where('key', 'role_perm_3')->update([
            'value' => json_encode([
                'view_online_exams',
                'create_online_exams',
                'edit_own_online_exams',
                'manage_exam_questions',
                'publish_online_exams',
            ]),
            'updated_at' => now(),
        ]);

        $classId = DB::table('teacher_permissions')->where('teacher_id', $teacher->id)->value('class_id');
        $subjectId = $this->makeSubject(1, $classId);
        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'total_marks' => 5,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
        ]);
        $this->makeQuestion($examId, ['marks' => 4]);

        $this->actingAs($teacher)
            ->post(route('teacher.online_exams.publish', $examId))
            ->assertSessionHasErrors();

        DB::table('online_exam_questions')->where('online_exam_id', $examId)->update(['marks' => 5]);

        $this->actingAs($teacher)
            ->post(route('teacher.online_exams.publish', $examId))
            ->assertRedirect();

        $this->assertDatabaseHas('online_exams', [
            'id' => $examId,
            'workflow_state' => 'published',
            'is_published' => 1,
        ]);
    }

    public function test_unpublish_fails_when_attempts_exist(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);
        DB::table('global_settings')->where('key', 'role_perm_3')->update([
            'value' => json_encode([
                'view_online_exams',
                'create_online_exams',
                'edit_own_online_exams',
                'manage_exam_questions',
                'publish_online_exams',
            ]),
            'updated_at' => now(),
        ]);

        $classId = DB::table('teacher_permissions')->where('teacher_id', $teacher->id)->value('class_id');
        $subjectId = $this->makeSubject(1, $classId);
        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'workflow_state' => 'published',
            'is_published' => 1,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
        ]);
        $this->makeSubmission(['online_exam_id' => $examId, 'school_id' => 1]);

        $this->actingAs($teacher)
            ->post(route('teacher.online_exams.unpublish', $examId))
            ->assertStatus(403);
    }

    public function test_teacher_can_mark_written_answer_and_reject_above_max(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);
        $student = $this->makeUser(7, 1);
        $classId = DB::table('teacher_permissions')->where('teacher_id', $teacher->id)->value('class_id');
        $subjectId = $this->makeSubject(1, $classId);

        $examId = $this->makeExam([
            'school_id' => 1,
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'created_by' => $teacher->id,
            'creator_id' => $teacher->id,
        ]);
        $questionId = $this->makeQuestion($examId, ['type' => 'essay', 'marks' => 5, 'correct_ans' => null]);
        $submissionId = $this->makeSubmission([
            'online_exam_id' => $examId,
            'student_id' => $student->id,
            'school_id' => 1,
            'status' => 'pending_manual_marking',
            'submitted_at' => now(),
        ]);

        $answerId = DB::table('online_exam_answers')->insertGetId([
            'submission_id' => $submissionId,
            'question_id' => $questionId,
            'answer_text' => 'Essay answer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.online_exams.answers.mark', $answerId), [
                'answer_id' => $answerId,
                'awarded_marks' => 6,
            ])->assertSessionHasErrors();

        $this->actingAs($teacher)
            ->post(route('teacher.online_exams.answers.mark', $answerId), [
                'answer_id' => $answerId,
                'awarded_marks' => 4,
            ])->assertRedirect();

        $this->assertDatabaseHas('online_exam_answers', [
            'id' => $answerId,
            'awarded_marks' => 4,
        ]);
    }

    public function test_existing_admin_and_student_routes_remain_valid(): void
    {
        $this->assertTrue(Route::has('admin.online_exams.index'));
        $this->assertTrue(Route::has('student.online_exam.list'));
    }

    public function test_teacher_navigation_links_render_only_when_permitted(): void
    {
        $teacher = $this->makeTeacherWithPermission(1);
        $response = $this->actingAs($teacher)->get(route('teacher.online_exams.index'));
        $response->assertSee('Online Exams');
        $response->assertSee('Question Bank');
        $response->assertSee('Marking Queue');

        DB::table('global_settings')->where('key', 'role_perm_3')->update([
            'value' => json_encode(['view_online_exams']),
            'updated_at' => now(),
        ]);

        DB::table('users')->where('id', $teacher->id)->update([
            'menu_permission' => json_encode(['view_online_exams']),
            'updated_at' => now(),
        ]);

        $responseLimited = $this->actingAs($teacher->fresh())->get(route('teacher.online_exams.index'));
        $responseLimited->assertSee('Online Exams');
        $responseLimited->assertSee('Marking Queue');
    }
}
