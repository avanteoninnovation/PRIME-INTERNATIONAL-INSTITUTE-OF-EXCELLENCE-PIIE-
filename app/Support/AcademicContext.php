<?php

namespace App\Support;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\Session;

/** Tenant-scoped canonical academic context with explicit legacy compatibility access. */
class AcademicContext
{
    public function currentYear(int|School $tenant): ?AcademicYear
    {
        $school = $this->school($tenant);

        return $school->currentAcademicYear()->first();
    }

    public function currentPeriod(int|School $tenant): ?AcademicPeriod
    {
        $school = $this->school($tenant);

        return $school->currentAcademicPeriod()->first();
    }

    /** Legacy Session lookup is scoped to the tenant and never uses global settings or a global first-row fallback. */
    public function legacyRunningSession(int|School $tenant): ?Session
    {
        $school = $this->school($tenant);
        $id = $school->running_session;

        return $id ? Session::query()->where('school_id', $school->id)->find($id) : null;
    }

    private function school(int|School $tenant): School
    {
        return $tenant instanceof School ? $tenant : School::query()->findOrFail($tenant);
    }
}
