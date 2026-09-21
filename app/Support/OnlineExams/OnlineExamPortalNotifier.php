<?php

namespace App\Support\OnlineExams;

use App\Models\OnlineExam;
use App\Models\OnlineExamSubmission;
use App\Models\OnlineExamUserNotification;
use App\Models\User;

final class OnlineExamPortalNotifier
{
    public static function admins(string $type, string $title, string $message, OnlineExam $exam, ?int $actorId = null, ?string $eventKey = null, ?int $submissionId = null): void
    {
        $users = User::where('school_id', $exam->school_id)->where('role_id', 2)->pluck('id');
        $url = $submissionId
            ? route('admin.online_exams.results', $exam->id) . '?submission=' . (int) $submissionId . '#submission-' . (int) $submissionId
            : route('admin.online_exams.show', $exam->id);
        foreach ($users as $userId) self::create($exam->school_id, (int) $userId, $type, $title, $message, $url, $actorId, $exam->id, $submissionId, $eventKey ? $eventKey . ':user:' . $userId : null);
    }

    public static function teacher(string $type, string $title, string $message, OnlineExam $exam, ?int $actorId = null, ?string $eventKey = null): void
    {
        $teacherId = (int) ($exam->creator_id ?: $exam->created_by);
        if ($teacherId) self::create($exam->school_id, $teacherId, $type, $title, $message, route('teacher.online_exams.show', $exam->id), $actorId, $exam->id, null, $eventKey ? $eventKey . ':user:' . $teacherId : null);
    }

    public static function eligibleStudents(OnlineExam $exam, string $type, string $title, string $message, ?int $actorId = null, ?string $eventKey = null, ?OnlineExamSubmission $submission = null): void
    {
        $query = User::where('school_id', $exam->school_id)->where('role_id', 7);
        if ($exam->class_id) $query->whereExists(fn ($q) => $q->selectRaw('1')->from('enrollment')->whereColumn('enrollment.user_id', 'users.id')->where('enrollment.class_id', $exam->class_id));
        foreach ($query->pluck('id') as $userId) self::create($exam->school_id, (int) $userId, $type, $title, $message, $submission ? route('student.online_exam.result', $submission->id) : route('student.online_exam.list'), $actorId, $exam->id, $submission?->id, $eventKey ? $eventKey . ':user:' . $userId : null);
    }

    public static function create(int $schoolId, int $userId, string $type, string $title, string $message, ?string $url, ?int $actorId = null, ?int $examId = null, ?int $submissionId = null, ?string $eventKey = null): void
    {
        $payload = [
                'school_id' => $schoolId, 'user_id' => $userId, 'actor_id' => $actorId,
                'online_exam_id' => $examId, 'submission_id' => $submissionId,
                'type' => $type, 'title' => $title, 'message' => $message,
                'action_url' => $url, 'event_key' => $eventKey,
                'created_at' => now(), 'updated_at' => now(),
            ];
        // insertOrIgnore makes repeated lifecycle requests idempotent and
        // remains safe under concurrent delivery attempts.
        \Illuminate\Support\Facades\DB::table('online_exam_user_notifications')->insertOrIgnore($payload);
    }
}
