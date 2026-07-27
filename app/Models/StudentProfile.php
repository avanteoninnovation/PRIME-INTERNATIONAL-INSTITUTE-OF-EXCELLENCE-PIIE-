<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'school_id', 'first_name', 'last_name', 'programme_id',
        'intake_session_id', 'year_of_study', 'nationality',
        'national_id_or_passport', 'next_of_kin_address', 'next_of_kin_contact',
        'additional_image', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function intakeSession()
    {
        return $this->belongsTo(IntakeSession::class, 'intake_session_id');
    }

    /** Human-readable label for audit log entries (see AuditableObserver). */
    public function auditLabel(): string
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));

        return 'Student profile' . ($name !== '' ? " ({$name})" : " #{$this->id}");
    }
}
