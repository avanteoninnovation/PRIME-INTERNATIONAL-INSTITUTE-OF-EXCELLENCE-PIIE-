<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Classes;
use App\Models\Department;
use App\Models\ExamCategory;
use App\Models\Grade;
use App\Models\Routine;
use App\Models\StudentFeeManager;
use App\Models\StudentProfile;
use App\Models\Syllabus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Generic Eloquent observer that turns model create/update/delete events
 * into audit log entries automatically, so CRUD tracking does not need to
 * be hand-added inside every controller action.
 *
 * Registered via `Model::observe(AuditableObserver::class)` (see
 * AuditServiceProvider) for models that have NO existing manual
 * AuditLog::record() calls in their controllers — attaching it to an
 * already-instrumented model would produce duplicate entries for the same
 * action. Must stay constructor-argument-free: Laravel's Eloquent observer
 * wiring only stores the observer's class name and re-resolves it fresh
 * from the container on every event, discarding any instance state — so
 * the module label is looked up from MODULES by model class instead of
 * being injected.
 */
class AuditableObserver
{
    /**
     * Models with automatic CRUD tracking, and the module label each is
     * reported under. Kept in one place so AuditServiceProvider and this
     * observer never drift apart.
     *
     * @var array<class-string, string>
     */
    public const MODULES = [
        User::class         => 'Staff & Students',
        Classes::class       => 'Classes',
        Department::class    => 'Departments',
        Book::class           => 'Library',
        BookIssue::class      => 'Library',
        ExamCategory::class   => 'Examinations',
        Routine::class        => 'Routine',
        Syllabus::class       => 'Syllabus',
        Grade::class          => 'Results',
        StudentProfile::class => 'Staff & Students',
        StudentFeeManager::class => 'Finance',
    ];

    /**
     * Attributes that change as a side effect of normal framework/auth
     * behaviour (e.g. touching remember_token on login) rather than a
     * meaningful business edit. An update whose only changed attributes
     * are in this list is not logged at all.
     */
    private const SILENT_ATTRIBUTES = [
        'updated_at', 'remember_token', 'email_verified_at',
    ];

    public function created(Model $model): void
    {
        AuditLog::record('CREATE', $this->moduleFor($model), $this->describe($model, 'Created'), [
            'event_type'  => 'DATA',
            'record_type' => get_class($model),
            'record_id'   => $model->getKey(),
            'new_values'  => $this->presentable($model->getAttributes()),
        ]);
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();

        foreach (self::SILENT_ATTRIBUTES as $silent) {
            unset($changes[$silent]);
        }

        if (empty($changes)) {
            return;
        }

        $original = [];
        foreach (array_keys($changes) as $key) {
            $original[$key] = $model->getOriginal($key);
        }

        $changedFields = implode(', ', array_keys($changes));

        AuditLog::record('UPDATE', $this->moduleFor($model), $this->describe($model, 'Updated') . " — changed: {$changedFields}", [
            'event_type'  => 'DATA',
            'record_type' => get_class($model),
            'record_id'   => $model->getKey(),
            'old_values'  => $this->presentable($original),
            'new_values'  => $this->presentable($changes),
        ]);
    }

    public function deleted(Model $model): void
    {
        AuditLog::record('DELETE', $this->moduleFor($model), $this->describe($model, 'Deleted'), [
            'event_type'  => 'DATA',
            'record_type' => get_class($model),
            'record_id'   => $model->getKey(),
            'old_values'  => $this->presentable($model->getAttributes()),
        ]);
    }

    private function moduleFor(Model $model): string
    {
        return self::MODULES[get_class($model)] ?? class_basename($model);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function presentable(array $attributes): array
    {
        unset($attributes['created_at'], $attributes['updated_at'], $attributes['remember_token']);

        return $attributes;
    }

    private function describe(Model $model, string $verb): string
    {
        $label = method_exists($model, 'auditLabel')
            ? $model->auditLabel()
            : class_basename($model) . ' #' . $model->getKey();

        return "{$verb} {$label}";
    }
}
