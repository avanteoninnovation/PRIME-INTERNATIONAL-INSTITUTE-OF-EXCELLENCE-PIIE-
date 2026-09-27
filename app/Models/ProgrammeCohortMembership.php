<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;

class ProgrammeCohortMembership extends Model
{
    public const STATUSES = ['active', 'deferred', 'transferred', 'withdrawn', 'completed'];
    public const CURRENT_STATUSES = ['active', 'deferred'];
    public const TERMINAL_STATUSES = ['transferred', 'withdrawn', 'completed'];

    protected $table = 'programme_cohort_memberships';
    protected $guarded = ['id', 'active_student_id'];

    protected $casts = [
        'school_id' => 'integer',
        'student_id' => 'integer',
        'programme_cohort_id' => 'integer',
        'admission_id' => 'integer',
        'assigned_by' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'active_student_id' => 'integer',
    ];

    private bool $domainWriteAllowed = false;

    protected static function booted(): void
    {
        static::creating(function (self $membership): void {
            if (! $membership->domainWriteAllowed) {
                throw new DomainException('Programme Cohort memberships may only be created through ProgrammeCohortService.');
            }
        });

        static::updating(function (self $membership): void {
            foreach (['school_id', 'student_id', 'programme_cohort_id', 'started_at', 'admission_reference', 'assigned_by'] as $immutable) {
                if ($membership->isDirty($immutable)) {
                    throw new DomainException('Membership history identity and started time are immutable.');
                }
            }
            if (! $membership->domainWriteAllowed) {
                throw new DomainException('Programme Cohort membership changes must go through ProgrammeCohortService.');
            }
        });

        static::deleting(function (): never {
            throw new DomainException('Programme Cohort membership history cannot be deleted.');
        });
    }

    public static function createThroughDomainService(array $attributes): self
    {
        $membership = new self();
        $membership->domainWriteAllowed = true;
        try {
            $membership->forceFill($attributes)->save();
        } finally {
            $membership->domainWriteAllowed = false;
        }

        return $membership;
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

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function cohort()
    {
        return $this->belongsTo(ProgrammeCohort::class, 'programme_cohort_id');
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class, 'admission_id');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function studentCurriculumAssignments()
    {
        return $this->hasMany(StudentCurriculumAssignment::class, 'programme_cohort_membership_id');
    }
}
