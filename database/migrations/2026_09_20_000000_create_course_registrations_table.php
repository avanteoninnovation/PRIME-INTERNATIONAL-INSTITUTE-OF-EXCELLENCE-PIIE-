<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course registration per session — a student picks subjects from their
 * programme/class's catalog (Subject already carries credits/code/
 * course_type, added for this HEI track earlier this session but never
 * actually consumed by anything student-facing), then confirms the
 * selection once their fee balance allows it. Nothing in this app
 * previously tracked "which courses is this student taking this session"
 * as its own concept — only Gradebook (marks *after* an exam), which
 * assumes the course list already exists elsewhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('subject_id')->index();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('status', 20)->default('registered'); // registered | confirmed | dropped
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');
    }
};
