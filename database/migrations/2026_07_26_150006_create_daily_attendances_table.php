<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('daily_attendances')) {
            Schema::create('daily_attendances', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('class_id')->index();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->unsignedBigInteger('student_id')->index();
                $table->integer('status');
                $table->unsignedBigInteger('session_id')->nullable();
                $table->unsignedBigInteger('school_id')->index();
                $table->integer('timestamp')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('daily_attendances');
    }
};
