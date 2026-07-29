<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema readiness only — Attendance has zero programme-aware groundwork
 * today (unlike Subject/Assignment/OnlineExam, which already had nullable
 * class_id before this redesign started). This migration makes class_id
 * nullable (section_id/session_id already are) and adds nullable
 * programme_id/intake_session_id, mirroring the exact precedent set by
 * 2026_07_27_050001_add_course_fields_to_subjects_table.php.
 *
 * It intentionally does NOT change any controller — AdminController's and
 * TeacherController's attendance-taking flows still query Enrollment/
 * class_id directly and always will for K-12 rosters. Building an actual
 * programme-based attendance-taking flow (what does "attendance" even mean
 * for a self-paced HEI course with no fixed daily register?) is real UX
 * design work, not a schema change, and is intentionally left for later.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_attendances', 'programme_id')) {
                $table->unsignedBigInteger('programme_id')->nullable()->after('class_id');
            }
            if (! Schema::hasColumn('daily_attendances', 'intake_session_id')) {
                $table->unsignedBigInteger('intake_session_id')->nullable()->after('programme_id');
            }
        });

        DB::statement('ALTER TABLE `daily_attendances` MODIFY `class_id` BIGINT UNSIGNED NULL');
    }

    public function down()
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            foreach (['programme_id', 'intake_session_id'] as $col) {
                if (Schema::hasColumn('daily_attendances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        // class_id intentionally left nullable on rollback — narrowing back
        // to NOT NULL could fail against rows saved without it.
    }
};
