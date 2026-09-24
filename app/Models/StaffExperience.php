<?php

namespace App\Models;

use App\Models\Concerns\StaffRecord;
use Illuminate\Database\Eloquent\Model;

/** One previous (or current) employment of a staff user (0..N per user). */
class StaffExperience extends Model
{
    use StaffRecord;

    protected $fillable = [
        'user_id', 'school_id', 'employer', 'position', 'start_date', 'end_date',
        'currently_working', 'description', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'currently_working' => 'boolean',
    ];
}
