<?php

namespace App\Policies;

use App\Models\OnlineExamAnswer;
use App\Models\User;
use App\Support\Permissions\OnlineExamAuthorizer;

class OnlineExamAnswerPolicy
{
    public function view(User $user, OnlineExamAnswer $answer): bool
    {
        return app(OnlineExamAuthorizer::class)->canAccessSubmission($user, $answer->submission);
    }

    public function create(User $user, OnlineExamAnswer $answer): bool
    {
        return (int) $user->role_id === 7
            && app(OnlineExamAuthorizer::class)->canAccessSubmission($user, $answer->submission);
    }

    public function update(User $user, OnlineExamAnswer $answer): bool
    {
        if ((int) $user->role_id === 7) {
            return app(OnlineExamAuthorizer::class)->canAccessSubmission($user, $answer->submission)
                && $answer->submission->status === 'in_progress'
                && !$answer->submission->isExpired();
        }

        return app(OnlineExamAuthorizer::class)->canMarkAnswer($user, $answer);
    }

    public function mark(User $user, OnlineExamAnswer $answer): bool
    {
        return app(OnlineExamAuthorizer::class)->canMarkAnswer($user, $answer);
    }
}
