<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by every staff professional record (profile, qualification,
 * registration, experience, document): each row belongs to one staff user and
 * carries that user's school_id, and is only ever looked up within a school.
 */
trait StaffRecord
{
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Tenant boundary: rows of $schoolId only. */
    public function scopeInSchool(Builder $query, int $schoolId): Builder
    {
        return $query->where($this->getTable() . '.school_id', $schoolId);
    }
}
