<?php

namespace App\Support;

use App\Models\Enrollment;
use App\Models\Session;

/**
 * Programme-track students (created via AdmissionsController::
 * createStudentFromAdmission() or AdminController::studentCreate()) never
 * got an Enrollment row at all, which is what every teacher-facing roster
 * (gradebook, attendance, online exam class lists) actually queries.
 * Without this row, those views don't just skip the student's specific
 * class — a *null* Enrollment lookup is treated differently downstream
 * than a present one with no class, so the student silently drops out of
 * even school-wide content (see App\Models\OnlineExam::
 * scopeVisibleToStudent()).
 *
 * class_id/section_id/department_id are NOT NULL columns on `enrollment`
 * with no "not applicable" value, so this uses the same 0-as-sentinel
 * convention StudentFeeInvoiceGenerator::generateForStudent() already
 * established for "not class-based" — every downstream `if ($classId)`
 * check treats 0 the same as null (both falsy in PHP), so this correctly
 * surfaces school-wide content without pretending the student belongs to
 * a real class. If staff later want a Programme-track student to also show
 * up in a specific teacher's class roster, that requires assigning them a
 * real class — there is currently no field for that on either Programme
 * admission/creation form.
 */
class EnrollmentDefaults
{
    public static function ensureRow(int $studentId, int $schoolId): void
    {
        if (Enrollment::where('user_id', $studentId)->where('school_id', $schoolId)->exists()) {
            return;
        }

        $sessionId = get_school_settings($schoolId)->value('running_session')
            ?: (Session::where('school_id', $schoolId)->value('id') ?? Session::value('id') ?? 1);

        Enrollment::create([
            'user_id'       => $studentId,
            'class_id'      => 0,
            'section_id'    => 0,
            'school_id'     => $schoolId,
            // 0 — no K12-style department concept applies to a
            // Programme-track student; unlike class_id/section_id this
            // isn't picked up by any downstream "if ($x)" visibility check,
            // it just needs to satisfy the NOT NULL column.
            'department_id' => 0,
            'session_id'    => $sessionId,
        ]);
    }
}
