<?php

namespace App\Policies;

use App\Models\OnlineExamProctoringEvent;
use App\Models\User;
use App\Support\Permissions\OnlineExamAuthorizer;

class OnlineExamProctoringEventPolicy
{
    public function view(User $user, OnlineExamProctoringEvent $event): bool
    {
        return app(OnlineExamAuthorizer::class)->canReviewProctoring($user, $event);
    }

    public function create(User $user, int $submissionId): bool
    {
        return (int) $user->role_id === 7
            && app(OnlineExamAuthorizer::class)->can($user, 'sit_online_exams');
    }

    public function review(User $user, OnlineExamProctoringEvent $event): bool
    {
        return app(OnlineExamAuthorizer::class)->canReviewProctoring($user, $event);
    }
}
