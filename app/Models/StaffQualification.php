<?php

namespace App\Models;

use App\Models\Concerns\StaffRecord;
use Illuminate\Database\Eloquent\Model;

/** One academic qualification of a staff user (0..N per user). */
class StaffQualification extends Model
{
    use StaffRecord;

    public const PENDING = 'pending';
    public const VERIFIED = 'verified';
    public const REJECTED = 'rejected';
    public const VERIFICATION_STATUSES = [self::PENDING, self::VERIFIED, self::REJECTED];

    protected $fillable = [
        'user_id', 'school_id', 'qualification_level', 'qualification_name', 'specialisation', 'institution',
        'country', 'start_year', 'completion_year', 'grade_or_class', 'certificate_number',
        'evidence_document_id', 'created_by',
    ];

    protected $casts = [
        'start_year' => 'integer',
        'completion_year' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function evidenceDocument()
    {
        return $this->belongsTo(StaffDocument::class, 'evidence_document_id');
    }
}
