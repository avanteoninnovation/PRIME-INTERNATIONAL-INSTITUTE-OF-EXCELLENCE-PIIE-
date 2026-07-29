<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherProgrammeAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = ['teacher_id', 'programme_id', 'school_id', 'marks', 'attendance', 'updated_at'];

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}
