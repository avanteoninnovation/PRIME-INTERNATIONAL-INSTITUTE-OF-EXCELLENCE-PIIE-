<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    public const STATUSES = ['planned', 'active', 'completed', 'cancelled'];

    protected $fillable = ['school_id', 'label', 'start_date', 'end_date', 'status'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function periods()
    {
        return $this->hasMany(AcademicPeriod::class)->orderBy('sequence');
    }

    public function legacySessions()
    {
        return $this->hasMany(Session::class);
    }

    public function curricula()
    {
        return $this->hasMany(Curriculum::class, 'effective_academic_year_id');
    }

    public function entryProgrammeCohorts()
    {
        return $this->hasMany(ProgrammeCohort::class, 'entry_academic_year_id');
    }

    public function studentEntryAssignments()
    {
        return $this->hasMany(StudentCurriculumAssignment::class, 'entry_academic_year_id')->where('school_id', $this->school_id);
    }

    public function studentEffectiveAssignments()
    {
        return $this->hasMany(StudentCurriculumAssignment::class, 'effective_from_academic_year_id')->where('school_id', $this->school_id);
    }

    public function courseOfferings()
    {
        return $this->hasMany(CourseOffering::class, 'academic_year_id')->where('school_id', $this->school_id);
    }
}
