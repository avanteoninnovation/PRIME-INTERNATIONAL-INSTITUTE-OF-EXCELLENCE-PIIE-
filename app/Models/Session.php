<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'session_title', 'status', 'school_id'
    ];

    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class, 'academic_session_id');
    }
}
