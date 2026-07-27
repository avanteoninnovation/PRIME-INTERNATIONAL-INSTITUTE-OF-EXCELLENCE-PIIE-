<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('appraisals')) {
            Schema::create('appraisals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->text('teacher_id')->nullable();
                $table->string('ans_type')->nullable();
                $table->string('title')->nullable();
                $table->text('question')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('appraisal_submits')) {
            Schema::create('appraisal_submits', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                // Column name intentionally matches the pre-existing typo used
                // throughout the model/controllers/views: "apprasial_id".
                $table->unsignedBigInteger('apprasial_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->text('teacher_id')->nullable();
                $table->text('answers')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('appraisal_submits');
        Schema::dropIfExists('appraisals');
    }
};
