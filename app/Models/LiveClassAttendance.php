<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveClassAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'live_class_id', 'user_id', 'role_id',
        'joined_at', 'left_at', 'duration_seconds',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'left_at'   => 'datetime',
    ];

    public function liveClass()
    {
        return $this->belongsTo(LiveClass::class, 'live_class_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Whether we ever learned when this attendee left (Jitsi-embedded only). */
    public function hasKnownDuration(): bool
    {
        return $this->left_at !== null && $this->duration_seconds !== null;
    }
}
