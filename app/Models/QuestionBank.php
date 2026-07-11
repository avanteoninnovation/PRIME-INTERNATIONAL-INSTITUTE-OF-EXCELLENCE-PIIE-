<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBank extends Model
{
    use HasFactory;

    protected $table = 'question_banks';

    protected $fillable = [
        'school_id', 'subject_id', 'question', 'type',
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_ans', 'marks', 'difficulty', 'created_by'
    ];

    protected $appends = ['normalized_type', 'correct_answer'];

    protected $casts = [
        'marks' => 'integer',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeVisibleToTeacher($query, int $teacherId, int $schoolId)
    {
        return $query->forSchool($schoolId)
            ->where(function ($q) use ($teacherId) {
                $q->where('created_by', $teacherId)
                    ->orWhereNull('created_by');
            });
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
