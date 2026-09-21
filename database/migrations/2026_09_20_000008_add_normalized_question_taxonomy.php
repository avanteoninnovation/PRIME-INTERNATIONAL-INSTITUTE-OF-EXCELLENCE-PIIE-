<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'subject_id', 'parent_id', 'name'], 'question_topics_scope_name_unique');
        });

        Schema::create('question_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 100);
            $table->string('normalized_name', 100);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'normalized_name'], 'question_tags_school_normalized_unique');
        });

        Schema::create('question_bank_tag', function (Blueprint $table) {
            $table->unsignedBigInteger('question_bank_id');
            $table->unsignedBigInteger('question_tag_id');
            $table->unique(['question_bank_id', 'question_tag_id'], 'question_bank_tag_unique');
            $table->index('question_tag_id');
        });

        Schema::table('question_banks', function (Blueprint $table) {
            $table->unsignedBigInteger('topic_id')->nullable()->after('session_id');
            $table->unsignedBigInteger('subtopic_id')->nullable()->after('topic_id');
            $table->index(['school_id', 'topic_id']);
            $table->index(['school_id', 'subtopic_id']);
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropIndex(['question_banks_school_id_topic_id_index']);
            $table->dropIndex(['question_banks_school_id_subtopic_id_index']);
            $table->dropColumn(['topic_id', 'subtopic_id']);
        });
        Schema::dropIfExists('question_bank_tag');
        Schema::dropIfExists('question_tags');
        Schema::dropIfExists('question_topics');
    }
};
