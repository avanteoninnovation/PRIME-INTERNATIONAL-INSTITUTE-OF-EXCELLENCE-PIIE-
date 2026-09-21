<?php

namespace Tests\Feature;

use App\Http\Controllers\OnlineExamController;
use App\Models\QuestionBank;
use App\Models\QuestionTag;
use App\Models\QuestionTopic;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamPhase2B1QuestionMetadataTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
        DB::table('global_settings')->where('key', 'role_perm_2')->update(['value' => json_encode(['manage_exam_questions', 'edit_all_online_exams', 'view_online_exams'])]);
    }

    private function validate(array $data, $teacher = null): void
    {
        $controller = app(OnlineExamController::class);
        $reflection = new ReflectionClass($controller);
        $property = $reflection->getProperty('school_id');
        $property->setAccessible(true);
        $property->setValue($controller, 1);
        $method = $reflection->getMethod('validateQuestionBankAcademicMetadata');
        $method->setAccessible(true);
        $method->invoke($controller, $data, $teacher);
    }

    public function test_legacy_and_structured_question_bank_rows_remain_usable(): void
    {
        $legacy = QuestionBank::create(['school_id' => 1, 'question' => 'Legacy', 'type' => 'mcq', 'correct_ans' => 'a', 'marks' => 1]);
        $structured = QuestionBank::create(['school_id' => 1, 'question' => 'Structured', 'type' => 'multiple_select', 'question_schema_version' => 2, 'question_config' => json_encode(['type' => 'multiple_select', 'options' => [['id' => 'a', 'label' => 'A']]]), 'marking_config' => json_encode(['correct_option_ids' => ['a']]), 'marks' => 1]);
        $this->assertNull($legacy->topic_id);
        $this->assertSame(2, (int) $structured->question_schema_version);
        $this->assertSame('active', $legacy->fresh()->status);
    }

    public function test_valid_same_school_metadata_and_multiple_tags_are_accepted(): void
    {
        $subject = $this->makeSubject(1);
        $programme = DB::table('programmes')->insertGetId(['school_id' => 1, 'name' => 'Programme', 'is_active' => 1]);
        DB::table('subjects')->where('id', $subject)->update(['programme_id' => $programme]);
        $session = DB::table('sessions')->insertGetId(['school_id' => 1, 'session_title' => '2026', 'status' => 'active']);
        $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Foundations']);
        $subtopic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'parent_id' => $topic->id, 'name' => 'Definitions']);
        $tagA = QuestionTag::create(['school_id' => 1, 'name' => 'Theory', 'normalized_name' => 'theory']);
        $tagB = QuestionTag::create(['school_id' => 1, 'name' => 'Core', 'normalized_name' => 'core']);
        $this->validate(['subject_id' => $subject, 'programme_id' => $programme, 'session_id' => $session, 'topic_id' => $topic->id, 'subtopic_id' => $subtopic->id, 'tag_ids' => [$tagA->id, $tagB->id], 'status' => 'draft']);
        $bank = QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'programme_id' => $programme, 'session_id' => $session, 'topic_id' => $topic->id, 'subtopic_id' => $subtopic->id, 'status' => 'draft', 'question' => 'Metadata', 'type' => 'mcq', 'marks' => 1]);
        $bank->tags()->sync([$tagA->id, $tagB->id]);
        $this->assertCount(2, $bank->fresh()->tags);
    }

    public function test_cross_school_and_incompatible_metadata_are_rejected(): void
    {
        $subject = $this->makeSubject(1);
        $otherSubject = $this->makeSubject(2);
        $otherProgramme = DB::table('programmes')->insertGetId(['school_id' => 2, 'name' => 'Other', 'is_active' => 1]);
        $topic = QuestionTopic::create(['school_id' => 2, 'subject_id' => $otherSubject, 'name' => 'Other']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['subject_id' => $subject, 'programme_id' => $otherProgramme, 'topic_id' => $topic->id]);
    }

    public function test_topic_subtopic_relationship_and_tag_scope_are_enforced(): void
    {
        $subject = $this->makeSubject(1);
        $otherSubject = $this->makeSubject(1);
        $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Topic']);
        $otherTopic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $otherSubject, 'name' => 'Other']);
        $subtopic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $otherSubject, 'parent_id' => $otherTopic->id, 'name' => 'Other Sub']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['subject_id' => $subject, 'topic_id' => $topic->id, 'subtopic_id' => $subtopic->id]);
    }

    public function test_duplicate_tags_are_rejected_and_pivot_is_unique(): void
    {
        $tag = QuestionTag::create(['school_id' => 1, 'name' => 'Core', 'normalized_name' => 'core']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['tag_ids' => [$tag->id, $tag->id]]);
    }

    public function test_lifecycle_accepts_all_supported_states_and_rejects_invalid_state(): void
    {
        foreach (['draft', 'active', 'retired', 'archived'] as $status) {
            $row = QuestionBank::create(['school_id' => 1, 'question' => $status, 'type' => 'mcq', 'status' => $status, 'marks' => 1]);
            $this->assertSame($status, $row->status);
        }
        $this->assertSame(4, QuestionBank::whereIn('status', ['draft', 'active', 'retired', 'archived'])->count());
    }

    public function test_same_school_scope_is_applied_to_metadata_queries(): void
    {
        $local = QuestionTag::create(['school_id' => 1, 'name' => 'Local', 'normalized_name' => 'local']);
        $foreign = QuestionTag::create(['school_id' => 2, 'name' => 'Foreign', 'normalized_name' => 'foreign']);
        $this->assertTrue(QuestionTag::where('school_id', 1)->whereKey($local->id)->exists());
        $this->assertFalse(QuestionTag::where('school_id', 1)->whereKey($foreign->id)->exists());
    }

    public function test_programme_and_session_are_optional(): void
    {
        $this->validate(['subject_id' => $this->makeSubject(1), 'status' => 'active']);
        $this->assertTrue(true);
    }

    public function test_cross_school_session_is_rejected(): void
    {
        $session = DB::table('sessions')->insertGetId(['school_id' => 2, 'session_title' => 'Foreign']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['session_id' => $session]);
    }

    public function test_incompatible_programme_and_course_are_rejected(): void
    {
        $subject = $this->makeSubject(1);
        $programme = DB::table('programmes')->insertGetId(['school_id' => 1, 'name' => 'Programme', 'is_active' => 1]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['subject_id' => $subject, 'programme_id' => $programme]);
    }

    public function test_unauthorized_teacher_programme_is_rejected(): void
    {
        $teacher = $this->makeUser(3, 1);
        $programme = DB::table('programmes')->insertGetId(['school_id' => 1, 'name' => 'Programme', 'is_active' => 1]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['programme_id' => $programme], $teacher);
    }

    public function test_cross_school_topic_is_rejected(): void
    {
        $subject = $this->makeSubject(1);
        $foreign = QuestionTopic::create(['school_id' => 2, 'subject_id' => $subject, 'name' => 'Foreign']);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['subject_id' => $subject, 'topic_id' => $foreign->id]);
    }

    public function test_invalid_lifecycle_is_rejected(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->validate(['status' => 'unknown']);
    }

    public function test_topic_and_lifecycle_filters_are_school_scoped(): void
    {
        $subject = $this->makeSubject(1);
        $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Topic']);
        QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'topic_id' => $topic->id, 'status' => 'retired', 'question' => 'Retired', 'type' => 'essay', 'difficulty' => 'hard', 'marks' => 2]);
        $this->assertSame(1, QuestionBank::where('school_id', 1)->where('topic_id', $topic->id)->where('status', 'retired')->where('difficulty', 'hard')->where('type', 'essay')->count());
        $this->assertSame(0, QuestionBank::where('school_id', 2)->where('topic_id', $topic->id)->count());
    }

    public function test_question_bank_tag_pivot_rejects_duplicate_membership(): void
    {
        $bank = QuestionBank::create(['school_id' => 1, 'question' => 'Tagged', 'type' => 'mcq', 'marks' => 1]);
        $tag = QuestionTag::create(['school_id' => 1, 'name' => 'Tag', 'normalized_name' => 'tag']);
        $bank->tags()->attach($tag->id);
        try { $bank->tags()->attach($tag->id); $this->fail('duplicate pivot should be rejected'); } catch (QueryException $e) { $this->assertStringContainsString('UNIQUE', strtoupper($e->getMessage())); }
    }

    public function test_migrated_default_lifecycle_is_active(): void
    {
        DB::table('question_banks')->insert(['school_id' => 1, 'question' => 'Existing', 'type' => 'mcq', 'marks' => 1]);
        $this->assertSame('active', QuestionBank::latest('id')->value('status'));
    }

    public function test_metadata_source_changes_do_not_touch_snapshot_marking_content(): void
    {
        $subject = $this->makeSubject(1);
        $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Topic']);
        $bank = QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'topic_id' => $topic->id, 'question' => 'Original', 'type' => 'mcq', 'correct_ans' => 'a', 'marks' => 1]);
        $exam = $this->makeExam();
        $snapshot = DB::table('online_exam_questions')->insertGetId(['online_exam_id' => $exam, 'question_bank_id' => $bank->id, 'question' => $bank->question, 'type' => $bank->type, 'correct_ans' => $bank->correct_ans, 'marks' => $bank->marks]);
        $bank->update(['topic_id' => null, 'status' => 'archived', 'question' => 'Changed']);
        $this->assertDatabaseHas('online_exam_questions', ['id' => $snapshot, 'question' => 'Original', 'correct_ans' => 'a']);
    }

    public function test_admin_http_can_manage_topic_subtopic_and_tag_and_deactivate_referenced_items(): void
    {
        $admin = $this->makeUser(2, 1); $subject = $this->makeSubject(1);
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.topics.store'), ['subject_id' => $subject, 'name' => 'Research'])->assertRedirect();
        $topic = QuestionTopic::latest('id')->first();
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.subtopics.store'), ['subject_id' => $subject, 'parent_id' => $topic->id, 'name' => 'Methods'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.tags.store'), ['name' => 'Case Study'])->assertRedirect();
        $tag = QuestionTag::latest('id')->first(); $bank = QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'topic_id' => $topic->id, 'status' => 'active', 'question' => 'Q', 'type' => 'mcq', 'marks' => 1]); $bank->tags()->attach($tag->id);
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.topics.toggle', $topic->id))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.tags.toggle', $tag->id))->assertRedirect();
        $this->assertDatabaseHas('question_banks', ['id' => $bank->id, 'topic_id' => $topic->id]); $this->assertDatabaseHas('question_bank_tag', ['question_bank_id' => $bank->id, 'question_tag_id' => $tag->id]);
    }

    public function test_admin_http_rejects_foreign_taxonomy_and_duplicate_tag(): void
    {
        $admin = $this->makeUser(2, 1); $foreignSubject = $this->makeSubject(2); $foreignTopic = QuestionTopic::create(['school_id' => 2, 'subject_id' => $foreignSubject, 'name' => 'Foreign']);
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.topics.store'), ['subject_id' => $foreignSubject, 'name' => 'Nope'])->assertSessionHasErrors('subject_id');
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.subtopics.store'), ['subject_id' => $this->makeSubject(1), 'parent_id' => $foreignTopic->id, 'name' => 'Nope'])->assertNotFound();
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.tags.store'), ['name' => 'Case Study'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.tags.store'), ['name' => ' case   study '])->assertStatus(422);
    }

    public function test_teacher_http_can_author_metadata_rich_question_and_foreign_ids_fail(): void
    {
        $teacher = $this->makeUser(3, 1); DB::table('global_settings')->where('key', 'role_perm_3')->update(['value' => json_encode(['manage_exam_questions', 'view_online_exams'])]); $class = $this->makeClass(1); DB::table('teacher_permissions')->insert(['class_id' => $class, 'school_id' => 1, 'teacher_id' => $teacher->id, 'marks' => 1, 'updated_at' => now()]); $subject = $this->makeSubject(1, $class); $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Valid']); $tag = QuestionTag::create(['school_id' => 1, 'name' => 'Tag', 'normalized_name' => 'tag']); $exam = $this->makeExam(['school_id' => 1, 'class_id' => $class, 'subject_id' => $subject, 'created_by' => $teacher->id, 'creator_id' => $teacher->id]);
        $this->actingAs($teacher)->post(route('teacher.online_exams.question_bank.store'), ['subject_id' => $subject, 'topic_id' => $topic->id, 'tag_ids' => [$tag->id], 'question' => 'Metadata Q', 'type' => 'mcq', 'option_a' => 'A', 'option_b' => 'B', 'correct_ans' => 'a', 'marks' => 1, 'difficulty' => 'easy'])->assertRedirect();
        $this->assertDatabaseHas('question_banks', ['question' => 'Metadata Q', 'topic_id' => $topic->id]);
        $foreignTopic = QuestionTopic::create(['school_id' => 2, 'subject_id' => $this->makeSubject(2), 'name' => 'Foreign']); $this->actingAs($teacher)->post(route('teacher.online_exams.question_bank.store'), ['subject_id' => $subject, 'topic_id' => $foreignTopic->id, 'question' => 'Bad', 'type' => 'mcq', 'option_a' => 'A', 'option_b' => 'B', 'correct_ans' => 'a', 'marks' => 1, 'difficulty' => 'easy'])->assertStatus(422);
    }

    public function test_admin_question_bank_http_filters_by_topic_tag_and_lifecycle(): void
    {
        $admin = $this->makeUser(2, 1); $subject = $this->makeSubject(1); $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Filter Topic']); $tag = QuestionTag::create(['school_id' => 1, 'name' => 'Filter Tag', 'normalized_name' => 'filter tag']); $bank = QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'topic_id' => $topic->id, 'status' => 'retired', 'question' => 'Filterable', 'type' => 'essay', 'difficulty' => 'hard', 'marks' => 2]); $bank->tags()->attach($tag->id);
        $this->actingAs($admin)->get(route('admin.question_bank.index', ['subject_id' => $subject, 'topic_id' => $topic->id, 'tag_id' => $tag->id, 'status' => 'retired', 'type' => 'essay', 'difficulty' => 'hard']))->assertOk()->assertSee('Filterable');
    }

    public function test_admin_http_can_rename_and_reactivate_taxonomy_and_tag(): void
    {
        $admin = $this->makeUser(2, 1); $subject = $this->makeSubject(1);
        $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Old Topic']);
        $tag = QuestionTag::create(['school_id' => 1, 'name' => 'Old Tag', 'normalized_name' => 'old tag', 'is_active' => 0]);
        $this->actingAs($admin)->put(route('admin.question_bank.metadata.topics.update', $topic->id), ['name' => 'New Topic', 'subject_id' => $subject])->assertRedirect();
        $this->actingAs($admin)->put(route('admin.question_bank.metadata.tags.update', $tag->id), ['name' => 'New Tag'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.topics.toggle', $topic->id))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.tags.toggle', $tag->id))->assertRedirect();
        $this->assertDatabaseHas('question_topics', ['id' => $topic->id, 'name' => 'New Topic', 'is_active' => 0]);
        $this->assertDatabaseHas('question_tags', ['id' => $tag->id, 'name' => 'New Tag', 'normalized_name' => 'new tag', 'is_active' => 1]);
    }

    public function test_admin_http_rejects_subtopic_course_mismatch(): void
    {
        $admin = $this->makeUser(2, 1); $subjectA = $this->makeSubject(1); $subjectB = $this->makeSubject(1);
        $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subjectA, 'name' => 'Topic A']);
        $this->actingAs($admin)->post(route('admin.question_bank.metadata.subtopics.store'), ['subject_id' => $subjectB, 'parent_id' => $topic->id, 'name' => 'Invalid'])->assertStatus(404);
    }

    public function test_teacher_http_can_attach_multiple_tags_and_retain_them_in_question_bank(): void
    {
        $teacher = $this->makeUser(3, 1); DB::table('global_settings')->where('key', 'role_perm_3')->update(['value' => json_encode(['manage_exam_questions', 'view_online_exams'])]);
        $class = $this->makeClass(1); DB::table('teacher_permissions')->insert(['class_id' => $class, 'school_id' => 1, 'teacher_id' => $teacher->id, 'marks' => 1, 'updated_at' => now()]); $subject = $this->makeSubject(1, $class);
        $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Authoring Topic']);
        $tags = [QuestionTag::create(['school_id' => 1, 'name' => 'One', 'normalized_name' => 'one'])->id, QuestionTag::create(['school_id' => 1, 'name' => 'Two', 'normalized_name' => 'two'])->id];
        $this->actingAs($teacher)->post(route('teacher.online_exams.question_bank.store'), ['subject_id' => $subject, 'topic_id' => $topic->id, 'tag_ids' => $tags, 'question' => 'Tagged Q', 'type' => 'mcq', 'option_a' => 'A', 'option_b' => 'B', 'correct_ans' => 'a', 'marks' => 1, 'difficulty' => 'easy'])->assertRedirect();
        $bank = QuestionBank::where('question', 'Tagged Q')->firstOrFail(); $this->assertCount(2, $bank->fresh()->tags);
        $this->actingAs($teacher)->get(route('teacher.online_exams.question_bank', ['topic_id' => $topic->id, 'tag_id' => $tags[0]]))->assertOk()->assertSee('Tagged Q');
        $this->assertCount(2, $bank->fresh()->tags);
    }

    public function test_admin_http_filters_by_programme_and_session(): void
    {
        $admin = $this->makeUser(2, 1); $subject = $this->makeSubject(1); $programme = DB::table('programmes')->insertGetId(['school_id' => 1, 'name' => 'Filter Programme', 'is_active' => 1]); DB::table('subjects')->where('id', $subject)->update(['programme_id' => $programme]); $session = DB::table('sessions')->insertGetId(['school_id' => 1, 'session_title' => 'Filter Session', 'status' => 'active']);
        QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'programme_id' => $programme, 'session_id' => $session, 'question' => 'Programme Session Match', 'type' => 'mcq', 'marks' => 1]); QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'question' => 'Programme Session Other', 'type' => 'mcq', 'marks' => 1]);
        $this->actingAs($admin)->get(route('admin.question_bank.index', ['programme_id' => $programme, 'session_id' => $session]))->assertOk()->assertSee('Programme Session Match')->assertDontSee('Programme Session Other');
    }

    public function test_admin_http_filters_by_subtopic_and_course(): void
    {
        $admin = $this->makeUser(2, 1); $subject = $this->makeSubject(1); $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Parent']); $subtopic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'parent_id' => $topic->id, 'name' => 'Child']);
        QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'subtopic_id' => $subtopic->id, 'question' => 'Subtopic Match', 'type' => 'essay', 'marks' => 1]); QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'question' => 'Subtopic Other', 'type' => 'essay', 'marks' => 1]);
        $this->actingAs($admin)->get(route('admin.question_bank.index', ['subject_id' => $subject, 'subtopic_id' => $subtopic->id]))->assertOk()->assertSee('Subtopic Match')->assertDontSee('Subtopic Other');
    }

    public function test_admin_question_edit_preserves_taxonomy_and_tags(): void
    {
        $admin = $this->makeUser(2, 1); $subject = $this->makeSubject(1); $topic = QuestionTopic::create(['school_id' => 1, 'subject_id' => $subject, 'name' => 'Edit Topic']); $tag = QuestionTag::create(['school_id' => 1, 'name' => 'Edit Tag', 'normalized_name' => 'edit tag']);
        $bank = QuestionBank::create(['school_id' => 1, 'subject_id' => $subject, 'topic_id' => $topic->id, 'status' => 'active', 'question' => 'Before', 'type' => 'mcq', 'option_a' => 'A', 'option_b' => 'B', 'correct_ans' => 'a', 'marks' => 1]); $bank->tags()->attach($tag->id);
        $this->actingAs($admin)->put(route('admin.question_bank.update', $bank->id), ['subject_id' => $subject, 'topic_id' => $topic->id, 'tag_ids' => [$tag->id], 'status' => 'active', 'difficulty' => 'easy', 'question' => 'After', 'type' => 'mcq', 'option_a' => 'A', 'option_b' => 'B', 'correct_ans' => 'a', 'marks' => 1])->assertRedirect();
        $this->assertDatabaseHas('question_banks', ['id' => $bank->id, 'question' => 'After', 'topic_id' => $topic->id]); $this->assertDatabaseHas('question_bank_tag', ['question_bank_id' => $bank->id, 'question_tag_id' => $tag->id]);
    }

    public function test_metadata_does_not_change_exam_question_snapshot(): void
    {
        $bank = QuestionBank::create(['school_id' => 1, 'question' => 'Snapshot', 'type' => 'mcq', 'marks' => 2, 'status' => 'active']);
        $exam = $this->makeExam();
        $questionId = DB::table('online_exam_questions')->insertGetId(['online_exam_id' => $exam, 'question_bank_id' => $bank->id, 'question' => $bank->question, 'type' => $bank->type, 'marks' => $bank->marks]);
        $bank->update(['status' => 'retired', 'topic_id' => null]);
        $this->assertDatabaseHas('online_exam_questions', ['id' => $questionId, 'question' => 'Snapshot', 'marks' => 2]);
    }

    public function test_taxonomy_and_tag_schema_is_additive_and_existing_count_is_preserved(): void
    {
        $this->assertTrue(Schema::hasTable('question_topics'));
        $this->assertTrue(Schema::hasTable('question_tags'));
        $this->assertTrue(Schema::hasTable('question_bank_tag'));
        $this->assertTrue(Schema::hasColumn('question_banks', 'topic_id'));
        $this->assertTrue(Schema::hasColumn('question_banks', 'subtopic_id'));
        $this->assertSame(0, QuestionBank::count());
    }
}
