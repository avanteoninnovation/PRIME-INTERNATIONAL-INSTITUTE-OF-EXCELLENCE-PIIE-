<?php

namespace App\Models;

use DomainException;
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
        'course_offering_id',
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
        'course_offering_id' => 'integer',
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

    public function toArray(): array
    {
        $data = parent::toArray();
        if ($this->course_offering_id !== null) {
            unset($data['meeting_url'], $data['meeting_id'], $data['meeting_password'], $data['recording_url']);
        }
        return $data;
    }

    protected static function booted(): void
    {
        static::saving(function (self $liveClass): void {
            $offeringId = $liveClass->course_offering_id;
            $previousOfferingId = $liveClass->getOriginal('course_offering_id');

            if ($offeringId === null || $offeringId === '') {
                if ($previousOfferingId !== null && $previousOfferingId !== '') {
                    throw new DomainException('An Offering-backed Live Class cannot be detached from its Course Offering.');
                }

                return;
            }

            if ($previousOfferingId !== null && (int) $previousOfferingId !== (int) $offeringId) {
                throw new DomainException('An Offering-backed Live Class cannot be reassigned to another Course Offering.');
            }

            $offering = CourseOffering::query()->whereKey($offeringId)->first();
            if (! $offering) {
                throw new DomainException('The selected Course Offering does not exist.');
            }

            if ((int) $liveClass->school_id !== (int) $offering->school_id) {
                throw new DomainException('The Live Class and Course Offering must belong to the same tenant.');
            }

            if (! Subject::query()->where('school_id', $offering->school_id)->whereKey($offering->subject_id)->exists()) {
                throw new DomainException('The Course Offering subject does not belong to the Offering tenant.');
            }

            if ((int) $liveClass->subject_id !== (int) $offering->subject_id) {
                throw new DomainException('The Live Class subject must match the Course Offering subject.');
            }

            if ($liveClass->isDirty('course_offering_id') && ! in_array($offering->status, [CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true)) {
                throw new DomainException('A new operational Live Class requires an open or in-progress Course Offering.');
            }
        });

        static::deleting(function (self $liveClass): void {
            if ($liveClass->course_offering_id !== null) {
                throw new DomainException('Offering-backed Live Classes cannot be deleted; cancel them to preserve history.');
            }
        });
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class, 'course_offering_id');
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

    public function attendances()
    {
        return $this->hasMany(LiveClassAttendance::class, 'live_class_id');
    }

    public function materials()
    {
        return $this->hasMany(LiveClassMaterial::class, 'live_class_id');
    }

    public function notifications()
    {
        return $this->hasMany(LiveClassNotification::class, 'live_class_id');
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

    /**
     * Scheduled length in minutes, or null when either boundary is missing
     * (e.g. a draft with no times entered yet).
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->scheduled_at || !$this->ends_at) {
            return null;
        }

        return max(0, $this->scheduled_at->diffInMinutes($this->ends_at));
    }

    /**
     * Free (non-Workspace) Google accounts cut group Meet calls off at 60
     * minutes. There is no way to lift that from this app's side — Meet's
     * API doesn't expose a "this account is on Workspace" flag — so this is
     * a scheduling-time warning, not an enforced limit: an admin who knows
     * their account is upgraded can ignore it, and staff who don't yet know
     * about the cap are warned before they find out mid-class.
     */
    public const FREE_TIER_MINUTE_LIMIT = 60;

    public function exceedsGoogleMeetFreeTierLimit(): bool
    {
        return $this->platform === 'google_meet'
            && $this->duration_minutes !== null
            && $this->duration_minutes > self::FREE_TIER_MINUTE_LIMIT;
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

        if ($this->course_offering_id !== null) {
            return route('live_classes.recording.access', ['liveClass' => $this->id]);
        }

        return $url;
    }
}
