<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['schools', 'users', 'admissions', 'programme_cohorts'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Cannot create Programme Cohort memberships: required table {$table} is missing.");
            }
        }

        Schema::create('programme_cohort_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('programme_cohort_id');
            $table->unsignedBigInteger('admission_id')->nullable();
            $table->string('admission_reference', 30)->nullable();
            $table->string('status', 20)->default('active');
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->timestamps();

            $table->unsignedBigInteger('active_student_id')
                ->storedAs('CASE WHEN ended_at IS NULL THEN student_id ELSE NULL END');

            $table->unique(['school_id', 'id'], 'pcm_school_id_id_uq');
            $table->unique(['school_id', 'active_student_id'], 'pcm_active_student_uq');
            $table->index(['school_id', 'programme_cohort_id', 'status', 'ended_at'], 'pcm_cohort_status_idx');
            $table->index(['school_id', 'student_id', 'started_at'], 'pcm_student_history_idx');
            $table->index(['school_id', 'admission_id'], 'pcm_admission_idx');

            $table->foreign('school_id', 'pcm_school_fk')
                ->references('id')->on('schools')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('student_id', 'pcm_student_fk')
                ->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'programme_cohort_id'], 'pcm_cohort_tenant_fk')
                ->references(['school_id', 'id'])->on('programme_cohorts')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('admission_id', 'pcm_admission_fk')
                ->references('id')->on('admissions')->onDelete('set null')->onUpdate('restrict');
            $table->foreign('assigned_by', 'pcm_assigner_fk')
                ->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_cohort_memberships');
    }
};
