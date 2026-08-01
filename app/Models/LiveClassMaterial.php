<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveClassMaterial extends Model
{
    use HasFactory;

    public const TYPE_FILE = 'file';
    public const TYPE_LINK = 'link';

    /** Where uploaded class materials live, relative to public/. */
    public const UPLOAD_DIR = 'assets/uploads/live_class_materials';

    public const ALLOWED_EXTENSIONS = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];
    public const MAX_FILE_MB = 20;

    protected $fillable = [
        'school_id', 'live_class_id', 'type', 'title',
        'original_name', 'stored_name', 'mime_type', 'size_bytes',
        'link_url', 'uploaded_by',
    ];

    public function liveClass()
    {
        return $this->belongsTo(LiveClass::class, 'live_class_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isFile(): bool
    {
        return $this->type === self::TYPE_FILE;
    }

    public function getUrlAttribute(): string
    {
        return $this->isFile()
            ? asset(self::UPLOAD_DIR . '/' . $this->stored_name)
            : (string) $this->link_url;
    }

    public function getAbsolutePathAttribute(): ?string
    {
        return $this->isFile() ? public_path(self::UPLOAD_DIR . '/' . $this->stored_name) : null;
    }

    public function getHumanSizeAttribute(): ?string
    {
        if (!$this->isFile() || $this->size_bytes === null) {
            return null;
        }

        $bytes = (int) $this->size_bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return max(1, round($bytes / 1024)) . ' KB';
    }
}
