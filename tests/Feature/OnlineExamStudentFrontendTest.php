<?php

namespace Tests\Feature;

use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamSubmission;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

/**
 * Covers the student-facing exam-taking rebuild: instructions() used to be a
 * JSON-only endpoint nothing ever consumed (a browser landing there via the
 * normal "Start Exam" redirect got raw JSON with no way to proceed), and
 * takeExam() never applied shuffle_questions/shuffle_options despite both
 * columns existing, never showed a previously-saved answer on resume, and
 * redirected an expired attempt at a POST-only route via GET (a 405).
 */
class OnlineExamStudentFrontendTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
    }

    // ── instructions() ───────────────────────────────────────────────────

    public function test_instructions_renders_a_page_with_exam_details(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId, 'title' => 'Midterm Test']);
        $this->makeQuestion($examId);

        $response = $this->actingAs($student)->get(route('student.online_exam.instructions', $examId));

        $response->assertStatus(200);
        $response->assertSee('Midterm Test');
        $response->assertViewIs('student.online_exam.instructions');
    }

    public function test_instructions_redirects_straight_to_take_when_an_attempt_is_already_active(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId]);
        $this->makeSubmission(['online_exam_id' => $examId, 'student_id' => $student->id, 'school_id' => 1]);

        $response = $this->actingAs($student)->get(route('student.online_exam.instructions', $examId));

        $response->assertRedirect(route('student.online_exam.take', $examId));
    }

    // ── takeExam(): shuffling ────────────────────────────────────────────

    public function test_question_order_is_stable_across_reloads_of_the_same_attempt_when_shuffled(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId, 'shuffle_questions' => true]);
        for ($i = 1; $i <= 6; $i++) {
            $this->makeQuestion($examId, ['question' => "Question {$i}", 'sort_order' => $i]);
        }

        $this->makeSubmission(['online_exam_id' => $examId, 'student_id' => $student->id, 'school_id' => 1]);

        $first = $this->actingAs($student)->get(route('student.online_exam.take', $examId));
        $second = $this->actingAs($student)->get(route('student.online_exam.take', $examId));

        $first->assertStatus(200);
        $this->assertSame($first->getContent(), $second->getContent());
    }

    public function test_question_order_is_not_shuffled_when_the_exam_does_not_request_it(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId, 'shuffle_questions' => false]);
        $q1 = $this->makeQuestion($examId, ['question' => 'Alpha Question', 'sort_order' => 1]);
        $q2 = $this->makeQuestion($examId, ['question' => 'Beta Question', 'sort_order' => 2]);

        $this->makeSubmission(['online_exam_id' => $examId, 'student_id' => $student->id, 'school_id' => 1]);

        $response = $this->actingAs($student)->get(route('student.online_exam.take', $examId));

        $response->assertStatus(200);
        $alphaPos = strpos($response->getContent(), 'Alpha Question');
        $betaPos = strpos($response->getContent(), 'Beta Question');
        $this->assertLessThan($betaPos, $alphaPos, 'Unshuffled questions must render in sort_order.');
    }

    public function test_shuffled_option_display_order_still_submits_the_true_underlying_option_key(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId, 'shuffle_options' => true]);
        $questionId = $this->makeQuestion($examId, [
            'option_a' => 'Correct Answer Text',
            'option_b' => 'Wrong One',
            'option_c' => 'Wrong Two',
            'option_d' => 'Wrong Three',
            'correct_ans' => 'a',
        ]);

        // takeExam() creates no submission itself; without one it redirects
        // to instructions, so an attempt is started directly via the model
        // to isolate this test to the rendering behaviour.
        $this->makeSubmission(['online_exam_id' => $examId, 'student_id' => $student->id, 'school_id' => 1]);

        $response = $this->actingAs($student)->get(route('student.online_exam.take', $examId));

        $response->assertStatus(200);
        // Whatever position "Correct Answer Text" renders in, its radio
        // input's value attribute must still be the real key "a" — the
        // question of grading correctness must never depend on display order.
        $response->assertSee('value="a"', false);
        $response->assertSee('Correct Answer Text');
    }

    // ── takeExam(): resume pre-fill ──────────────────────────────────────

    public function test_a_previously_saved_answer_is_pre_filled_on_resume(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId]);
        $questionId = $this->makeQuestion($examId, ['type' => 'mcq']);
        $submissionId = $this->makeSubmission(['online_exam_id' => $examId, 'student_id' => $student->id, 'school_id' => 1]);

        OnlineExamAnswer::create([
            'submission_id' => $submissionId,
            'question_id' => $questionId,
            'selected_option' => 'b',
        ]);

        $response = $this->actingAs($student)->get(route('student.online_exam.take', $examId));

        $response->assertStatus(200);
        $response->assertSee('id="q' . $questionId . 'b" data-question-id="' . $questionId . '" data-answer-type="option"
                                       checked', false);
    }

    public function test_an_essay_answer_text_is_pre_filled_on_resume(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId]);
        $questionId = $this->makeQuestion($examId, ['type' => 'essay', 'option_a' => null, 'option_b' => null, 'option_c' => null, 'option_d' => null]);
        $submissionId = $this->makeSubmission(['online_exam_id' => $examId, 'student_id' => $student->id, 'school_id' => 1]);

        OnlineExamAnswer::create([
            'submission_id' => $submissionId,
            'question_id' => $questionId,
            'answer_text' => 'My previously typed essay answer',
        ]);

        $response = $this->actingAs($student)->get(route('student.online_exam.take', $examId));

        $response->assertStatus(200);
        $response->assertSee('My previously typed essay answer');
    }

    // ── takeExam(): expired attempt no longer 405s ───────────────────────

    public function test_visiting_take_on_an_expired_attempt_finalises_it_instead_of_erroring(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId, 'pass_mark' => 5]);
        $questionId = $this->makeQuestion($examId, ['marks' => 10, 'correct_ans' => 'a']);
        $submissionId = $this->makeSubmission([
            'online_exam_id' => $examId,
            'student_id' => $student->id,
            'school_id' => 1,
            'started_at' => now()->subHour(),
            'expires_at' => now()->subMinute(),
        ]);

        OnlineExamAnswer::create([
            'submission_id' => $submissionId,
            'question_id' => $questionId,
            'selected_option' => 'a',
        ]);

        $response = $this->actingAs($student)->get(route('student.online_exam.take', $examId));

        // Previously a 405 (GET against a POST-only route); must now finalise
        // and land on the results page instead.
        $response->assertRedirect(route('student.online_exam.result', $submissionId));

        $this->assertSame(
            OnlineExamSubmission::STATUS_FINALIZED,
            OnlineExamSubmission::find($submissionId)->status
        );
    }

    // ── list.blade.php routing ───────────────────────────────────────────

    public function test_list_offers_resume_not_view_result_for_an_in_progress_attempt(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId]);
        $this->makeSubmission(['online_exam_id' => $examId, 'student_id' => $student->id, 'school_id' => 1, 'status' => 'in_progress']);

        $response = $this->actingAs($student)->get(route('student.online_exam.list'));

        $response->assertStatus(200);
        $response->assertSee('Resume Exam');
        $response->assertDontSee('View Result');
    }

    public function test_list_offers_view_result_once_a_finalized_attempt_exists(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId, 'result_release_policy' => 'immediate']);
        $this->makeSubmission([
            'online_exam_id' => $examId,
            'student_id' => $student->id,
            'school_id' => 1,
            'status' => 'finalized',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.online_exam.list'));

        $response->assertStatus(200);
        $response->assertSee('View Result');
        $response->assertDontSee('Resume Exam');
    }

    public function test_list_offers_start_exam_when_the_student_has_never_attempted_it(): void
    {
        $student = $this->makeUser(7, 1);
        $classId = $this->makeClass(1);
        $this->enrollStudent($student->id, 1, $classId);

        $examId = $this->makeExam(['school_id' => 1, 'class_id' => $classId]);

        $response = $this->actingAs($student)->get(route('student.online_exam.list'));

        $response->assertStatus(200);
        $response->assertSee('Start Exam');
        $response->assertSee(route('student.online_exam.instructions', $examId), false);
    }
}
