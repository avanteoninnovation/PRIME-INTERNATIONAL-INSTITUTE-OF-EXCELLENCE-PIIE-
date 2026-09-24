<?php

namespace App\Providers;

use App\Models\LiveClass;
use App\Models\OnlineExam;
use App\Models\OnlineExamAnswer;
use App\Models\OnlineExamProctoringEvent;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamSubmission;
use App\Policies\LiveClassPolicy;
use App\Policies\OnlineExamAnswerPolicy;
use App\Policies\OnlineExamPolicy;
use App\Policies\OnlineExamProctoringEventPolicy;
use App\Policies\OnlineExamQuestionPolicy;
use App\Policies\OnlineExamSubmissionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Support\Permissions\PermissionService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        LiveClass::class => LiveClassPolicy::class,
        OnlineExam::class => OnlineExamPolicy::class,
        OnlineExamQuestion::class => OnlineExamQuestionPolicy::class,
        OnlineExamSubmission::class => OnlineExamSubmissionPolicy::class,
        OnlineExamAnswer::class => OnlineExamAnswerPolicy::class,
        OnlineExamProctoringEvent::class => OnlineExamProctoringEventPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // RBAC Phase 3A: one entry point for staff permissions — $user->can('permission', 'finance.view'),
        // @can('permission', 'finance.view') or @permission('finance.view') … @endpermission in Blade.
        // Deliberately not a Gate::before, so existing policies are unaffected.
        Gate::define('permission', fn ($user, string $key) => app(PermissionService::class)->allows($user, $key));
        Blade::if('permission', fn (string $key) => app(PermissionService::class)->allows(auth()->user(), $key));

        //
    }
}
