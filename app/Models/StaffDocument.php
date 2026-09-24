<?php

namespace App\Models;

use App\Models\Concerns\StaffRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Metadata of one PRIVATE staff document (0..N per user). The file lives
 * outside the web root (App\Support\Staff\StaffDocumentStorage) and is only
 * ever served by database id through an authorized, school-scoped route.
 * storage_key is internal and hidden from every array/JSON serialization.
 */
class StaffDocument extends Model
{
    use StaffRecord;

    public const CATEGORIES = [
        'national_id' => 'National ID',
        'cv' => 'CV',
        'academic_certificate' => 'Academic certificate',
        'academic_transcript' => 'Academic transcript',
        'professional_certificate' => 'Professional certificate',
        'appointment_letter' => 'Appointment letter',
        'employment_contract' => 'Employment contract',
        'other' => 'Other',
    ];

    public const PENDING = 'pending';
    public const VERIFIED = 'verified';
    public const REJECTED = 'rejected';
    public const VERIFICATION_STATUSES = [self::PENDING, self::VERIFIED, self::REJECTED];

    protected $fillable = [
        'user_id', 'school_id', 'category', 'storage_key', 'original_name', 'mime_type', 'size_bytes', 'uploaded_by',
    ];

    protected $hidden = ['storage_key'];

    protected $casts = [
        'size_bytes' => 'integer',
        'verified_at' => 'datetime',
    ];
}
