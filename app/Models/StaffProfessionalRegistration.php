<?php

namespace App\Models;

use App\Models\Concerns\StaffRecord;
use Illuminate\Database\Eloquent\Model;

/** Membership of / licence from a professional body (0..N per user; never mandatory). */
class StaffProfessionalRegistration extends Model
{
    use StaffRecord;

    protected $fillable = [
        'user_id', 'school_id', 'professional_body', 'membership_number', 'registration_number',
        'issue_date', 'expiry_date', 'evidence_document_id', 'created_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function evidenceDocument()
    {
        return $this->belongsTo(StaffDocument::class, 'evidence_document_id');
    }
}
