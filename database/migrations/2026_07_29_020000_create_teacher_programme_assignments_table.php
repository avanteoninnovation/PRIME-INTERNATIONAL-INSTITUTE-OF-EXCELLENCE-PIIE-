<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Programme-based equivalent of `teacher_permissions` (which grants a
 * teacher marks/attendance access to a Class+Section — see
 * App\Models\TeacherPermission). There is no programme_id/course
 * equivalent for HEI teachers today, which is why
 * OnlineExamPermissionService::teacherCanUseSubject() and
 * LiveClassController::getAllowedSubjects() had to fail open for any
 * programme-linked (class_id-null) Subject during the Phase 0 audit — a
 * class_id-based check can structurally never pass for them.
 *
 * Deliberately a SEPARATE table rather than adding nullable programme_id
 * to teacher_permissions: several existing TeacherController call sites do
 * `TeacherPermission::...->select('class_id')...` then
 * `Classes::find($id)->toArray()` with no null-guard, and would crash the
 * moment a programme-only row appeared in that same table. Grain matches
 * TeacherPermission: a whole Programme (not per-course), same marks/
 * attendance boolean flags.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('teacher_programme_assignments')) {
            return;
        }

        Schema::create('teacher_programme_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('programme_id');
            $table->unsignedBigInteger('school_id');
            $table->boolean('marks')->default(0);
            $table->boolean('attendance')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['teacher_id', 'programme_id', 'school_id'], 'tpa_teacher_programme_school_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('teacher_programme_assignments');
    }
};
