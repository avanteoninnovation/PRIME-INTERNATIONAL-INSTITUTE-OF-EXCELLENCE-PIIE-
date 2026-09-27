<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;

class ProgrammeCohort extends Model
{
    public const STATUSES = ['draft', 'active', 'completed', 'cancelled'];

    protected $table = 'programme_cohorts';

    protected $fillable = [
        'programme_id', 'intake_session_id', 'entry_academic_year_id', 'curriculum_id',
        'name', 'code', 'expected_completion_date',
    ];

    protected $casts = [
        'school_id' => 'integer',
        'programme_id' => 'integer',
        'intake_session_id' => 'integer',
        'entry_academic_year_id' => 'integer',
        'curriculum_id' => 'integer',
        'created_by' => 'integer',
        'expected_completion_date' => 'date',
    ];

    private bool $domainWriteAllowed = false;

    protected static function booted(): void
    {
        static::creating(function (self $cohort): void {
            if (! $cohort->domainWriteAllowed) {
                throw new DomainException('Programme Cohorts may only be created through ProgrammeCohortService.');
            }
        });

        static::updating(function (self $cohort): void {
            if (! $cohort->domainWriteAllowed) {
                throw new DomainException('Programme Cohort changes must go through ProgrammeCohortService.');
            }
        });

        static::deleting(function (): never {
            throw new DomainException('Programme Cohorts cannot be deleted. Cancel unused drafts instead.');
        });
    }

    public static function createThroughDomainService(array $attributes): self
    {
        $cohort = new self();
        $cohort->domainWriteAllowed = true;
        try {
            $cohort->forceFill($attributes)->save();
        } finally {
            $cohort->domainWriteAllowed = false;
        }

        return $cohort;
    }

    public function updateThroughDomainService(array $attributes): void
    {
        $this->domainWriteAllowed = true;
        try {
            $this->forceFill($attributes)->save();
        } finally {
            $this->domainWriteAllowed = false;
        }
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function intakeSession()
    {
        return $this->belongsTo(IntakeSession::class, 'intake_session_id');
    }

    public function entryAcademicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'entry_academic_year_id');
    }

    public function studyPlan()
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberships()
    {
        return $this->hasMany(ProgrammeCohortMembership::class, 'programme_cohort_id');
    }

    public function currentMemberships()
    {
        return $this->memberships()->whereNull('ended_at');
    }

    public function pendingPlacementMemberships()
    {
        return $this->currentMemberships()->whereDoesntHave('studentCurriculumAssignments');
    }

    public function studentAssignments()
    {
        return $this->hasManyThrough(
            StudentCurriculumAssignment::class,
            ProgrammeCohortMembership::class,
            'programme_cohort_id',
            'programme_cohort_membership_id',
            'id',
            'id'
        )->whereColumn('student_curriculum_assignments.school_id', 'programme_cohort_memberships.school_id');
    }
}
