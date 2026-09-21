<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    protected $fillable = [
        'user_id', 'school_id', 'type', 'title', 'body', 'url', 'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }
}
