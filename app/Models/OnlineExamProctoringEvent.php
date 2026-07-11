<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnlineExamProctoringEvent extends Model
{
    public const EVENT_TYPES = [
        'consent_given',
        'camera_permission_granted',
        'camera_permission_denied',
        'camera_started',
        'camera_stopped',
        'tab_hidden',
        'fullscreen_started',
        'fullscreen_exited',
        'connection_lost',
        'connection_restored',
        'snapshot_captured',
        'snapshot_failed',
    ];

    protected $table = 'online_exam_proctoring_events';

    protected $fillable = [
        'submission_id',
        'event_type',
        'event_time',
        'metadata',
        'reviewed_by',
        'review_status',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'metadata' => 'array',
    ];

    public function submission()
    {
        return $this->belongsTo(OnlineExamSubmission::class, 'submission_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeForSubmission($query, int $submissionId)
    {
        return $query->where('submission_id', $submissionId);
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('event_time')->orderBy('id');
    }
}
