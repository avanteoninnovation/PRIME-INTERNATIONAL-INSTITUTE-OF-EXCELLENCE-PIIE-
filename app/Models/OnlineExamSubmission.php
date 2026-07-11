<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class OnlineExamSubmission extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_TIMED_OUT = 'timed_out';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_PENDING_MANUAL = 'pending_manual_marking';
    public const STATUS_FINALIZED = 'finalized';

    protected $table = 'online_exam_submissions';

    protected $fillable = [
        'online_exam_id', 'student_id', 'school_id',
        'answers', 'score', 'attempt_no', 'started_at', 'expires_at',
        'last_activity_at', 'submitted_at', 'submitted_via', 'status',
        'timeout_at', 'total_marks_snapshot', 'objective_score',
        'manual_score', 'passed', 'camera_consent_at',
        'camera_permission_granted', 'camera_ready_at',
        'fullscreen_started_at', 'browser_session_token',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'answers' => 'array',
        'attempt_no' => 'integer',
        'score' => 'decimal:2',
        'objective_score' => 'decimal:2',
        'manual_score' => 'decimal:2',
        'total_marks_snapshot' => 'integer',
        'passed' => 'boolean',
        'camera_permission_granted' => 'boolean',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'submitted_at' => 'datetime',
        'timeout_at' => 'datetime',
        'camera_consent_at' => 'datetime',
        'camera_ready_at' => 'datetime',
        'fullscreen_started_at' => 'datetime',
    ];

    public function exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function answerRows()
    {
        return $this->hasMany(OnlineExamAnswer::class, 'submission_id');
    }

    public function proctoringEvents()
    {
        return $this->hasMany(OnlineExamProctoringEvent::class, 'submission_id');
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeFinalized($query)
    {
        return $query->where('status', self::STATUS_FINALIZED);
    }

    public function computeExpiryAt(): ?Carbon
    {
        if (empty($this->started_at) || empty($this->exam)) {
            return null;
        }

        $durationExpiry = $this->started_at->copy()->addMinutes((int) $this->exam->duration_mins);

        if (!empty($this->exam->end_datetime)) {
            return $durationExpiry->lessThan($this->exam->end_datetime)
                ? $durationExpiry
                : $this->exam->end_datetime->copy();
        }

        return $durationExpiry;
    }

    public function isExpired(?Carbon $at = null): bool
    {
        $at = $at ?: now();
        if (empty($this->expires_at)) {
            return false;
        }

        return $at->gte($this->expires_at);
    }

    public function getEffectiveScoreAttribute(): float
    {
        $objective = (float) ($this->objective_score ?? 0);
        $manual = (float) ($this->manual_score ?? 0);

        if ($objective > 0 || $manual > 0) {
            return $objective + $manual;
        }

        return (float) ($this->score ?? 0);
    }

    public function remainingSeconds(?Carbon $at = null): int
    {
        $at = $at ?: now();
        if (empty($this->expires_at)) {
            return 0;
        }

        $seconds = $this->expires_at->diffInSeconds($at, false);
        return max(0, -$seconds);
    }

    public function isResultVisible(): bool
    {
        if (empty($this->exam)) {
            return false;
        }

        return $this->exam->isResultVisibleFor($this);
    }
}
