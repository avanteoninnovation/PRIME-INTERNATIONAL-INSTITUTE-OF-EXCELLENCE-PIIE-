<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionVote extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'election_id', 'position_id', 'candidate_id', 'voter_id', 'school_id', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(ElectionCandidate::class, 'candidate_id');
    }
}
