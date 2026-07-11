<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineExamQuestion extends Model
{
    public $timestamps = false;

    protected $table = 'online_exam_questions';

    protected $fillable = [
        'online_exam_id', 'question_bank_id', 'question', 'type',
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_ans', 'marks', 'sort_order'
    ];

    protected $appends = ['normalized_type', 'correct_answer'];

    protected $casts = [
        'marks' => 'integer',
        'sort_order' => 'integer',
    ];

    public function exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    public function questionBank()
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function answers()
    {
        return $this->hasMany(OnlineExamAnswer::class, 'question_id');
    }

    public function scopeForExam($query, int $examId)
    {
        return $query->where('online_exam_id', $examId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getNormalizedTypeAttribute(): string
    {
        $map = [
            'mcq' => 'multiple_choice',
            'true_false' => 'true_false',
            'short' => 'short_answer',
            'essay' => 'essay',
            'fill_blank' => 'fill_blank',
        ];

        return $map[$this->type] ?? 'multiple_choice';
    }

    public function setTypeAttribute($value): void
    {
        $map = [
            'multiple_choice' => 'mcq',
            'mcq' => 'mcq',
            'true_false' => 'true_false',
            'short_answer' => 'short',
            'short' => 'short',
            'essay' => 'essay',
            'fill_blank' => 'fill_blank',
        ];

        $this->attributes['type'] = $map[$value] ?? $value;
    }

    public function getCorrectAnswerAttribute(): ?string
    {
        return $this->correct_ans;
    }

    public function setCorrectAnswerAttribute($value): void
    {
        $this->attributes['correct_ans'] = $value;
    }
}
