<?php

namespace App\Support\Permissions;

use App\Models\TeacherPermission;
use App\Models\TeacherProgrammeAssignment;
use App\Models\User;

class OnlineExamPermissionService
{
    public const KEYS = [
        'view_online_exams',
        'create_online_exams',
        'edit_own_online_exams',
        'edit_all_online_exams',
        'delete_online_exams',
        'publish_online_exams',
        'cancel_online_exams',
        'manage_exam_questions',
        'view_exam_attempts',
        'mark_exam_answers',
        'view_exam_results',
        'manage_exam_settings',
        'review_exam_proctoring',
        'sit_online_exams',
    ];

    public function has(User $user, string $permission): bool
    {
        if (!in_array($permission, self::KEYS, true)) {
            return false;
        }

        if (!$this->isAccountActive($user)) {
            return false;
        }

        if ($this->isSuperAdmin($user) || $this->isSchoolAdmin($user)) {
            return true;
        }

        if ($this->hasMenuPermission($user, $permission)) {
            return true;
        }

        if ($this->hasRoleSettingPermission($user, $permission)) {
            return true;
        }

        if ((int) $user->role_id === 3) {
            return $this->teacherFallbackPermission($permission);
        }

        if ((int) $user->role_id === 7) {
            return $permission === 'sit_online_exams' || $permission === 'view_exam_results';
        }

        if ((int) $user->role_id === 19) {
            return in_array($permission, [
                'view_online_exams',
                'view_exam_attempts',
                'view_exam_results',
                'review_exam_proctoring',
            ], true);
        }

        return false;
    }

    public function hasAny(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->has($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function teacherCanUseSubject(User $user, ?int $subjectId): bool
    {
        if ((int) $user->role_id !== 3 || empty($subjectId)) {
            return true;
        }

        $subject = \App\Models\Subject::where('id', $subjectId)
            ->where('school_id', $user->school_id)
            ->first();

        if (!$subject) {
            return false;
        }

        // Programme-linked (HEI) subjects have no class_id — check the
        // programme-based assignment table instead. Fail open only while
        // this school hasn't configured any programme assignment yet (no
        // rows at all for the school), so schools that haven't adopted this
        // feature aren't suddenly locked out of every HEI subject; once an
        // admin assigns at least one teacher to a programme, it's enforced
        // normally, same as class_id-based TeacherPermission always was.
        if (empty($subject->class_id)) {
            if (empty($subject->programme_id)) {
                return true;
            }

            $schoolHasConfiguredAssignments = TeacherProgrammeAssignment::where('school_id', $user->school_id)->exists();
            if (!$schoolHasConfiguredAssignments) {
                return true;
            }

            return TeacherProgrammeAssignment::where('teacher_id', $user->id)
                ->where('school_id', $user->school_id)
                ->where('programme_id', $subject->programme_id)
                ->exists();
        }

        return TeacherPermission::where('teacher_id', $user->id)
            ->where('school_id', $user->school_id)
            ->where('class_id', $subject->class_id)
            ->exists();
    }

    public function hasMenuPermission(User $user, string $permission): bool
    {
        if (empty($user->menu_permission) || $user->menu_permission === 'null') {
            return false;
        }

        $menuPermissions = json_decode($user->menu_permission, true);
        if (!is_array($menuPermissions)) {
            return false;
        }

        $menuPermissions = $this->normalizeMenuPermissionAliases($menuPermissions);

        $map = [
            'view_online_exams' => ['admin.online_exams', 'admin.online_exams.index', 'teacher.online_exams', 'student.online_exam.list'],
            'create_online_exams' => ['admin.online_exams', 'admin.online_exams.index', 'teacher.online_exams'],
            'edit_own_online_exams' => ['admin.online_exams', 'admin.online_exams.index', 'teacher.online_exams'],
            'edit_all_online_exams' => ['admin.online_exams', 'admin.online_exams.index'],
            'delete_online_exams' => ['admin.online_exams', 'admin.online_exams.index'],
            'publish_online_exams' => ['admin.online_exams', 'admin.online_exams.index'],
            'cancel_online_exams' => ['admin.online_exams', 'admin.online_exams.index'],
            'manage_exam_questions' => ['admin.question_bank', 'admin.question_bank.index', 'admin.online_exams', 'admin.online_exams.index', 'teacher.question_bank', 'teacher.online_exams'],
            'view_exam_attempts' => ['admin.online_exams', 'admin.online_exams.index', 'teacher.online_exams'],
            'mark_exam_answers' => ['admin.online_exams', 'admin.online_exams.index', 'teacher.online_exams'],
            'view_exam_results' => ['admin.online_exams', 'admin.online_exams.index', 'teacher.online_exams', 'student.online_exam.list'],
            'manage_exam_settings' => ['admin.online_exams', 'admin.online_exams.index'],
            'review_exam_proctoring' => ['admin.online_exams', 'admin.online_exams.index'],
            'sit_online_exams' => ['student.online_exam.list'],
        ];

        foreach ($map[$permission] ?? [] as $key) {
            if (in_array($key, $menuPermissions, true)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeMenuPermissionAliases(array $menuPermissions): array
    {
        $normalized = [];
        foreach ($menuPermissions as $entry) {
            $value = trim((string) $entry);
            if ($value === '') {
                continue;
            }

            $normalized[] = $value;

            if ($value === 'admin.online_exams') {
                $normalized[] = 'admin.online_exams.index';
            } elseif ($value === 'admin.online_exams.index') {
                $normalized[] = 'admin.online_exams';
            }

            if ($value === 'admin.question_bank') {
                $normalized[] = 'admin.question_bank.index';
            } elseif ($value === 'admin.question_bank.index') {
                $normalized[] = 'admin.question_bank';
            }
        }

        return array_values(array_unique($normalized));
    }

    public function hasRoleSettingPermission(User $user, string $permission): bool
    {
        $raw = get_settings('role_perm_' . (int) $user->role_id);
        if (empty($raw)) {
            return false;
        }

        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return false;
        }

        if (in_array($permission, $decoded, true)) {
            return true;
        }

        $legacyRoleMap = [
            'view_online_exams' => ['online_exams', 'exams'],
            'create_online_exams' => ['online_exams', 'exams'],
            'edit_own_online_exams' => ['online_exams', 'exams'],
            'edit_all_online_exams' => ['online_exams', 'exams'],
            'delete_online_exams' => ['online_exams', 'exams'],
            'publish_online_exams' => ['online_exams', 'exams'],
            'cancel_online_exams' => ['online_exams', 'exams'],
            'manage_exam_questions' => ['online_exams', 'question_bank', 'exams'],
            'view_exam_attempts' => ['online_exams', 'exams'],
            'mark_exam_answers' => ['online_exams', 'exams'],
            'view_exam_results' => ['online_exams', 'results', 'exams'],
            'manage_exam_settings' => ['online_exams', 'settings'],
            'review_exam_proctoring' => ['online_exams', 'exams'],
            'sit_online_exams' => ['online_exams', 'exams'],
        ];

        foreach ($legacyRoleMap[$permission] ?? [] as $legacyKey) {
            if (in_array($legacyKey, $decoded, true)) {
                return true;
            }
        }

        return false;
    }

    private function isAccountActive(User $user): bool
    {
        return $user->account_status !== 'disable';
    }

    private function isSuperAdmin(User $user): bool
    {
        return (int) $user->role_id === 1;
    }

    private function isSchoolAdmin(User $user): bool
    {
        return (int) $user->role_id === 2;
    }

    private function teacherFallbackPermission(string $permission): bool
    {
        return in_array($permission, [
            'view_online_exams',
            'create_online_exams',
            'edit_own_online_exams',
            'manage_exam_questions',
            'view_exam_attempts',
            'mark_exam_answers',
            'view_exam_results',
        ], true);
    }
}
