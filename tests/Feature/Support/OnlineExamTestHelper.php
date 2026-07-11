<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

trait OnlineExamTestHelper
{
    protected function bootOnlineExamTestSchema(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->unsignedInteger('role_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('account_status')->default('active');
            $table->text('menu_permission')->nullable();
            $table->timestamps();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->timestamps();
        });

        Schema::create('online_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->string('exam_type')->default('cat');
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->integer('duration_mins')->default(60);
            $table->integer('total_marks')->default(100);
            $table->integer('pass_mark')->default(50);
            $table->unsignedTinyInteger('max_attempts')->default(1);
            $table->text('instructions')->nullable();
            $table->tinyInteger('is_published')->default(0);
            $table->tinyInteger('auto_submit')->default(1);
            $table->string('workflow_state', 30)->default('draft')->index();
            $table->boolean('shuffle_questions')->default(false);
            $table->boolean('shuffle_options')->default(false);
            $table->boolean('allow_previous_navigation')->default(true);
            $table->string('result_release_policy', 30)->default('immediate');
            $table->boolean('webcam_required')->default(false);
            $table->boolean('fullscreen_required')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('creator_id')->nullable();
            $table->unsignedBigInteger('updater_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('online_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('online_exam_id')->index();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->text('question')->nullable();
            $table->string('type')->default('mcq');
            $table->text('option_a')->nullable();
            $table->text('option_b')->nullable();
            $table->text('option_c')->nullable();
            $table->text('option_d')->nullable();
            $table->string('correct_ans', 255)->nullable();
            $table->tinyInteger('marks')->default(1);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('online_exam_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('online_exam_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->json('answers')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->unsignedInteger('attempt_no')->default(1);
            $table->dateTime('started_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->dateTime('last_activity_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('submitted_via', 20)->nullable();
            $table->string('status', 40)->default('in_progress');
            $table->dateTime('timeout_at')->nullable();
            $table->integer('total_marks_snapshot')->nullable();
            $table->decimal('objective_score', 8, 2)->nullable();
            $table->decimal('manual_score', 8, 2)->nullable();
            $table->boolean('passed')->nullable();
            $table->dateTime('camera_consent_at')->nullable();
            $table->boolean('camera_permission_granted')->default(false);
            $table->dateTime('camera_ready_at')->nullable();
            $table->dateTime('fullscreen_started_at')->nullable();
            $table->string('browser_session_token', 80)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('online_exam_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id')->index();
            $table->unsignedBigInteger('question_id')->index();
            $table->string('selected_option', 10)->nullable();
            $table->text('answer_text')->nullable();
            $table->decimal('awarded_marks', 8, 2)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->unsignedBigInteger('marked_by')->nullable();
            $table->dateTime('marked_at')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamps();
            $table->unique(['submission_id', 'question_id']);
        });

        Schema::create('online_exam_proctoring_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('submission_id')->index();
            $table->string('event_type', 50);
            $table->dateTime('event_time')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->string('review_status', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('question_bank', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('question');
            $table->string('type', 30)->default('mcq');
            $table->text('option_a')->nullable();
            $table->text('option_b')->nullable();
            $table->text('option_c')->nullable();
            $table->text('option_d')->nullable();
            $table->string('correct_ans', 255)->nullable();
            $table->integer('marks')->default(1);
            $table->string('difficulty', 20)->default('easy');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action');
            $table->string('module');
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('language', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phrase');
            $table->text('translated')->nullable();
        });

        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('unique_identifier')->unique();
            $table->string('status')->default('0');
            $table->timestamps();
        });

        DB::table('global_settings')->insert([
            ['key' => 'role_perm_2', 'value' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'role_perm_4', 'value' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'role_perm_7', 'value' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'role_perm_19', 'value' => json_encode([]), 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('language')->insert([
            'name' => null,
            'phrase' => 'Exam Result',
            'translated' => 'Exam Result',
        ]);

        DB::table('addons')->insert([
            'unique_identifier' => 'transport',
            'status' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeUser(int $roleId, int $schoolId, string $status = 'active'): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'school_id' => $schoolId,
            'account_status' => $status,
            'menu_permission' => null,
        ]);
    }

    protected function makeClass(int $schoolId): int
    {
        return (int) DB::table('classes')->insertGetId([
            'school_id' => $schoolId,
            'name' => 'Class ' . $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeSubject(int $schoolId, ?int $classId = null): int
    {
        return (int) DB::table('subjects')->insertGetId([
            'school_id' => $schoolId,
            'class_id' => $classId,
            'name' => 'Subject ' . $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function enrollStudent(int $userId, int $schoolId, int $classId): void
    {
        DB::table('enrollment')->insert([
            'user_id' => $userId,
            'class_id' => $classId,
            'school_id' => $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeExam(array $overrides = []): int
    {
        $defaults = [
            'school_id' => 1,
            'title' => 'Exam',
            'subject_id' => null,
            'class_id' => null,
            'exam_type' => 'quiz',
            'start_datetime' => now()->subHour(),
            'end_datetime' => now()->addHour(),
            'duration_mins' => 30,
            'total_marks' => 10,
            'pass_mark' => 5,
            'max_attempts' => 1,
            'instructions' => 'Read carefully',
            'is_published' => 1,
            'workflow_state' => 'published',
            'result_release_policy' => 'immediate',
            'auto_submit' => 1,
            'created_by' => null,
            'creator_id' => null,
            'updater_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('online_exams')->insertGetId(array_merge($defaults, $overrides));
    }

    protected function makeQuestion(int $examId, array $overrides = []): int
    {
        $defaults = [
            'online_exam_id' => $examId,
            'question' => 'Question text',
            'type' => 'mcq',
            'option_a' => 'A',
            'option_b' => 'B',
            'option_c' => 'C',
            'option_d' => 'D',
            'correct_ans' => 'A',
            'marks' => 5,
            'sort_order' => 1,
        ];

        return (int) DB::table('online_exam_questions')->insertGetId(array_merge($defaults, $overrides));
    }

    protected function makeSubmission(array $overrides = []): int
    {
        $defaults = [
            'online_exam_id' => 1,
            'student_id' => 1,
            'school_id' => 1,
            'attempt_no' => 1,
            'status' => 'in_progress',
            'started_at' => now()->subMinutes(10),
            'expires_at' => now()->addMinutes(20),
            'last_activity_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('online_exam_submissions')->insertGetId(array_merge($defaults, $overrides));
    }
}
