<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->unsignedBigInteger('programme_id')->nullable()->after('subject_id');
            $table->unsignedBigInteger('session_id')->nullable()->after('programme_id');
            $table->string('topic', 150)->nullable()->after('difficulty');
            $table->string('subtopic', 150)->nullable()->after('topic');
            $table->string('bloom_level', 40)->nullable()->after('subtopic');
            $table->string('status', 20)->default('active')->after('bloom_level');
            $table->index(['school_id', 'programme_id']);
            $table->index(['school_id', 'session_id']);
            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropIndex(['question_banks_school_id_programme_id_index']);
            $table->dropIndex(['question_banks_school_id_session_id_index']);
            $table->dropIndex(['question_banks_school_id_status_index']);
            $table->dropIndex(['question_banks_school_id_topic_index']);
            $table->dropColumn(['programme_id', 'session_id', 'topic', 'subtopic', 'bloom_level', 'status']);
        });
    }
};
