<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'code', 'name', 'level', 'duration', 'mode',
        'tuition_fee', 'department_id', 'is_active'
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class, 'programme_id');
    }

    public function admissions()
    {
        return $this->hasMany(Admission::class, 'programme_id');
    }

    public function liveClasses()
    {
        return $this->hasMany(LiveClass::class, 'programme_id');
    }
}
