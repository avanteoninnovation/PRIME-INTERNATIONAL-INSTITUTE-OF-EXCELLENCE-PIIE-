<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\HostelFee;
use App\Models\StudentFeeManager;

/**
 * Security Phase 2F: audit entries for state changes that are made with
 * query-builder updates (or on models outside AuditableObserver::MODULES) and
 * so were never recorded — promotion / enrollment changes, offline-payment
 * submission and approval/decline, hostel payment decisions.
 *
 * Every entry goes through the existing AuditLog::record(), which supplies
 * the actor, role, school, route, IP and timestamp. Only non-sensitive state
 * is recorded: statuses, amounts, class/section/session and the student id —
 * never uploaded file names or contents.
 */
class StatusChangeAudit
{
    public static function feePayment(StudentFeeManager $before, string $verb): void
    {
        $after = StudentFeeManager::find($before->id) ?? $before;

        AuditLog::record('update', 'Finance', "Offline payment {$verb} for invoice #{$before->id} ({$before->title}).", [
            'event_type'  => 'DATA',
            'school_id'   => $before->school_id,
            'record_type' => StudentFeeManager::class,
            'record_id'   => $before->id,
            'old_values'  => self::feeState($before),
            'new_values'  => self::feeState($after),
        ]);
    }

    public static function hostelPayment(HostelFee $fee, int $oldStatus, string $verb): void
    {
        AuditLog::record('update', 'Hostel', "Hostel offline payment #{$fee->id} {$verb}.", [
            'event_type'  => 'DATA',
            'school_id'   => $fee->school_id,
            'record_type' => HostelFee::class,
            'record_id'   => $fee->id,
            'old_values'  => ['status' => $oldStatus, 'student_id' => $fee->student_id],
            'new_values'  => ['status' => (int) $fee->status, 'student_id' => $fee->student_id],
        ]);
    }

    /** Records an enrollment placement change; no entry when class/section/session did not change. */
    public static function enrollment(Enrollment $after, ?array $old, string $reason): void
    {
        $new = self::placement($after);
        if ($old !== null && $old === $new) {
            return;
        }

        AuditLog::record('update', 'Academic', "{$reason}: enrollment #{$after->id} for student #{$after->user_id}.", [
            'event_type'  => 'DATA',
            'school_id'   => $after->school_id,
            'record_type' => Enrollment::class,
            'record_id'   => $after->id,
            'old_values'  => $old,
            'new_values'  => $new,
        ]);
    }

    public static function placement(Enrollment $enrollment): array
    {
        return [
            'student_id' => (int) $enrollment->user_id,
            'class_id'   => (int) $enrollment->class_id,
            'section_id' => (int) $enrollment->section_id,
            'session_id' => (int) $enrollment->session_id,
        ];
    }

    private static function feeState(StudentFeeManager $fee): array
    {
        return [
            'status'         => $fee->status,
            'paid_amount'    => $fee->paid_amount,
            'payment_method' => $fee->payment_method,
            'student_id'     => $fee->student_id,
        ];
    }
}
