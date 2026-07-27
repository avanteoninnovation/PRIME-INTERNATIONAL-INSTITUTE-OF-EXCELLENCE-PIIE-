<?php

namespace App\Providers;

use App\Support\Audit\AuditableObserver;
use Illuminate\Support\ServiceProvider;

/**
 * Wires up automatic CRUD audit tracking via Model observers, so create/
 * update/delete activity is captured without touching controller code.
 *
 * The observed model list (and each model's module label) lives on
 * AuditableObserver::MODULES — deliberately limited to models that have NO
 * existing manual AuditLog::record() calls in their controllers (Programme,
 * Admission, IntakeSession, AdmissionAgent, ProcurementRequest, Asset,
 * Payroll-related actions already log manually — observing them too would
 * create duplicate entries for the same action). High-volume per-row
 * models (DailyAttendances, per-student mark rows, OnlineExam* — already
 * covered separately) are intentionally excluded to avoid flooding the log
 * on bulk operations like "take attendance for a whole class".
 */
class AuditServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        foreach (array_keys(AuditableObserver::MODULES) as $model) {
            $model::observe(AuditableObserver::class);
        }
    }
}
