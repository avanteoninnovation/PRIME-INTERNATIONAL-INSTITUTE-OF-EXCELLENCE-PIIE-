<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseRegistration extends Model
{
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DROPPED = 'dropped';

    protected $fillable = [
        'student_id', 'subject_id', 'session_id', 'school_id', 'status',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
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
