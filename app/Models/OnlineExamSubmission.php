<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineExamSubmission extends Model
{
    public $timestamps = false;
    protected $table = 'online_exam_submissions';
    protected $fillable = [
        'online_exam_id', 'student_id', 'school_id',
        'answers', 'score', 'started_at', 'submitted_at', 'status'
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
