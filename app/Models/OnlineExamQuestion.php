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

    public function exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }
}
