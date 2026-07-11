<?php

namespace App\Policies;

use App\Models\OnlineExam;
use App\Models\User;
use App\Support\Permissions\OnlineExamAuthorizer;

class OnlineExamPolicy
{
    public function viewAny(User $user): bool
    {
        return app(OnlineExamAuthorizer::class)->can($user, 'view_online_exams');
    }

    public function view(User $user, OnlineExam $exam): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);

        if (!$authorizer->sameSchool($user, (int) $exam->school_id)) {
            return false;
        }

        if ((int) $user->role_id === 7) {
            return $authorizer->can($user, 'sit_online_exams');
        }

        if ($authorizer->can($user, 'edit_all_online_exams')) {
            return true;
        }

        return $authorizer->ownsExam($user, $exam);
    }

    public function create(User $user): bool
    {
        return app(OnlineExamAuthorizer::class)->can($user, 'create_online_exams');
    }

    public function update(User $user, OnlineExam $exam): bool
    {
        return app(OnlineExamAuthorizer::class)->canManageExam($user, $exam);
    }

    public function delete(User $user, OnlineExam $exam): bool
    {
        if ($exam->submissions()->exists()) {
            return false;
        }

        return app(OnlineExamAuthorizer::class)->can($user, 'delete_online_exams')
            && app(OnlineExamAuthorizer::class)->canManageExam($user, $exam);
    }

    public function publish(User $user, OnlineExam $exam): bool
    {
        return app(OnlineExamAuthorizer::class)->can($user, 'publish_online_exams')
            && app(OnlineExamAuthorizer::class)->canManageExam($user, $exam);
    }

    public function unpublish(User $user, OnlineExam $exam): bool
    {
        if ($exam->submissions()->exists()) {
            return false;
        }

        return $this->publish($user, $exam);
    }

    public function cancel(User $user, OnlineExam $exam): bool
    {
        return app(OnlineExamAuthorizer::class)->can($user, 'cancel_online_exams')
            && app(OnlineExamAuthorizer::class)->canManageExam($user, $exam);
    }

    public function manageQuestions(User $user, OnlineExam $exam): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);
        return $authorizer->can($user, 'manage_exam_questions')
            && $authorizer->canManageExam($user, $exam);
    }

    public function viewAttempts(User $user, OnlineExam $exam): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);
        return $authorizer->can($user, 'view_exam_attempts')
            && $authorizer->canManageExam($user, $exam);
    }

    public function markAnswers(User $user, OnlineExam $exam): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);
        return $authorizer->can($user, 'mark_exam_answers')
            && $authorizer->canManageExam($user, $exam);
    }

    public function reviewProctoring(User $user, OnlineExam $exam): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);
        return $authorizer->can($user, 'review_exam_proctoring')
            && $authorizer->canManageExam($user, $exam);
    }

    public function sit(User $user, OnlineExam $exam): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);
        return (int) $user->role_id === 7
            && $authorizer->sameSchool($user, (int) $exam->school_id)
            && $authorizer->can($user, 'sit_online_exams');
    }

    public function viewResult(User $user, OnlineExam $exam): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);

        if ((int) $user->role_id === 7) {
            return $authorizer->sameSchool($user, (int) $exam->school_id)
                && $authorizer->can($user, 'view_exam_results');
        }

        return $authorizer->canManageExam($user, $exam)
            && $authorizer->can($user, 'view_exam_results');
    }
}
