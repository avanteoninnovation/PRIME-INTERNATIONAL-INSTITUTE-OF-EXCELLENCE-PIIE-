<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use App\Models\OnlineExam;
use App\Support\OnlineExams\OnlineExamPortalNotifier;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamAdminVisibilityTest extends TestCase
{
    use OnlineExamTestHelper;

    private $admin;
    private $student;
    private int $exam;
    private int $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
        $this->admin = $this->makeUser(2, 1);
        $this->student = $this->makeUser(7, 1);
        $this->exam = $this->makeExam([
            'title' => 'Admin visibility test',
            'result_release_policy' => 'manual',
        ]);
        $this->submission = $this->makeSubmission([
            'online_exam_id' => $this->exam,
            'student_id' => $this->student->id,
            'school_id' => 1,
        ]);
    }

    private function createPendingManualAttempt(): void
    {
        $question = $this->makeQuestion($this->exam, [
            'type' => 'essay',
            'correct_ans' => null,
            'marks' => 5,
        ]);
        $this->actingAs($this->student)->postJson(route('student.online_exam.save_answer', $this->submission), [
            'submission_id' => $this->submission,
            'question_id' => $question,
            'answer_revision' => 1,
            'answer_text' => 'Written response',
        ])->assertOk();
        $this->actingAs($this->student)->post(route('student.online_exam.submit', $this->exam), [
            'submission_id' => $this->submission,
        ])->assertRedirect();
    }

    public function test_admin_index_exposes_submission_count_pending_marking_and_results_action(): void
    {
        $this->createPendingManualAttempt();

        $this->actingAs($this->admin)->get(route('admin.online_exams.index'))
            ->assertOk()
            ->assertSee('Admin visibility test')
            ->assertSee('Manual marking required')
            ->assertSee('Results &amp; Marking', false)
            ->assertSee('1');
    }

    public function test_admin_submissions_exposes_marking_and_proctoring_routes_and_event_label(): void
    {
        $this->createPendingManualAttempt();
        DB::table('online_exam_proctoring_events')->insert([
            'submission_id' => $this->submission,
            'event_type' => 'tab_hidden',
            'event_time' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->admin)->get(route('admin.online_exams.submissions', $this->exam))
            ->assertOk()
            ->assertSee('Awaiting Marking')
            ->assertSee('Mark / Review')
            ->assertSee('Proctoring');

        $this->actingAs($this->admin)->get(route('admin.online_exams.proctoring.review', [
            'id' => $this->exam,
            'submission' => $this->submission,
        ]))->assertOk()->assertSee('Tab Hidden');
    }

    public function test_authorized_admin_publication_persists_and_is_idempotent(): void
    {
        DB::table('online_exams')->where('id', $this->exam)->update(['result_release_policy' => 'manual']);
        $question = $this->makeQuestion($this->exam, ['type' => 'mcq', 'correct_ans' => 'a', 'marks' => 5]);
        $this->actingAs($this->student)->postJson(route('student.online_exam.save_answer', $this->submission), [
            'submission_id' => $this->submission, 'question_id' => $question, 'answer_revision' => 1, 'selected_option' => 'a',
        ])->assertOk();
        $this->actingAs($this->student)->post(route('student.online_exam.submit', $this->exam), ['submission_id' => $this->submission])->assertRedirect();
        $this->assertSame('finalized', DB::table('online_exam_submissions')->where('id', $this->submission)->value('status'));
        // Simulate the teacher's explicit Submit Marking for Review action;
        // completion alone intentionally leaves this state as not_ready.
        DB::table('online_exam_submissions')->where('id', $this->submission)->update(['result_review_state' => 'pending_review']);

        $this->actingAs($this->admin)->post(route('admin.online_exams.submissions.publish_result', $this->submission))
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('result_published', DB::table('online_exam_submissions')->where('id', $this->submission)->value('status'));
        $this->assertDatabaseHas('online_exam_user_notifications', [
            'user_id' => $this->student->id,
            'type' => 'result_published',
            'submission_id' => $this->submission,
        ]);

        $this->actingAs($this->admin)->post(route('admin.online_exams.submissions.publish_result', $this->submission))
            ->assertRedirect();
        $this->assertSame('result_published', DB::table('online_exam_submissions')->where('id', $this->submission)->value('status'));
    }

    public function test_after_exam_end_policy_cannot_be_published_manually(): void
    {
        $this->createPendingManualAttempt();
        $response = $this->actingAs($this->admin)->post(route('admin.online_exams.submissions.publish_result', $this->submission));
        $response->assertStatus(422);
        $this->assertNotSame('result_published', DB::table('online_exam_submissions')->where('id', $this->submission)->value('status'));
    }

    public function test_marking_completion_stays_not_ready_until_explicit_review_submission(): void
    {
        $this->createPendingManualAttempt();
        $answer = DB::table('online_exam_answers')->where('submission_id', $this->submission)->first();

        $this->actingAs($this->admin)->post(route('admin.online_exams.answers.manual_mark', $answer->id), [
            'answer_id' => $answer->id,
            'awarded_marks' => 4,
        ])->assertRedirect();

        $this->assertSame('submitted', DB::table('online_exam_submissions')->where('id', $this->submission)->value('status'));
        $this->assertSame('not_ready', DB::table('online_exam_submissions')->where('id', $this->submission)->value('result_review_state'));
        $this->assertDatabaseMissing('online_exam_user_notifications', [
            'submission_id' => $this->submission,
            'type' => 'marking_submitted_for_review',
        ]);
        $this->actingAs($this->student)->get(route('student.online_exam.result', $this->submission))
            ->assertOk()->assertSee('awaiting publication');
    }

    public function test_marking_review_notification_targets_exact_submission_results_row(): void
    {
        OnlineExamPortalNotifier::admins(
            'marking_submitted_for_review',
            'Marking Awaiting Review',
            'Review this submission.',
            OnlineExam::findOrFail($this->exam),
            3,
            'marking-review:' . $this->submission,
            $this->submission
        );

        $notification = DB::table('online_exam_user_notifications')
            ->where('type', 'marking_submitted_for_review')
            ->where('submission_id', $this->submission)
            ->first();

        $this->assertNotNull($notification);
        $this->assertSame(
            route('admin.online_exams.results', $this->exam) . '?submission=' . $this->submission . '#submission-' . $this->submission,
            $notification->action_url
        );
    }
}
