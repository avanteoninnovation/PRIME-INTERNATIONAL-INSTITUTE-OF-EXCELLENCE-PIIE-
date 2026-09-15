<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineExamNotification extends Model
{
    public const TYPE_REMINDER_24H = 'reminder_24h';
    public const TYPE_REMINDER_1H = 'reminder_1h';
    public const TYPE_REMINDER_15M = 'reminder_15m';

    protected $fillable = [
        'school_id', 'online_exam_id', 'type', 'recipient_count', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }
}
