<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveClassMeetGuest extends Model
{
    protected $table = 'live_class_meet_guests';

    protected $fillable = [
        'school_id', 'email', 'label', 'created_by',
    ];

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }
}
