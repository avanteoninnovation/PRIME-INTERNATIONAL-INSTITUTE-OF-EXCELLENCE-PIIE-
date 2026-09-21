<?php

namespace App\Support\Permissions;

use App\Models\OnlineExam;
use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamProctoringEvent;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use App\Models\TeacherPermission;
use App\Models\TeacherProgrammeAssignment;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Marking access follows the authoritative academic assignment, while
     * authoring access remains owner/admin controlled.
     */
    public function canTeachExam(User $user, OnlineExam $exam): bool
    {
        if ((int) $user->role_id !== 3 || !$this->sameSchool($user, (int) $exam->school_id)) {
            return false;
        }

        if ($exam->programme_id && (!Schema::hasTable('teacher_programme_assignments') || !TeacherProgrammeAssignment::where('teacher_id', $user->id)
            ->where('school_id', $user->school_id)
            ->where('programme_id', $exam->programme_id)->exists())) {
            return false;
        }

        if (empty($exam->class_id)) {
            $subject = $exam->subject;
            return $subject && $this->teacherCanUseSubject($user, (int) $subject->id);
        }

        return TeacherPermission::where('teacher_id', $user->id)
            ->where('school_id', $user->school_id)
            ->where('class_id', $exam->class_id)
            ->exists()
            && $this->teacherCanUseSubject($user, (int) $exam->subject_id);
    }

    public function canAccessExamAttempts(User $user, OnlineExam $exam): bool
    {
        if (!$this->sameSchool($user, (int) $exam->school_id)) {
            return false;
        }

        if ($this->can($user, 'edit_all_online_exams')) {
            return true;
        }

        // The author may mark their own exam; other teachers require the
        // authoritative class/course assignment.
        return $this->ownsExam($user, $exam) || $this->canTeachExam($user, $exam);
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

        if ($this->can($user, 'view_exam_attempts') && $this->canAccessExamAttempts($user, $exam)) {
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
