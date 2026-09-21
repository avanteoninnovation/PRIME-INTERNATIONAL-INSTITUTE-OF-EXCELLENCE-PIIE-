<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamBatch2BRevisionTest extends TestCase
{
    use OnlineExamTestHelper;

    private $student;
    private int $exam;
    private int $question;
    private int $submission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
        $this->student = $this->makeUser(7, 1);
        $this->exam = $this->makeExam();
        $this->question = $this->makeQuestion($this->exam);
        $this->submission = $this->makeSubmission(['online_exam_id' => $this->exam, 'student_id' => $this->student->id, 'school_id' => 1]);
        $this->actingAs($this->student);
    }

    private function saveRevision($revision, ?string $option = 'a', array $extra = [])
    {
        return $this->postJson(route('student.online_exam.save_answer', $this->submission), $extra + [
            'submission_id' => $this->submission, 'question_id' => $this->question,
            'answer_revision' => $revision, 'selected_option' => $option,
        ]);
    }

    public function test_first_and_higher_revisions_are_accepted_with_authoritative_metadata(): void
    {
        $this->saveRevision(1)->assertOk()->assertJsonPath('answer_revision', 1)
            ->assertJsonStructure(['status', 'submission_id', 'question_id', 'answer_revision', 'answer_updated_at', 'server_time', 'expires_at']);
        $this->saveRevision(6, 'b')->assertOk()->assertJsonPath('answer_revision', 6);
        $this->assertDatabaseCount('online_exam_answers', 1);
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 6, 'selected_option' => 'b']);
    }

    public function test_tab_b_revision_six_before_tab_a_revision_five_cannot_be_overwritten(): void
    {
        $this->saveRevision(6, 'b')->assertOk();
        $before = DB::table('online_exam_answers')->first();
        $this->saveRevision(5, 'a')->assertStatus(409)->assertExactJson([
            'status' => 'stale', 'submission_id' => $this->submission, 'question_id' => $this->question,
            'answer_revision' => 6, 'selected_option' => 'b', 'answer_text' => null,
        ]);
        $this->assertEquals($before, DB::table('online_exam_answers')->first());
    }

    public function test_equal_identical_is_idempotent_without_touching_answer_timestamp(): void
    {
        $this->saveRevision(4)->assertOk();
        $before = DB::table('online_exam_answers')->first();
        $this->travel(2)->seconds();
        $this->saveRevision(4)->assertOk()->assertJsonPath('status', 'idempotent');
        $this->assertEquals($before, DB::table('online_exam_answers')->first());
    }

    public function test_equal_different_payload_conflicts_and_lower_revision_is_stale(): void
    {
        $this->saveRevision(4)->assertOk();
        $this->saveRevision(4, 'b')->assertStatus(409)->assertJsonPath('status', 'conflict');
        $this->saveRevision(3)->assertStatus(409)->assertJsonPath('status', 'stale');
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 4, 'selected_option' => 'a']);
    }

    public function test_retried_first_creation_has_one_row_and_unique_constraint_remains(): void
    {
        $this->saveRevision(0)->assertOk();
        $this->saveRevision(0)->assertOk()->assertJsonPath('status', 'idempotent');
        $this->assertDatabaseCount('online_exam_answers', 1);
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('online_exam_answers')->insert(['submission_id' => $this->submission, 'question_id' => $this->question]);
    }

    public function test_another_student_and_school_cannot_read_conflict_or_write(): void
    {
        $this->saveRevision(4)->assertOk();
        foreach ([$this->makeUser(7, 1), $this->makeUser(7, 2)] as $other) {
            $this->actingAs($other);
            $this->saveRevision(4, 'b')->assertForbidden()->assertJsonMissing(['selected_option' => 'a']);
            $this->saveRevision(99)->assertForbidden();
        }
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 4]);
    }

    public function test_revision_cannot_bypass_finalization_or_expiry(): void
    {
        $this->saveRevision(1)->assertOk();
        foreach (['finalized', 'submitted', 'timed_out', 'pending_manual', 'result_published'] as $status) {
            DB::table('online_exam_submissions')->where('id', $this->submission)->update(['status' => $status]);
            $this->saveRevision(99, 'b')->assertStatus(422);
        }
        DB::table('online_exam_submissions')->where('id', $this->submission)->update(['status' => 'in_progress', 'expires_at' => now()->subSecond()]);
        $this->saveRevision(99, 'b')->assertStatus(422);
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 1, 'selected_option' => 'a']);
    }

    public function test_blank_clearing_requires_new_revision(): void
    {
        $this->saveRevision(1)->assertOk();
        $this->saveRevision(1, null)->assertStatus(409);
        $this->saveRevision(2, null)->assertOk();
        $this->saveRevision(2, null)->assertOk()->assertJsonPath('status', 'idempotent');
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 2, 'selected_option' => null]);
    }

    public function test_written_payload_is_part_of_equal_revision_identity(): void
    {
        DB::table('online_exam_questions')->where('id', $this->question)->update(['type' => 'essay']);
        $this->saveRevision(1, null, ['answer_text' => 'First draft'])->assertOk();
        $this->saveRevision(1, null, ['answer_text' => 'First draft'])->assertOk()->assertJsonPath('status', 'idempotent');
        $this->saveRevision(1, null, ['answer_text' => 'Changed draft'])->assertStatus(409);
        $this->saveRevision(2, null, ['answer_text' => ''])->assertOk();
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 2, 'answer_text' => null]);
    }

    public function test_exam_school_must_match_even_if_submission_owner_matches(): void
    {
        DB::table('online_exams')->where('id', $this->exam)->update(['school_id' => 2]);
        $this->saveRevision(99)->assertForbidden();
        $this->assertDatabaseCount('online_exam_answers', 0);
    }

    public function test_heartbeat_expiry_then_timeout_blocks_late_answer(): void
    {
        $this->saveRevision(6)->assertOk();
        DB::table('online_exam_submissions')->where('id', $this->submission)->update(['expires_at' => now()->subSecond()]);
        $this->postJson(route('student.online_exam.heartbeat', $this->submission))->assertOk()->assertJsonPath('expired', true);
        $this->saveRevision(5, 'b')->assertStatus(422);
        $this->saveRevision(7, 'b')->assertStatus(422);
        $this->post(route('student.online_exam.timeout_submit', $this->submission))->assertRedirect();
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 6, 'selected_option' => 'a']);
        $this->assertDatabaseMissing('online_exam_submissions', ['id' => $this->submission, 'status' => 'in_progress']);
    }

    public function test_revision_validation_and_question_and_route_boundaries(): void
    {
        foreach ([null, -1, 1.5, 'abc', 4294967296] as $invalid) {
            $this->saveRevision($invalid)->assertStatus(422)->assertJsonValidationErrors('answer_revision');
        }
        $this->saveRevision(1, 'a', ['question_id' => $this->makeQuestion($this->makeExam())])->assertStatus(422);
        $this->saveRevision(1, 'a', ['submission_id' => 999999])->assertStatus(422);
        $this->saveRevision(4294967295)->assertOk();
    }

    public function test_manual_submission_blocks_late_stale_and_higher_saves(): void
    {
        $this->saveRevision(6)->assertOk();
        $this->post(route('student.online_exam.submit', $this->exam), ['submission_id' => $this->submission])->assertRedirect();
        $this->saveRevision(5, 'b')->assertStatus(422);
        $this->saveRevision(7, 'b')->assertStatus(422);
        $this->post(route('student.online_exam.timeout_submit', $this->submission))->assertRedirect();
        $this->assertDatabaseHas('online_exam_answers', ['answer_revision' => 6, 'selected_option' => 'a']);
    }

    public function test_migration_round_trip_preserves_legacy_data_and_schema(): void
    {
        $migration = require database_path('migrations/2026_09_19_000004_add_answer_revision_to_online_exam_answers.php');
        $migration->down();
        $columns = DB::select('PRAGMA table_info(online_exam_answers)');
        $indexes = DB::select('PRAGMA index_list(online_exam_answers)');
        DB::table('online_exam_answers')->insert(['submission_id' => $this->submission, 'question_id' => $this->question, 'selected_option' => 'a', 'created_at' => now(), 'updated_at' => now()]);
        $legacy = (array) DB::table('online_exam_answers')->first();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('online_exam_answers', 'answer_revision'));
        $this->assertDatabaseHas('online_exam_answers', $legacy + ['answer_revision' => 0]);
        $this->saveRevision(0)->assertOk()->assertJsonPath('status', 'idempotent');
        $this->saveRevision(0, 'b')->assertStatus(409);
        $migration->down();
        $this->assertEquals($columns, DB::select('PRAGMA table_info(online_exam_answers)'));
        $this->assertEquals($indexes, DB::select('PRAGMA index_list(online_exam_answers)'));
        $this->assertSame($legacy, (array) DB::table('online_exam_answers')->first());
    }
}
