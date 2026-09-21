<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionCandidate extends Model
{
    protected $fillable = ['position_id', 'school_id', 'student_id', 'manifesto'];

    public function position()
    {
        return $this->belongsTo(ElectionPosition::class, 'position_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function votes()
    {
        return $this->hasMany(ElectionVote::class, 'candidate_id');
    }
}
