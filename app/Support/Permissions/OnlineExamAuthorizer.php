<?php

namespace App\Support\Permissions;

use App\Models\OnlineExam;
use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamProctoringEvent;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use App\Models\User;

class OnlineExamAuthorizer
{
    private OnlineExamPermissionService $permissionService;

    public function __construct(OnlineExamPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function can(User $user, string $permission): bool
    {
        return $this->permissionService->has($user, $permission);
    }

    public function canAny(User $user, array $permissions): bool
    {
        return $this->permissionService->hasAny($user, $permissions);
    }

    public function sameSchool(User $user, int $schoolId): bool
    {
        return (int) $user->school_id === (int) $schoolId;
    }

    public function ownsExam(User $user, OnlineExam $exam): bool
    {
        return (int) $user->id === (int) ($exam->creator_id ?: $exam->created_by);
    }

    public function canManageExam(User $user, OnlineExam $exam): bool
    {
        if (!$this->sameSchool($user, (int) $exam->school_id)) {
            return false;
        }

        if ($this->can($user, 'edit_all_online_exams')) {
            return true;
        }

        return $this->can($user, 'edit_own_online_exams') && $this->ownsExam($user, $exam);
    }

    public function canManageQuestion(User $user, OnlineExamQuestion $question): bool
    {
        $exam = $question->exam;
        if (!$exam) {
            return false;
        }

        if (!$this->can($user, 'manage_exam_questions')) {
            return false;
        }

        return $this->canManageExam($user, $exam);
    }

    public function canAccessSubmission(User $user, OnlineExamSubmission $submission): bool
    {
        if (!$this->sameSchool($user, (int) $submission->school_id)) {
            return false;
        }

        if ((int) $user->role_id === 7) {
            return (int) $submission->student_id === (int) $user->id;
        }

        $exam = $submission->exam;
        if (!$exam) {
            return false;
        }

        if ($this->can($user, 'view_exam_attempts') && $this->canManageExam($user, $exam)) {
            return true;
        }

        return false;
    }

    public function canMarkAnswer(User $user, OnlineExamAnswer $answer): bool
    {
        if (!$this->can($user, 'mark_exam_answers')) {
            return false;
        }

        $submission = $answer->submission;
        if (!$submission) {
            return false;
        }

        return $this->canAccessSubmission($user, $submission);
    }

    public function canReviewProctoring(User $user, OnlineExamSubmission|OnlineExamProctoringEvent $target): bool
    {
        if (!$this->can($user, 'review_exam_proctoring')) {
            return false;
        }

        $submission = $target instanceof OnlineExamSubmission ? $target : $target->submission;
        if (!$submission) {
            return false;
        }

        return $this->canAccessSubmission($user, $submission);
    }

    public function teacherCanUseSubject(User $user, ?int $subjectId): bool
    {
        return $this->permissionService->teacherCanUseSubject($user, $subjectId);
    }
}
