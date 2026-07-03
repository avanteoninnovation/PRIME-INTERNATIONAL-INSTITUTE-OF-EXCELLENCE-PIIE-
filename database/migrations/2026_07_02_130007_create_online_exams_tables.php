<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('online_exams', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->enum('exam_type', ['cat','midterm','final','quiz','assignment'])->default('cat');
            $table->dateTime('start_datetime')->nullable();
            $table->dateTime('end_datetime')->nullable();
            $table->integer('duration_mins')->default(60);
            $table->integer('total_marks')->default(100);
            $table->integer('pass_mark')->default(50);
            $table->text('instructions')->nullable();
            $table->tinyInteger('is_published')->default(0);
            $table->tinyInteger('auto_submit')->default(1);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('online_exam_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('online_exam_id')->index();
            $table->unsignedBigInteger('question_bank_id')->nullable();
            $table->text('question')->nullable();
            $table->enum('type', ['mcq','true_false','short','essay'])->default('mcq');
            $table->text('option_a')->nullable();
            $table->text('option_b')->nullable();
            $table->text('option_c')->nullable();
            $table->text('option_d')->nullable();
            $table->string('correct_ans', 5)->nullable();
            $table->tinyInteger('marks')->default(1);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('online_exam_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('online_exam_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->json('answers')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->enum('status', ['in_progress','submitted','graded'])->default('in_progress');
        });
    }

    public function down()
    {
        Schema::dropIfExists('online_exam_submissions');
        Schema::dropIfExists('online_exam_questions');
        Schema::dropIfExists('online_exams');
    }
};
