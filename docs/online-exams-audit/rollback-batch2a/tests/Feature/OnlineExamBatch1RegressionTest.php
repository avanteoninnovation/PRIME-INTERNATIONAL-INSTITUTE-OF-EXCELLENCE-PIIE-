<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamBatch1RegressionTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
    }

    public function test_manual_submit_uses_owned_submission_and_is_idempotent(): void
    {
        $student = $this->makeUser(7, 1);
        $exam = $this->makeExam(['is_published' => 1, 'workflow_state' => 'published']);
        $question = $this->makeQuestion($exam, ['correct_ans' => 'a']);
        $submission = $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => $student->id, 'school_id' => 1]);
        DB::table('online_exam_answers')->insert(['submission_id' => $submission, 'question_id' => $question, 'selected_option' => 'a', 'created_at' => now(), 'updated_at' => now()]);

        $payload = ['submission_id' => $submission];
        $this->actingAs($student)->post(route('student.online_exam.submit', $exam), $payload)->assertRedirect();
        $this->actingAs($student)->post(route('student.online_exam.submit', $exam), $payload)->assertRedirect();
        $this->assertDatabaseHas('online_exam_submissions', ['id' => $submission, 'status' => 'finalized', 'score' => 5]);
        $this->assertDatabaseCount('online_exam_answers', 1);
    }

    public function test_blank_answer_can_be_cleared_without_creating_duplicates(): void
    {
        $student = $this->makeUser(7, 1);
        $exam = $this->makeExam();
        $question = $this->makeQuestion($exam);
        $submission = $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => $student->id, 'school_id' => 1]);

        $route = route('student.online_exam.save_answer', $submission);
        $this->actingAs($student)->postJson($route, ['submission_id' => $submission, 'question_id' => $question, 'selected_option' => 'a'])->assertOk();
        $this->actingAs($student)->postJson($route, ['submission_id' => $submission, 'question_id' => $question, 'selected_option' => null, 'answer_text' => null])->assertOk();
        $this->assertDatabaseCount('online_exam_answers', 1);
        $this->assertDatabaseHas('online_exam_answers', ['submission_id' => $submission, 'question_id' => $question, 'selected_option' => null, 'answer_text' => null]);
    }

    public function test_exam_take_script_uses_revision_aware_autosave_and_flushes_before_submit(): void
    {
        $script = file_get_contents(resource_path('views/student/online_exam/take.blade.php'));
        $this->assertStringContainsString('answerRevisions', $script);
        $this->assertStringContainsString("answerRevisions[questionId] = (answerRevisions[questionId] || 0) + 1", $script);
        $this->assertStringContainsString('requestRevision', $script);
        $this->assertStringContainsString('flushPendingSaves', $script);
        $this->assertStringContainsString('Save failed', $script);
    }

    public function test_active_attempt_cannot_view_a_result(): void
    {
        $student = $this->makeUser(7, 1);
        $exam = $this->makeExam();
        $submission = $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => $student->id, 'school_id' => 1]);

        $this->actingAs($student)->get(route('student.online_exam.result', $submission))->assertForbidden();
    }

    public function test_result_release_policies_are_enforced_server_side(): void
    {
        $student = $this->makeUser(7, 1);
        $admin = $this->makeUser(2, 1);

        $manualExam = $this->makeExam(['result_release_policy' => 'manual']);
        $manualSubmission = $this->makeSubmission(['online_exam_id' => $manualExam, 'student_id' => $student->id, 'school_id' => 1, 'status' => 'finalized', 'submitted_at' => now(), 'score' => 5]);
        $this->actingAs($student)->get(route('student.online_exam.result', $manualSubmission))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.online_exams.submissions.publish_result', $manualSubmission))->assertRedirect();
        $this->actingAs($student)->get(route('student.online_exam.result', $manualSubmission))->assertOk();

        $futureExam = $this->makeExam(['result_release_policy' => 'after_exam_end', 'end_datetime' => now()->addHour()]);
        $futureSubmission = $this->makeSubmission(['online_exam_id' => $futureExam, 'student_id' => $student->id, 'school_id' => 1, 'status' => 'finalized', 'submitted_at' => now(), 'score' => 5]);
        $this->actingAs($student)->get(route('student.online_exam.result', $futureSubmission))->assertForbidden();
    }

    public function test_manual_finalization_requires_all_written_answers_marked(): void
    {
        $admin = $this->makeUser(2, 1);
        $exam = $this->makeExam(['is_published' => 1, 'workflow_state' => 'published']);
        $question = $this->makeQuestion($exam, ['type' => 'essay', 'correct_ans' => null]);
        $submission = $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => 1, 'school_id' => 1, 'status' => 'pending_manual_marking', 'submitted_at' => now()]);
        DB::table('online_exam_answers')->insert(['submission_id' => $submission, 'question_id' => $question, 'answer_text' => 'response', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($admin)->post(route('admin.online_exams.submissions.finalize', $submission))->assertStatus(422);
        $this->assertDatabaseHas('online_exam_submissions', ['id' => $submission, 'status' => 'pending_manual_marking']);
    }

    public function test_active_attempt_cannot_be_manually_marked(): void
    {
        $teacher = $this->makeUser(3, 1);
        $exam = $this->makeExam(['created_by' => $teacher->id, 'creator_id' => $teacher->id]);
        $question = $this->makeQuestion($exam, ['type' => 'essay']);
        $submission = $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => 1, 'school_id' => 1, 'status' => 'in_progress']);
        $answer = DB::table('online_exam_answers')->insertGetId(['submission_id' => $submission, 'question_id' => $question, 'answer_text' => 'response', 'created_at' => now(), 'updated_at' => now()]);

        $this->actingAs($teacher)->post(route('teacher.online_exams.answers.mark', $answer), ['answer_id' => $answer, 'awarded_marks' => 1])->assertStatus(422);
    }

    public function test_teacher_and_administrator_can_mark_only_eligible_written_answers(): void
    {
        foreach ([2, 3] as $role) {
            $marker = $this->makeUser($role, 1);
            $exam = $this->makeExam(['created_by' => $marker->id, 'creator_id' => $marker->id]);
            $question = $this->makeQuestion($exam, ['type' => 'essay']);
            $submission = $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => 1, 'school_id' => 1, 'status' => 'pending_manual_marking', 'submitted_at' => now()]);
            $answer = DB::table('online_exam_answers')->insertGetId(['submission_id' => $submission, 'question_id' => $question, 'answer_text' => 'response', 'created_at' => now(), 'updated_at' => now()]);
            $route = $role === 2
                ? route('admin.online_exams.answers.manual_mark', $answer)
                : route('teacher.online_exams.answers.mark', $answer);
            $this->actingAs($marker)->post($route, ['answer_id' => $answer, 'awarded_marks' => 1])->assertRedirect();
            $this->assertDatabaseHas('online_exam_answers', ['id' => $answer, 'awarded_marks' => 1, 'marked_by' => $marker->id]);
        }
    }

    public function test_true_false_keys_are_canonical_and_grade_both_values(): void
    {
        foreach ([['true', 'true', 5], ['true', 'false', 0], ['false', 'false', 5], ['false', 'true', 0]] as [$key, $given, $score]) {
            $student = $this->makeUser(7, 1);
            $exam = $this->makeExam(['total_marks' => 5, 'pass_mark' => 3]);
            $question = $this->makeQuestion($exam, ['type' => 'true_false', 'correct_ans' => $key, 'marks' => 5]);
            $submission = $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => $student->id, 'school_id' => 1]);
            DB::table('online_exam_answers')->insert(['submission_id' => $submission, 'question_id' => $question, 'selected_option' => $given, 'created_at' => now(), 'updated_at' => now()]);
            $this->actingAs($student)->post(route('student.online_exam.submit', $exam), ['submission_id' => $submission])->assertRedirect();
            $this->assertDatabaseHas('online_exam_submissions', ['id' => $submission, 'score' => $score]);
        }
    }

    public function test_invalid_true_false_answer_key_is_rejected(): void
    {
        $admin = $this->makeUser(2, 1);
        $exam = $this->makeExam(['is_published' => 0, 'workflow_state' => 'draft']);
        $this->actingAs($admin)->post(route('admin.online_exams.questions.store', $exam), [
            'question' => 'Boolean question', 'type' => 'true_false', 'marks' => 1, 'correct_ans' => 'a',
        ])->assertRedirect()->assertSessionHasErrors('correct_ans');
        $this->assertDatabaseCount('online_exam_questions', 0);
    }

    public function test_question_bank_static_route_precedes_dynamic_exam_route(): void
    {
        $teacher = $this->makeUser(3, 1);
        $this->actingAs($teacher)->get(route('teacher.online_exams.question_bank'))->assertOk();
    }

    public function test_teacher_route_bound_exam_can_be_updated_without_casting_model_to_int(): void
    {
        $teacher = $this->makeUser(3, 1);
        $subject = $this->makeSubject(1);
        $exam = $this->makeExam(['created_by' => $teacher->id, 'creator_id' => $teacher->id, 'subject_id' => $subject]);
        $payload = [
            'title' => 'Updated exam', 'subject_id' => $subject, 'exam_type' => 'quiz',
            'start_datetime' => now()->subHour()->toDateTimeString(), 'end_datetime' => now()->addHour()->toDateTimeString(),
            'duration_mins' => 30, 'total_marks' => 10, 'pass_mark' => 5, 'max_attempts' => 1,
            'result_release_policy' => 'immediate', 'workflow_state' => 'draft',
        ];
        $this->actingAs($teacher)->put(route('teacher.online_exams.update', $exam), $payload)->assertRedirect();
        $this->assertDatabaseHas('online_exams', ['id' => $exam, 'title' => 'Updated exam']);
    }

    public function test_teacher_cannot_delete_another_authors_question_bank_entry(): void
    {
        $teacher = $this->makeUser(3, 1);
        $author = $this->makeUser(3, 1);
        $question = DB::table('question_banks')->insertGetId([
            'school_id' => 1, 'question' => 'Owned by another teacher', 'type' => 'mcq',
            'correct_ans' => 'a', 'marks' => 1, 'created_by' => $author->id,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs($teacher)->delete(route('admin.question_bank.delete', $question))->assertForbidden();
        $this->assertDatabaseHas('question_banks', ['id' => $question]);
    }

    public function test_unready_exam_cannot_be_published_and_mutating_get_is_not_registered(): void
    {
        $admin = $this->makeUser(2, 1);
        $exam = $this->makeExam(['is_published' => 0, 'workflow_state' => 'draft']);
        $this->actingAs($admin)->get(route('admin.online_exams.publish', $exam))->assertStatus(405);
        $this->actingAs($admin)->post(route('admin.online_exams.publish', $exam))->assertStatus(422);
        $this->assertDatabaseHas('online_exams', ['id' => $exam, 'is_published' => 0]);
    }

    public function test_unpublish_and_cancel_do_not_strand_an_active_candidate(): void
    {
        $admin = $this->makeUser(2, 1);
        $exam = $this->makeExam(['is_published' => 1, 'workflow_state' => 'published']);
        $this->makeSubmission(['online_exam_id' => $exam, 'student_id' => 1, 'school_id' => 1, 'status' => 'in_progress']);

        $this->actingAs($admin)->post(route('admin.online_exams.unpublish', $exam))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.online_exams.cancel', $exam), ['reason' => 'maintenance'])->assertSessionHasErrors('exam');
        $this->assertDatabaseHas('online_exams', ['id' => $exam, 'is_published' => 1, 'workflow_state' => 'published']);
    }
}
