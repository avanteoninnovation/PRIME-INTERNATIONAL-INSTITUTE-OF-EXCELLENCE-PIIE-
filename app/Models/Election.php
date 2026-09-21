<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Election extends Model
{
    protected $fillable = [
        'school_id', 'title', 'description', 'start_at', 'end_at', 'results_published', 'created_by',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'results_published' => 'boolean',
    ];

    public function positions()
    {
        return $this->hasMany(ElectionPosition::class);
    }

    public function votes()
    {
        return $this->hasMany(ElectionVote::class);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Computed, not stored — same reasoning as OnlineExam/LiveClass's own
     * computed_status: a manually-toggled "is this open" flag can silently
     * drift out of sync with the dates actually shown to voters.
     */
    public function getComputedStatusAttribute(): string
    {
        $now = now();
        if ($now->lt($this->start_at)) {
            return 'upcoming';
        }
        if ($now->gt($this->end_at)) {
            return 'closed';
        }
        return 'open';
    }

    public function scopeOpen($query, ?\Carbon\Carbon $at = null)
    {
        $at = $at ?: now();
        return $query->where('start_at', '<=', $at)->where('end_at', '>=', $at);
    }
}
