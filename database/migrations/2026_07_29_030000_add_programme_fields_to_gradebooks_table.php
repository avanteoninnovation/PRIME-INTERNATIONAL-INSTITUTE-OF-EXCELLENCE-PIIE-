<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema readiness for programme-based (HEI) gradebook entries, matching
 * the Subject/LiveClass/Assignment/OnlineExam precedent of an
 * independently-nullable programme_id alongside the existing
 * class_id/section_id/session_id columns (all already nullable on this
 * table). This migration only adds the columns — it does not change any
 * controller/query to populate or read them; CommonController::markUpdate()
 * and the marks-filter methods in AdminController/TeacherController still
 * require class_id/section_id/session_id in every query today, so a
 * programme-only student cannot get a Gradebook row yet. Wiring that up is
 * separate follow-up work, not part of this schema change.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('gradebooks', function (Blueprint $table) {
            if (! Schema::hasColumn('gradebooks', 'programme_id')) {
                $table->unsignedBigInteger('programme_id')->nullable()->after('exam_category_id');
            }
            if (! Schema::hasColumn('gradebooks', 'intake_session_id')) {
                $table->unsignedBigInteger('intake_session_id')->nullable()->after('programme_id');
            }
        });
    }

    public function down()
    {
        Schema::table('gradebooks', function (Blueprint $table) {
            foreach (['programme_id', 'intake_session_id'] as $col) {
                if (Schema::hasColumn('gradebooks', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
