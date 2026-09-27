<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;

class CourseOffering extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $guarded = ['*'];

    protected $casts = [
        'school_id' => 'integer',
        'subject_id' => 'integer',
        'academic_year_id' => 'integer',
        'academic_period_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (): void {
            throw new DomainException('Course Offerings may only be created through CourseOfferingService.');
        });

        static::updating(function (self $offering): void {
            throw new DomainException('Course Offerings may only be changed through CourseOfferingService.');
        });

        static::deleting(function (self $offering): void {
            throw new DomainException('Course Offerings cannot be deleted; cancel the Offering to preserve history.');
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id')->whereKey($this->school_id);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id')->where('school_id', $this->school_id);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id')->where('school_id', $this->school_id);
    }

    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id')->where('school_id', $this->school_id);
    }

    public function applicability()
    {
        return $this->hasMany(CourseOfferingCurriculumMembership::class, 'course_offering_id')
            ->where('school_id', $this->school_id);
    }

    public function lecturerAllocations()
    {
        return $this->hasMany(CourseOfferingLecturerAllocation::class, 'course_offering_id')
            ->where('school_id', $this->school_id);
    }

    public function curriculumMemberships()
    {
        return $this->belongsToMany(
            CurriculumMembership::class,
            'course_offering_curriculum_memberships',
            'course_offering_id',
            'curriculum_membership_id'
        )->withPivot(['school_id', 'curriculum_id', 'subject_id', 'created_at', 'updated_at'])
            ->wherePivot('school_id', $this->school_id);
    }
}
