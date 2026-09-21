<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\OnlineExamTestHelper;
use Tests\TestCase;

class OnlineExamBatch2DUXTest extends TestCase
{
    use OnlineExamTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootOnlineExamTestSchema();
        DB::table('global_settings')->updateOrInsert(
            ['key' => 'role_perm_2'],
            ['value' => json_encode(['view_online_exams', 'publish_online_exams', 'edit_all_online_exams']), 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function test_admin_html_publish_redirects_with_readiness_errors(): void
    {
        $admin = $this->makeUser(2, 1);
        $exam = $this->makeExam(['total_marks' => 100, 'is_published' => 0, 'workflow_state' => 'draft', 'created_by' => $admin->id, 'creator_id' => $admin->id]);
        $this->makeQuestion($exam, ['marks' => 4]);

        $this->actingAs($admin)->post(route('admin.online_exams.publish', $exam))
            ->assertRedirect()->assertSessionHasErrors('readiness');
        $this->assertDatabaseHas('online_exams', ['id' => $exam, 'is_published' => 0]);
    }

    public function test_admin_json_publish_keeps_structured_422(): void
    {
        $admin = $this->makeUser(2, 1);
        $exam = $this->makeExam(['total_marks' => 100, 'is_published' => 0, 'workflow_state' => 'draft', 'created_by' => $admin->id, 'creator_id' => $admin->id]);
        $this->makeQuestion($exam, ['marks' => 4]);

        $this->actingAs($admin)->postJson(route('admin.online_exams.publish', $exam))
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('errors.readiness.1', 'Total question marks (4) must equal exam total marks (100).');
    }
}
