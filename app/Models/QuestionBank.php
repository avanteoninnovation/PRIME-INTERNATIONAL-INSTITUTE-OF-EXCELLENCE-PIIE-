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
}
