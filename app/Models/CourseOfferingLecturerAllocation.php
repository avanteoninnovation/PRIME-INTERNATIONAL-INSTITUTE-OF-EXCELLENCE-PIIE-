<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;

class CourseOfferingLecturerAllocation extends Model
{
    public const ROLE_PRIMARY_LECTURER = 'primary_lecturer';
    public const ROLE_CO_LECTURER = 'co_lecturer';
    public const ROLE_TEACHING_ASSISTANT = 'teaching_assistant';
    public const ROLE_LAB_INSTRUCTOR = 'lab_instructor';
    public const ROLE_GUEST_LECTURER = 'guest_lecturer';

    public const ROLES = [
        self::ROLE_PRIMARY_LECTURER,
        self::ROLE_CO_LECTURER,
        self::ROLE_TEACHING_ASSISTANT,
        self::ROLE_LAB_INSTRUCTOR,
        self::ROLE_GUEST_LECTURER,
    ];

    public const STATUS_PLANNED = 'planned';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ENDED = 'ended';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PLANNED,
        self::STATUS_ACTIVE,
        self::STATUS_ENDED,
        self::STATUS_CANCELLED,
    ];

    protected $table = 'course_offering_lecturer_allocations';

    protected $guarded = ['*'];

    protected $casts = [
        'school_id' => 'integer',
        'course_offering_id' => 'integer',
        'user_id' => 'integer',
        'starts_on' => 'date:Y-m-d',
        'ends_on' => 'date:Y-m-d',
    ];

    protected static function booted(): void
    {
        static::creating(function (): void {
            throw new DomainException('Lecturer Allocations may only be created through CourseOfferingLecturerAllocationService.');
        });

        static::updating(function (): void {
            throw new DomainException('Lecturer Allocations may only be changed through CourseOfferingLecturerAllocationService.');
        });

        static::deleting(function (): void {
            throw new DomainException('Lecturer Allocations are historical records and cannot be deleted.');
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
