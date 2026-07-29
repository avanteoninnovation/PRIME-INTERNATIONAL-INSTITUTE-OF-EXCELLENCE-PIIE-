<?php

namespace App\Support;

use App\Models\FeeStructure;
use App\Models\StudentFeeManager;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Auto-generates a student's applicable fee invoices from their Programme's
 * mandatory FeeStructure rows, on creation only (admission conversion or
 * manual admin creation) — never on a later Programme change, so changing
 * a student's programme afterwards does not silently re-bill them; that
 * remains a deliberate, authorized Finance action.
 *
 * Idempotent: keyed on (student_id, fee_structure_id, session_id) via
 * student_fee_managers.fee_structure_id, so calling this more than once for
 * the same student/session (e.g. a retried conversion request) never
 * creates duplicate invoices.
 */
class StudentFeeInvoiceGenerator
{
    /**
     * @return StudentFeeManager[] newly created invoices (empty if none
     *                             applicable or all already exist)
     */
    public static function generateForStudent(User $student, ?int $programmeId, int $schoolId): array
    {
        if (! $programmeId) {
            return [];
        }

        $sessionId = DB::table('schools')->where('id', $schoolId)->value('running_session');

        $feeStructures = FeeStructure::where('school_id', $schoolId)
            ->where('is_mandatory', 1)
            ->where(function ($q) use ($programmeId) {
                $q->whereNull('programme_id')->orWhere('programme_id', $programmeId);
            })
            ->where(function ($q) use ($sessionId) {
                $q->whereNull('session_id')->orWhere('session_id', $sessionId);
            })
            ->get();

        $created = [];

        foreach ($feeStructures as $fee) {
            $alreadyInvoiced = StudentFeeManager::where('student_id', $student->id)
                ->where('fee_structure_id', $fee->id)
                ->where('session_id', $sessionId)
                ->exists();

            if ($alreadyInvoiced) {
                continue;
            }

            $created[] = StudentFeeManager::create([
                'title'            => $fee->name,
                'total_amount'     => (int) round((float) $fee->amount),
                'amount'           => $fee->amount,
                'discounted_price' => 0,
                // No class/section concept for a programme-based (HEI)
                // student — 0 is a sentinel meaning "not class-based";
                // class-filtered fee views will not surface this invoice,
                // but student-scoped views (by student_id) and the
                // programme_id-filtered admin view will.
                'class_id'         => 0,
                'programme_id'     => $programmeId,
                'student_id'       => $student->id,
                'fee_structure_id' => $fee->id,
                'parent_id'        => $student->parent_id,
                'payment_method'   => 'unpaid',
                'paid_amount'      => 0,
                'status'           => 'unpaid',
                'school_id'        => $schoolId,
                'session_id'       => $sessionId,
                'timestamp'        => strtotime(date('d-M-Y')),
            ]);
        }

        return $created;
    }
}
