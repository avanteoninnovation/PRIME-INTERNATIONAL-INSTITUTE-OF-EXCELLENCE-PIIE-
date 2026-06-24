<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClubNotice extends Model
{
    protected $fillable = [
        'club_id',
        'admin_id',
        'advisor_id',
        'title',
        'description',
        'notice_date',
        'image',
        'status',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
        public function teacher()
    {
        return $this->belongsTo(User::class, 'advisor_id');
    }

    public function club()
    {
        return $this->belongsTo(Club::class);
    }
    public function creator()
    {
        return $this->admin ?: $this->teacher;
    }
}

