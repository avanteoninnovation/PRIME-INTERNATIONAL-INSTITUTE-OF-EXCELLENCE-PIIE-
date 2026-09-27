<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['student_curriculum_assignments', 'programme_cohort_memberships', 'curriculum_stages'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Cannot add cohort provenance to Student Curriculum assignments: required table {$table} is missing.");
            }
        }

        Schema::table('student_curriculum_assignments', function (Blueprint $table): void {
            $table->unsignedBigInteger('programme_cohort_membership_id')->nullable();
            $table->unsignedBigInteger('entry_curriculum_stage_id')->nullable();

            $table->index(['school_id', 'programme_cohort_membership_id'], 'sca_cohort_membership_idx');
            $table->index(['school_id', 'curriculum_id', 'entry_curriculum_stage_id'], 'sca_entry_stage_idx');

            $table->foreign(['school_id', 'programme_cohort_membership_id'], 'sca_cohort_membership_tenant_fk')
                ->references(['school_id', 'id'])->on('programme_cohort_memberships')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'curriculum_id', 'entry_curriculum_stage_id'], 'sca_entry_stage_tenant_fk')
                ->references(['school_id', 'curriculum_id', 'id'])->on('curriculum_stages')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('student_curriculum_assignments', function (Blueprint $table): void {
            $table->dropForeign('sca_entry_stage_tenant_fk');
            $table->dropForeign('sca_cohort_membership_tenant_fk');
            $table->dropIndex('sca_entry_stage_idx');
            $table->dropIndex('sca_cohort_membership_idx');
            $table->dropColumn(['entry_curriculum_stage_id', 'programme_cohort_membership_id']);
        });
    }
};
