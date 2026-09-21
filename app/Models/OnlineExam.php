<?php

namespace App\Models;

use Carbon\Carbon;
use App\Support\OnlineExams\AnswerKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Programme;
use App\Models\Session;

class OnlineExam extends Model
{
    use HasFactory;

    protected $table = 'online_exams';

    protected $fillable = [
        'school_id', 'title', 'subject_id', 'class_id', 'exam_type',
        'programme_id', 'session_id',
        'start_datetime', 'end_datetime', 'duration_mins', 'total_marks',
        'pass_mark', 'instructions', 'is_published', 'auto_submit', 'created_by',
        'workflow_state', 'max_attempts', 'shuffle_questions', 'shuffle_options',
        'allow_previous_navigation', 'result_release_policy', 'webcam_required',
        'fullscreen_required', 'creator_id', 'updater_id', 'reviewed_by',
        'reviewed_at', 'cancelled_at', 'cancellation_reason', 'locked_at',
    ];

    protected $appends = ['lifecycle_status', 'duration_minutes'];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
        'reviewed_at'    => 'datetime',
        'cancelled_at'   => 'datetime',
        'locked_at'      => 'datetime',
        'is_published'   => 'boolean',
        'auto_submit'    => 'boolean',
        'shuffle_questions' => 'boolean',
        'shuffle_options' => 'boolean',
        'allow_previous_navigation' => 'boolean',
        'webcam_required' => 'boolean',
        'fullscreen_required' => 'boolean',
        'max_attempts'   => 'integer',
        'total_marks'    => 'integer',
        'pass_mark'      => 'integer',
        'duration_mins'  => 'integer',
    ];

    public function questions()
    {
        return $this->hasMany(OnlineExamQuestion::class, 'online_exam_id');
    }

    public function submissions()
    {
        return $this->hasMany(OnlineExamSubmission::class, 'online_exam_id');
    }

    public function notifications()
    {
        return $this->hasMany(OnlineExamNotification::class, 'online_exam_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function classRoom()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updater_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeForTeacher($query, int $teacherId)
    {
        return $query->where(function ($q) use ($teacherId) {
            $q->where('creator_id', $teacherId)->orWhere('created_by', $teacherId);
        });
    }

    public function scopePublished($query)
    {
        return $query->where('workflow_state', 'published');
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class, 'programme_id');
    }

    public function academicSession()
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    /**
     * Exam schedules are entered as local institutional times. Prefer the
     * existing system timezone setting (for example Africa/Nairobi) when
     * interpreting those database values; fall back to the application
     * timezone for isolated tests and installations without that setting.
     */
    public function scheduleTimezone(): string
    {
        $timezone = function_exists('get_settings') ? get_settings('timezone') : null;
        $timezone = $timezone ?: config('app.timezone', 'UTC');

        return in_array($timezone, timezone_identifiers_list(), true)
            ? $timezone
            : config('app.timezone', 'UTC');
    }

    public function scheduledStartAt(): ?Carbon
    {
        $value = $this->getRawOriginal('start_datetime');

        return $value ? Carbon::parse($value, $this->scheduleTimezone()) : null;
    }

    public function scheduledEndAt(): ?Carbon
    {
        $value = $this->getRawOriginal('end_datetime');

        return $value ? Carbon::parse($value, $this->scheduleTimezone()) : null;
    }

    public function isWithinScheduledWindow(?Carbon $at = null): bool
    {
        $timezone = $this->scheduleTimezone();
        $at = ($at ?: Carbon::now($timezone))->copy()->setTimezone($timezone);
        $start = $this->scheduledStartAt();
        $end = $this->scheduledEndAt();

        return (!$start || $at->gte($start)) && (!$end || $at->lt($end));
    }

    public function scopeActive($query, ?Carbon $at = null)
    {
        $at = $at ?: now();

        return $query->where('workflow_state', 'published')
            ->where(function ($q) use ($at) {
                $q->whereNull('start_datetime')->orWhere('start_datetime', '<=', $at);
            })
            ->where(function ($q) use ($at) {
                $q->whereNull('end_datetime')->orWhere('end_datetime', '>=', $at);
            });
    }

    public function scopeEnded($query, ?Carbon $at = null)
    {
        $at = $at ?: now();

        return $query->where('workflow_state', 'published')
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<', $at);
    }

    public function scopeVisibleToStudent($query, int $schoolId, ?int $classId, ?int $programmeId = null, array $sessionIds = [])
    {
        return $query->forSchool($schoolId)
            ->published()
            ->where(function ($q) use ($classId) {
                $q->whereNull('class_id');
                if ($classId) {
                    $q->orWhere('class_id', $classId);
                }
            })
            ->where(function ($q) use ($programmeId) {
                $q->whereNull('programme_id');
                if ($programmeId) $q->orWhere('programme_id', $programmeId);
            })
            ->where(function ($q) use ($sessionIds) {
                $q->whereNull('session_id');
                if ($sessionIds) $q->orWhereIn('session_id', $sessionIds);
            });
    }

    public function getLifecycleStatusAttribute(): string
    {
        if ($this->workflow_state === 'cancelled') {
            return 'cancelled';
        }

        if ($this->workflow_state !== 'published') {
            return $this->workflow_state ?: 'draft';
        }

        $now = Carbon::now($this->scheduleTimezone());
        $start = $this->scheduledStartAt();
        $end = $this->scheduledEndAt();
        if ($start && $now->lt($start)) {
            return 'published';
        }

        if ($end && $now->gte($end)) {
            return 'ended';
        }

        return 'active';
    }

    public function getDurationMinutesAttribute(): int
    {
        return (int) ($this->duration_mins ?? 0);
    }

    public function setDurationMinutesAttribute($value): void
    {
        $this->attributes['duration_mins'] = $value;
    }

    public function setIsPublishedAttribute($value): void
    {
        $this->attributes['is_published'] = (int) (bool) $value;
    }

    public function isStructurallyLocked(): bool
    {
        if (!empty($this->locked_at)) {
            return true;
        }

        return $this->submissions()->whereIn('status', [
            OnlineExamSubmission::STATUS_IN_PROGRESS,
            OnlineExamSubmission::STATUS_SUBMITTED,
            OnlineExamSubmission::STATUS_TIMED_OUT,
            OnlineExamSubmission::STATUS_PENDING_MANUAL,
            OnlineExamSubmission::STATUS_FINALIZED,
        ])->exists();
    }

    public function publicationReadinessErrors(): array
    {
        $errors = [];

        if (empty($this->title)) {
            $errors[] = 'Title is required.';
        }

        if ((int) $this->duration_mins <= 0) {
            $errors[] = 'Duration must be greater than zero.';
        }

        if ((int) $this->total_marks <= 0) {
            $errors[] = 'Total marks must be greater than zero.';
        }

        if ((int) $this->pass_mark > (int) $this->total_marks) {
            $errors[] = 'Pass mark cannot exceed total marks.';
        }

        if ($this->start_datetime && $this->end_datetime && $this->end_datetime->lte($this->start_datetime)) {
            $errors[] = 'End time must be later than start time.';
        }

        $questions = $this->questions;
        if ($questions->count() < 1) {
            $errors[] = 'At least one question is required.';
        } else {
            $questionMarks = (int) $questions->sum('marks');
            if ($questionMarks !== (int) $this->total_marks) {
                $errors[] = "Total question marks ({$questionMarks}) must equal exam total marks ({$this->total_marks}).";
            }
            foreach ($questions as $question) {
                $type = $question->normalized_type;
                $key = AnswerKey::forQuestion($question);
                if ($type === 'true_false' && $key === null) {
                    $errors[] = "Question {$question->id} has an invalid true/false answer key.";
                }
                if ($type === 'multiple_choice' && $key === null) {
                    $errors[] = "Question {$question->id} has an invalid multiple-choice answer key.";
                }
            }
        }

        $validPolicies = ['immediate', 'after_exam_end', 'manual'];
        if (!in_array($this->result_release_policy ?: 'immediate', $validPolicies, true)) {
            $errors[] = 'Invalid result release policy.';
        }

        return $errors;
    }

    public function isResultVisibleFor(OnlineExamSubmission $submission): bool
    {
        $reviewState = $submission->result_review_state;
        // Rows created before governance metadata existed are readable using
        // their persisted technical publication state; migrated rows always
        // have an explicit result_review_state.
        if ($reviewState === null && $submission->status === OnlineExamSubmission::STATUS_RESULT_PUBLISHED) {
            $reviewState = 'published';
        }
        if (!$submission->isAttemptCompleted() || $submission->status !== OnlineExamSubmission::STATUS_RESULT_PUBLISHED || $reviewState !== 'published') {
            return false;
        }

        $policy = $this->result_release_policy ?: 'immediate';
        if ($policy === 'after_exam_end') {
            $end = $this->scheduledEndAt();
            if (!$end) {
                return false;
            }

            return Carbon::now($this->scheduleTimezone())->gte($end);
        }

        // All release policies require the persisted Admin publication state.
        // "immediate" controls when Admin may publish; it never auto-publishes.
        return true;
    }
}
