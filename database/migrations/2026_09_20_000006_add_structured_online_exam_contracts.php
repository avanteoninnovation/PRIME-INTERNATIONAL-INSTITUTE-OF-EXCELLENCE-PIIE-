<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_exam_questions', function (Blueprint $table) {
            $table->unsignedTinyInteger('question_schema_version')->nullable()->after('correct_ans');
            $table->longText('question_config')->nullable()->after('question_schema_version');
            $table->longText('marking_config')->nullable()->after('question_config');
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->unsignedTinyInteger('question_schema_version')->nullable()->after('correct_ans');
            $table->longText('question_config')->nullable()->after('question_schema_version');
            $table->longText('marking_config')->nullable()->after('question_config');
        });

        Schema::table('online_exam_answers', function (Blueprint $table) {
            $table->unsignedTinyInteger('answer_schema_version')->nullable()->after('answer_revision');
            $table->longText('answer_payload')->nullable()->after('answer_schema_version');
        });
    }

    public function down(): void
    {
        Schema::table('online_exam_answers', function (Blueprint $table) {
            $table->dropColumn(['answer_schema_version', 'answer_payload']);
        });
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropColumn(['question_schema_version', 'question_config', 'marking_config']);
        });
        Schema::table('online_exam_questions', function (Blueprint $table) {
            $table->dropColumn(['question_schema_version', 'question_config', 'marking_config']);
        });
    }
};
