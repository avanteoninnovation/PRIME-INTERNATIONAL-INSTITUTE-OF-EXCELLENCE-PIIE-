<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema readiness only — Timetable/Routine has zero programme-aware
 * groundwork today. Makes class_id/section_id/subject_id nullable (all
 * three are hard NOT NULL currently) and adds a nullable programme_id,
 * mirroring the precedent set by
 * 2026_07_27_050001_add_course_fields_to_subjects_table.php.
 *
 * Does NOT change any controller. Building a real programme-based
 * timetable (HEI course scheduling is frequently not a fixed weekly
 * class/section grid at all) is genuine UX/design work, not a schema
 * change, and is intentionally left for later — this migration only
 * removes the structural blocker that would prevent it from ever being
 * built without altering a live table later.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('routines', function (Blueprint $table) {
            if (! Schema::hasColumn('routines', 'programme_id')) {
                $table->unsignedBigInteger('programme_id')->nullable()->after('subject_id');
            }
        });

        DB::statement('ALTER TABLE `routines` MODIFY `class_id` INT NULL');
        DB::statement('ALTER TABLE `routines` MODIFY `section_id` INT NULL');
        DB::statement('ALTER TABLE `routines` MODIFY `subject_id` INT NULL');
    }

    public function down()
    {
        Schema::table('routines', function (Blueprint $table) {
            if (Schema::hasColumn('routines', 'programme_id')) {
                $table->dropColumn('programme_id');
            }
        });
        // class_id/section_id/subject_id intentionally left nullable on
        // rollback — narrowing back to NOT NULL could fail against rows
        // saved without them.
    }
};
