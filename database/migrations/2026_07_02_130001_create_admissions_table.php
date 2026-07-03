<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('app_number', 30)->unique();
            $table->unsignedBigInteger('intake_session_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->text('qualifications')->nullable();
            $table->json('documents')->nullable();
            $table->enum('status', ['submitted','under_review','accepted','rejected','enrolled','withdrawn'])->default('submitted');
            $table->date('offer_date')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admissions');
    }
};
