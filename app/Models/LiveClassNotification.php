<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveClassNotification extends Model
{
    use HasFactory;

    public const TYPE_REMINDER_24H = 'reminder_24h';
    public const TYPE_REMINDER_1H = 'reminder_1h';

    protected $fillable = [
        'school_id', 'live_class_id', 'type', 'recipient_count', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function liveClass()
    {
        return $this->belongsTo(LiveClass::class, 'live_class_id');
    }
}
