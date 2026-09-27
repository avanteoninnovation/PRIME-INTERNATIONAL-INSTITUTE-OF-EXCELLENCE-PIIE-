<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicPeriod extends Model
{
    public const TYPES = ['semester', 'term'];
    public const STATUSES = ['planned', 'active', 'completed', 'cancelled'];

    public static function matchesCalendarPattern(string $type, ?string $pattern): bool
    {
        return in_array($type, self::TYPES, true) && (! $pattern || $type === $pattern);
    }

    protected $fillable = ['school_id', 'academic_year_id', 'type', 'label', 'sequence', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'sequence' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class, 'academic_period_id')->where('school_id', $this->school_id);
    }
}
