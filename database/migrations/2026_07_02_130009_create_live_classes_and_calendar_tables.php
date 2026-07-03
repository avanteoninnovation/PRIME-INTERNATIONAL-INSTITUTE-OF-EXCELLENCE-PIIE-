<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('live_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->enum('platform', ['jitsi','zoom','google_meet','teams','other'])->default('jitsi');
            $table->string('meeting_url', 500)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->enum('status', ['scheduled','live','ended','cancelled'])->default('scheduled');
            $table->string('recording_url', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('academic_calendar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->enum('event_type', ['semester_start','semester_end','exam_period','holiday','registration','graduation','other'])->default('other');
            $table->date('event_date');
            $table->date('end_date')->nullable();
            $table->string('color', 10)->default('#1a3a6b');
            $table->text('description')->nullable();
            $table->tinyInteger('is_public')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('academic_calendar');
        Schema::dropIfExists('live_classes');
    }
};
