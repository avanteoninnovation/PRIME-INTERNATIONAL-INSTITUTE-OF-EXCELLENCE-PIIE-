<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineExamAnswer extends Model
{
    protected $table = 'online_exam_answers';

    protected $fillable = [
        'submission_id',
        'question_id',
        'answer_revision',
        'answer_schema_version',
        'answer_payload',
        'selected_option',
        'answer_text',
        'awarded_marks',
        'is_correct',
        'marked_by',
        'marked_at',
        'teacher_comment',
    ];

    protected $casts = [
        'answer_revision' => 'integer',
        'answer_schema_version' => 'integer',
        'awarded_marks' => 'decimal:2',
        'is_correct' => 'boolean',
        'marked_at' => 'datetime',
    ];

    public function submission()
    {
        return $this->belongsTo(OnlineExamSubmission::class, 'submission_id');
    }

    public function question()
    {
        return $this->belongsTo(OnlineExamQuestion::class, 'question_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by');
    }
}
