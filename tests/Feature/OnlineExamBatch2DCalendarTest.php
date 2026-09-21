<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamBatch2DCalendarTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
        Schema::create('academic_calendar', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->string('title');
            $table->string('event_type'); $table->date('event_date'); $table->date('end_date')->nullable();
            $table->string('color')->nullable(); $table->text('description')->nullable();
            $table->boolean('is_public')->default(true); $table->timestamps();
        });
        DB::table('global_settings')->updateOrInsert(
            ['key' => 'role_perm_2'],
            ['value' => json_encode(['view_online_exams', 'publish_online_exams', 'edit_all_online_exams']), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_publishing_does_not_copy_noticeboard_or_calendar(): void
    {
        $admin = $this->makeUser(2, 1);
        $exam = $this->makeExam(['is_published' => 0, 'workflow_state' => 'draft', 'created_by' => $admin->id]);
        $this->makeQuestion($exam, ['marks' => 10]);

        $this->actingAs($admin)->post(route('admin.online_exams.publish', $exam))->assertRedirect();
        $this->assertDatabaseCount('noticeboard', 0);
        $this->assertDatabaseCount('academic_calendar', 0);
    }

    public function test_existing_calendar_event_is_preserved_and_exam_is_dynamic(): void
    {
        DB::table('academic_calendar')->insert([
            'school_id' => 1, 'title' => 'Existing event', 'event_type' => 'holiday',
            'event_date' => now()->toDateString(), 'is_public' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $exam = $this->makeExam(['title' => 'Dynamic exam']);
        $admin = $this->makeUser(2, 1);
        $response = $this->actingAs($admin)->getJson(route('calendar.events_json'));
        $response->assertOk()->assertJsonFragment(['title' => 'Existing event'])->assertJsonFragment(['title' => 'Dynamic exam']);
        $response->assertJsonFragment(['type' => 'online_exam']);
        $response->assertJsonMissing(['correct_ans' => 'A']);
        $this->assertDatabaseCount('academic_calendar', 1);
    }

    public function test_class_exam_is_visible_only_to_enrolled_student(): void
    {
        $classA = $this->makeClass(1); $classB = $this->makeClass(1);
        $studentA = $this->makeUser(7, 1); $studentB = $this->makeUser(7, 1);
        $this->enrollStudent($studentA->id, 1, $classA); $this->enrollStudent($studentB->id, 1, $classB);
        $exam = $this->makeExam(['title' => 'Class A exam', 'class_id' => $classA]);

        $this->actingAs($studentA)->getJson(route('calendar.events_json'))->assertJsonFragment(['title' => 'Class A exam']);
        $this->actingAs($studentB)->getJson(route('calendar.events_json'))->assertJsonMissing(['title' => 'Class A exam']);
        $this->assertDatabaseCount('academic_calendar', 0);
        $this->assertDatabaseCount('noticeboard', 0);
    }

    public function test_calendar_events_keep_school_isolation(): void
    {
        $student = $this->makeUser(7, 2);
        $this->makeExam(['title' => 'School One exam', 'school_id' => 1]);

        $this->actingAs($student)->getJson(route('calendar.events_json'))
            ->assertOk()->assertJsonMissing(['title' => 'School One exam']);
    }

    public function test_draft_unpublished_and_cancelled_exams_are_not_dynamic_events(): void
    {
        $student = $this->makeUser(7, 1);
        $this->enrollStudent($student->id, 1, $this->makeClass(1));
        $draft = $this->makeExam(['title' => 'Draft', 'workflow_state' => 'draft', 'is_published' => 0]);
        $unpublished = $this->makeExam(['title' => 'Unpublished', 'workflow_state' => 'draft', 'is_published' => 0]);
        $cancelled = $this->makeExam(['title' => 'Cancelled', 'workflow_state' => 'cancelled', 'is_published' => 0]);

        $response = $this->actingAs($student)->getJson(route('calendar.events_json'));
        $response->assertJsonMissing(['title' => 'Draft'])->assertJsonMissing(['title' => 'Unpublished'])->assertJsonMissing(['title' => 'Cancelled']);
        $this->assertDatabaseHas('online_exams', ['id' => $draft, 'workflow_state' => 'draft']);
        $this->assertDatabaseHas('online_exams', ['id' => $cancelled, 'workflow_state' => 'cancelled']);
    }

    public function test_dynamic_event_reflects_rename_and_reschedule_without_copy(): void
    {
        $student = $this->makeUser(7, 1); $this->enrollStudent($student->id, 1, $this->makeClass(1));
        $exam = $this->makeExam(['title' => 'Original title']);
        $this->actingAs($student)->getJson(route('calendar.events_json'))->assertJsonFragment(['title' => 'Original title']);
        $newStart = now()->addDays(2)->startOfHour();
        DB::table('online_exams')->where('id', $exam)->update(['title' => 'Renamed title', 'start_datetime' => $newStart]);
        $response = $this->actingAs($student)->getJson(route('calendar.events_json'));
        $response->assertJsonFragment(['title' => 'Renamed title'])->assertJsonMissing(['title' => 'Original title']);
        $this->assertDatabaseCount('academic_calendar', 0);
    }
}
