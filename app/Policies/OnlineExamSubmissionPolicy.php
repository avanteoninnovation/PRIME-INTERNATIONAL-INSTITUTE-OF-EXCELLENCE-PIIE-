<?php

namespace App\Policies;

use App\Models\OnlineExamSubmission;
use App\Models\User;
use App\Support\Permissions\OnlineExamAuthorizer;

class OnlineExamSubmissionPolicy
{
    public function view(User $user, OnlineExamSubmission $submission): bool
    {
        return app(OnlineExamAuthorizer::class)->canAccessSubmission($user, $submission);
    }

    public function create(User $user, int $examId): bool
    {
        return (int) $user->role_id === 7
            && app(OnlineExamAuthorizer::class)->can($user, 'sit_online_exams');
    }

    public function submit(User $user, OnlineExamSubmission $submission): bool
    {
        return (int) $user->role_id === 7
            && app(OnlineExamAuthorizer::class)->canAccessSubmission($user, $submission)
            && $submission->status === OnlineExamSubmission::STATUS_IN_PROGRESS;
    }

    public function forceSubmit(User $user, OnlineExamSubmission $submission): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);

        return $authorizer->canAccessSubmission($user, $submission)
            && $authorizer->canAny($user, ['mark_exam_answers', 'view_exam_attempts']);
    }

    public function grade(User $user, OnlineExamSubmission $submission): bool
    {
        $authorizer = app(OnlineExamAuthorizer::class);

        if (!$authorizer->canAccessSubmission($user, $submission)) {
            return false;
        }

        return $authorizer->canAny($user, ['mark_exam_answers', 'manage_exam_results']);
    }

    public function viewResult(User $user, OnlineExamSubmission $submission): bool
    {
        if ((int) $user->role_id === 7) {
            return app(OnlineExamAuthorizer::class)->canAccessSubmission($user, $submission)
                && $submission->isResultVisible();
        }

        return app(OnlineExamAuthorizer::class)->canAccessSubmission($user, $submission)
            && app(OnlineExamAuthorizer::class)->can($user, 'view_exam_results');
    }
}
