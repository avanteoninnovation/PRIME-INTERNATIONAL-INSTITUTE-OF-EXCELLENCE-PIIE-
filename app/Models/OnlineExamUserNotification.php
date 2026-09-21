<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineExamUserNotification extends Model
{
    protected $table = 'online_exam_user_notifications';

    protected $fillable = [
        'school_id', 'user_id', 'actor_id', 'online_exam_id', 'submission_id',
        'type', 'title', 'message', 'action_url', 'read_at', 'event_key',
    ];

    protected $casts = ['read_at' => 'datetime'];

    public function scopeForUser($query, int $schoolId, int $userId)
    {
        return $query->where('school_id', $schoolId)->where('user_id', $userId);
    }
}
