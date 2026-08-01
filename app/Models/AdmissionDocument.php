<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionDocument extends Model
{
    use HasFactory;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REJECTED = 'rejected';

    /** Where uploaded application files live, relative to public/. */
    public const UPLOAD_DIR = 'assets/uploads/admissions';

    protected $fillable = [
        'school_id', 'admission_id', 'requirement_key', 'label',
        'original_name', 'stored_name', 'mime_type', 'size_bytes',
        'status', 'review_note', 'reviewed_by', 'reviewed_at',
        'uploaded_by_applicant_id', 'uploaded_by_user_id',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'size_bytes'  => 'integer',
    ];

    public function admission()
    {
        return $this->belongsTo(Admission::class, 'admission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getUrlAttribute(): string
    {
        return asset(self::UPLOAD_DIR . '/' . $this->stored_name);
    }

    public function getAbsolutePathAttribute(): string
    {
        return public_path(self::UPLOAD_DIR . '/' . $this->stored_name);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size_bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return max(1, round($bytes / 1024)) . ' KB';
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
