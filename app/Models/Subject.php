<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'class_id', 'school_id', 'session_id'
    ];

    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class, 'subject_id');
    }
}
