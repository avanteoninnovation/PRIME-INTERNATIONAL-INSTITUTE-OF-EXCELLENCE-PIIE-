<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Structured education history, replacing the free-text
 * `admissions.qualifications` blob for portal applications.
 *
 * Admissions officers need to filter and compare on award, grade and year —
 * none of which a paragraph of prose supports. The old text column is kept
 * (staff-entered and pre-portal applications still use it) and is shown
 * alongside these rows on the review screen rather than being parsed.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('admission_qualifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('admission_id')->index();
            $table->string('institution', 200);
            $table->string('award', 150)->nullable();
            $table->string('subject', 150)->nullable();
            $table->string('grade', 60)->nullable();
            $table->unsignedSmallInteger('start_year')->nullable();
            $table->unsignedSmallInteger('end_year')->nullable();
            $table->string('country', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_qualifications');
    }
};
