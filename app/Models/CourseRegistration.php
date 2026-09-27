<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;

class CourseRegistration extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DROPPED = 'dropped';

    protected $fillable = [
        'student_id', 'subject_id', 'session_id', 'school_id', 'status',
        'course_offering_id', 'curriculum_membership_id', 'registered_credits', 'registered_classification',
    ];

    protected $casts = [
        'registered_credits' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $registration): void {
            if ($registration->exists) {
                $previousStatus = $registration->getOriginal('status');
                $nextStatus = $registration->status;
                $allowed = match ($previousStatus) {
                    self::STATUS_REGISTERED => [self::STATUS_REGISTERED, self::STATUS_CONFIRMED, self::STATUS_DROPPED],
                    self::STATUS_CONFIRMED => [self::STATUS_CONFIRMED, self::STATUS_DROPPED],
                    self::STATUS_DROPPED => [self::STATUS_DROPPED],
                    default => [$previousStatus],
                };
                if (! in_array($nextStatus, $allowed, true)) {
                    throw new DomainException('Invalid Course Registration status transition.');
                }
            }
            if ($registration->exists && $registration->getOriginal('status') === self::STATUS_CONFIRMED) {
                foreach (['school_id', 'student_id', 'course_offering_id', 'curriculum_membership_id', 'subject_id', 'registered_credits', 'registered_classification'] as $field) {
                    if ($registration->isDirty($field)) {
                        throw new DomainException('Confirmed registration academic provenance is immutable.');
                    }
                }
            }
        });
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id')
            ->where('school_id', $this->school_id);
    }

    public function curriculumMembership()
    {
        return $this->belongsTo(CurriculumMembership::class, 'curriculum_membership_id')
            ->where('school_id', $this->school_id);
    }

    public function isOfferingBacked(): bool
    {
        return $this->course_offering_id !== null;
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_DROPPED);
    }
}
