<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'class_id', 'school_id', 'session_id',
        'code', 'credits', 'course_type', 'level', 'pass_mark', 'cats_marks', 'exam_marks', 'programme_id',
    ];

    /** Reuses Programme's same standardized list — see Programme::LEVELS/LEVELS_LEGACY. */
    public const TYPES = ['compulsory', 'elective', 'general', 'dissertation'];

    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class, 'subject_id');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function classes()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }
}
