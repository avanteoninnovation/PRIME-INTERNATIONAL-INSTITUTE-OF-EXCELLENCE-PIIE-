<?php

namespace App\Policies;

use App\Models\OnlineExamQuestion;
use App\Models\User;
use App\Support\Permissions\OnlineExamAuthorizer;

class OnlineExamQuestionPolicy
{
    public function view(User $user, OnlineExamQuestion $question): bool
    {
        return app(OnlineExamAuthorizer::class)->canManageQuestion($user, $question);
    }

    public function create(User $user, int $examId): bool
    {
        return app(OnlineExamAuthorizer::class)->can($user, 'manage_exam_questions')
            && app(OnlineExamAuthorizer::class)->can($user, 'create_online_exams');
    }

    public function update(User $user, OnlineExamQuestion $question): bool
    {
        return app(OnlineExamAuthorizer::class)->canManageQuestion($user, $question)
            && !$question->exam?->isStructurallyLocked();
    }

    public function delete(User $user, OnlineExamQuestion $question): bool
    {
        return app(OnlineExamAuthorizer::class)->canManageQuestion($user, $question)
            && app(OnlineExamAuthorizer::class)->can($user, 'manage_exam_questions')
            && !$question->exam?->isStructurallyLocked();
    }
}
