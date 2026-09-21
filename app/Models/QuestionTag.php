<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionTag extends Model
{
    protected $fillable = ['school_id', 'name', 'normalized_name', 'is_active', 'created_by'];
    protected $casts = ['is_active' => 'boolean'];
    public function questions() { return $this->belongsToMany(QuestionBank::class, 'question_bank_tag'); }
}
