<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuestionTopic extends Model
{
    protected $fillable = ['school_id', 'subject_id', 'parent_id', 'name', 'is_active', 'created_by'];
    protected $casts = ['is_active' => 'boolean'];
    public function subject() { return $this->belongsTo(Subject::class, 'subject_id'); }
    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }
}
