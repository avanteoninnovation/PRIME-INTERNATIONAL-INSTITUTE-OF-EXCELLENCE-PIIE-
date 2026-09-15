<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveClassMaterial extends Model
{
    use HasFactory;

    public const TYPE_FILE = 'file';
    public const TYPE_LINK = 'link';

    public const CATEGORY_RESOURCE = 'resource';
    public const CATEGORY_RECORDING = 'recording';

    /** Where uploaded class materials live, relative to public/. */
    public const UPLOAD_DIR = 'assets/uploads/live_class_materials';

    /** Recordings get their own directory — kept separate so disk usage from large video files is easy to spot/manage on its own. */
    public const RECORDING_UPLOAD_DIR = 'assets/uploads/live_class_recordings';

    public const ALLOWED_EXTENSIONS = ['pdf', 'ppt', 'pptx', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];
    public const MAX_FILE_MB = 20;

    /**
     * Recordings get their own extension list and a much larger cap than
     * resources — but this is still bounded by the host's PHP
     * upload_max_filesize/post_max_size (typically small on shared cPanel
     * hosting), so a link to an externally-hosted recording (YouTube
     * unlisted, Google Drive) stays the recommended path for anything
     * lecture-length; direct upload realistically suits short clips only.
     */
    public const ALLOWED_RECORDING_EXTENSIONS = ['mp4', 'webm', 'mov', 'mkv', 'mp3', 'm4a'];
    public const MAX_RECORDING_MB = 300;

    protected $fillable = [
        'school_id', 'live_class_id', 'type', 'category', 'title',
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

    public function isRecording(): bool
    {
        return $this->category === self::CATEGORY_RECORDING;
    }

    public function uploadDir(): string
    {
        return $this->isRecording() ? self::RECORDING_UPLOAD_DIR : self::UPLOAD_DIR;
    }

    public function getUrlAttribute(): string
    {
        return $this->isFile()
            ? asset($this->uploadDir() . '/' . $this->stored_name)
            : (string) $this->link_url;
    }

    public function getAbsolutePathAttribute(): ?string
    {
        return $this->isFile() ? public_path($this->uploadDir() . '/' . $this->stored_name) : null;
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
