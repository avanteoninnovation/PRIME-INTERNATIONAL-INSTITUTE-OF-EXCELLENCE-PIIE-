<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_offering_lecturer_allocations', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('course_offering_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role', 32);
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('status', 16)->default('planned');
            $table->timestamps();

            $table->index(
                ['school_id', 'course_offering_id', 'status', 'starts_on', 'ends_on'],
                'cofla_offering_status_dates_idx'
            );
            $table->index(
                ['school_id', 'user_id', 'status', 'starts_on', 'ends_on'],
                'cofla_user_status_dates_idx'
            );

            $table->foreign(['school_id', 'course_offering_id'], 'cofla_offering_tenant_fk')
                ->references(['school_id', 'id'])->on('course_offerings')
                ->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('user_id', 'cofla_user_fk')
                ->references('id')->on('users')
                ->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offering_lecturer_allocations');
    }
};
