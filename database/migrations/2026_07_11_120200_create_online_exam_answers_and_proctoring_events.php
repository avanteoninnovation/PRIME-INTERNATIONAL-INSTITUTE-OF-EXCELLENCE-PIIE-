<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('online_exam_answers')) {
            Schema::create('online_exam_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('submission_id')->index();
                $table->unsignedBigInteger('question_id')->index();
                $table->string('selected_option', 10)->nullable();
                $table->longText('answer_text')->nullable();
                $table->decimal('awarded_marks', 8, 2)->nullable();
                $table->boolean('is_correct')->nullable();
                $table->unsignedBigInteger('marked_by')->nullable()->index();
                $table->dateTime('marked_at')->nullable();
                $table->text('teacher_comment')->nullable();
                $table->timestamps();

                $table->unique(['submission_id', 'question_id'], 'oea_submission_question_unique');
            });
        }

        if (!Schema::hasTable('online_exam_proctoring_events')) {
            Schema::create('online_exam_proctoring_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('submission_id')->index();
                $table->string('event_type', 50)->index();
                $table->dateTime('event_time');
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->string('review_status', 30)->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('online_exam_proctoring_events')) {
            Schema::drop('online_exam_proctoring_events');
        }

        if (Schema::hasTable('online_exam_answers')) {
            Schema::drop('online_exam_answers');
        }
    }
};
