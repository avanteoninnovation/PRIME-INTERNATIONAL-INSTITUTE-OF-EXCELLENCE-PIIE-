<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->text('instructions')->nullable();
            $table->dateTime('due_date')->nullable();
            $table->integer('max_marks')->default(100);
            $table->enum('submission_type', ['file','text','link','any'])->default('any');
            $table->tinyInteger('is_published')->default(1);
            $table->timestamps();
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assignment_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->text('submission')->nullable();
            $table->string('file_path')->nullable();
            $table->string('link')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->decimal('marks_awarded', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->enum('status', ['submitted','late','graded'])->default('submitted');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};
