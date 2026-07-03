<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicCalendar extends Model
{
    use HasFactory;
    protected $table = 'academic_calendar';
    protected $fillable = [
        'school_id', 'title', 'event_type', 'event_date',
        'end_date', 'color', 'description', 'is_public'
    ];

    protected $casts = [
        'event_date' => 'date',
        'end_date'   => 'date',
    ];
}
