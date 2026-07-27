<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('gradebooks')) {
            Schema::create('gradebooks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('class_id')->nullable()->index();
                $table->unsignedBigInteger('section_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('exam_category_id')->nullable()->index();
                // JSON map of subject_id => mark; widened to text (vs. the
                // original vendor's varchar(255)) to avoid truncation for
                // exams with many subjects.
                $table->text('marks')->nullable();
                $table->string('comment')->nullable();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->integer('timestamp')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admit_cards')) {
            Schema::create('admit_cards', function (Blueprint $table) {
                $table->id();
                $table->string('template');
                $table->string('heading')->nullable();
                $table->string('title')->nullable();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('exam_center')->nullable();
                $table->string('footer_text')->nullable();
                $table->string('left_logo')->nullable();
                $table->string('right_logo')->nullable();
                $table->string('sign')->nullable();
                $table->string('background_image')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admit_cards');
        Schema::dropIfExists('gradebooks');
    }
};
