<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admission extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'app_number', 'intake_session_id', 'programme_id',
        'first_name', 'last_name', 'email', 'phone', 'dob', 'gender',
        'nationality', 'qualifications', 'documents', 'status', 'source',
        'offer_date', 'agent_id', 'reviewed_by', 'notes'
    ];

    protected $casts = [
        'documents' => 'array',
    ];

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function intakeSession()
    {
        return $this->belongsTo(IntakeSession::class, 'intake_session_id');
    }

    public function agent()
    {
        return $this->belongsTo(AdmissionAgent::class, 'agent_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
