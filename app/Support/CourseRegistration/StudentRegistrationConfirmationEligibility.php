<?php

namespace App\Support\CourseRegistration;

use App\Models\StudentFeeManager;
use App\Models\User;

class StudentRegistrationConfirmationEligibility
{
    public function allows(User $student): bool
    {
        $invoices = StudentFeeManager::where('student_id', $student->id)
            ->where('school_id', $student->school_id)
            ->get();
        $balance = (float) $invoices->sum(fn ($invoice) => max(0, (float) $invoice->total_amount - (float) $invoice->paid_amount));

        return $balance <= 0;
    }
}
