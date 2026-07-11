<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class LiveClass extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_LIVE = 'live';
    public const STATUS_ENDED = 'ended';
    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'live_classes';

    protected $fillable = [
        'school_id',
        'title',
        'description',
        'subject_id',
        'class_id',
        'programme_id',
        'academic_session_id',
        'teacher_id',
        'platform',
        'meeting_url',
        'meeting_id',
        'meeting_password',
        'scheduled_at',
        'ends_at',
        'start_date',
        'start_time',
        'end_time',
        'timezone',
        'status',
        'is_published',
        'attendance_enabled',
        'recording_url',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'ends_at'      => 'datetime',
        'start_date'   => 'date',
        'start_time'   => 'datetime:H:i:s',
        'end_time'     => 'datetime:H:i:s',
        'is_published' => 'boolean',
        'attendance_enabled' => 'boolean',
    ];

    protected $appends = [
        'computed_status',
        'can_join',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function course()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lecturer()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function academicSession()
    {
        return $this->belongsTo(Session::class, 'academic_session_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now());
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_LIVE)
                ->orWhere(function ($inner) {
                    $inner->where('status', self::STATUS_SCHEDULED)
                        ->whereNotNull('scheduled_at')
                        ->where('scheduled_at', '<=', now())
                        ->where(function ($sub) {
                            $sub->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                        });
                });
        });
    }

    public function scopeEnded($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_ENDED)
                ->orWhere(function ($inner) {
                    $inner->whereNotNull('ends_at')
                        ->where('ends_at', '<', now())
                        ->where('status', '!=', self::STATUS_CANCELLED);
                });
        });
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return self::STATUS_CANCELLED;
        }

        if (!$this->is_published) {
            return self::STATUS_DRAFT;
        }

        if (!$this->scheduled_at) {
            return $this->status ?: self::STATUS_DRAFT;
        }

        $now = now();
        $start = $this->scheduled_at;
        $end = $this->ends_at;

        if ($end && $now->greaterThan($end)) {
            return self::STATUS_ENDED;
        }

        if ($now->greaterThanOrEqualTo($start) && (!$end || $now->lessThanOrEqualTo($end))) {
            return self::STATUS_LIVE;
        }

        return self::STATUS_SCHEDULED;
    }

    public function shouldAllowJoin(?Carbon $now = null): bool
    {
        $now = $now ?: now();

        if (!$this->is_published || $this->computed_status === self::STATUS_CANCELLED) {
            return false;
        }

        if (empty($this->safe_meeting_url)) {
            return false;
        }

        if (in_array($this->computed_status, [self::STATUS_LIVE, self::STATUS_SCHEDULED], true)) {
            if (!$this->scheduled_at) {
                return false;
            }

            // Allow early join up to 15 minutes before start.
            return $now->greaterThanOrEqualTo($this->scheduled_at->copy()->subMinutes(15));
        }

        return false;
    }

    public function getCanJoinAttribute(): bool
    {
        return $this->shouldAllowJoin();
    }

    public function getSafeMeetingUrlAttribute(): ?string
    {
        $url = trim((string) $this->meeting_url);
        if ($url === '') {
            return null;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        if (!isset($parts['scheme']) || strtolower($parts['scheme']) !== 'https') {
            return null;
        }

        return $url;
    }

    public function getSafeRecordingUrlAttribute(): ?string
    {
        $url = trim((string) $this->recording_url);
        if ($url === '') {
            return null;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($url);
        if (!isset($parts['scheme']) || strtolower($parts['scheme']) !== 'https') {
            return null;
        }

        return $url;
    }
}
