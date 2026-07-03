<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveClass extends Model
{
    use HasFactory;
    protected $table = 'live_classes';
    protected $fillable = [
        'school_id', 'title', 'subject_id', 'class_id', 'teacher_id',
        'platform', 'meeting_url', 'scheduled_at', 'ends_at', 'status', 'recording_url'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'ends_at'      => 'datetime',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
