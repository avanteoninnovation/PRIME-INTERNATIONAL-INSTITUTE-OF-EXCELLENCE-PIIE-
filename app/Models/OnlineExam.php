<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineExam extends Model
{
    use HasFactory;
    protected $table = 'online_exams';
    protected $fillable = [
        'school_id', 'title', 'subject_id', 'class_id', 'exam_type',
        'start_datetime', 'end_datetime', 'duration_mins', 'total_marks',
        'pass_mark', 'instructions', 'is_published', 'auto_submit', 'created_by'
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
    ];

    public function questions()
    {
        return $this->hasMany(OnlineExamQuestion::class, 'online_exam_id');
    }

    public function submissions()
    {
        return $this->hasMany(OnlineExamSubmission::class, 'online_exam_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
