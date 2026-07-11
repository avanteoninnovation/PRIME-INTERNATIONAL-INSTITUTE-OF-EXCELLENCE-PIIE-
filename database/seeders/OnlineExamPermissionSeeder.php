<?php

namespace Database\Seeders;

use App\Models\GlobalSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OnlineExamPermissionSeeder extends Seeder
{
    private const ONLINE_EXAM_KEYS = [
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

    public function run(): void
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $roleIdColumn = Schema::hasColumn('roles', 'role_id') ? 'role_id' : 'id';
        $nameColumn = Schema::hasColumn('roles', 'name')
            ? 'name'
            : (Schema::hasColumn('roles', 'role_name') ? 'role_name' : null);

        $roles = DB::table('roles')->get();
        foreach ($roles as $role) {
            $roleId = (int) ($role->{$roleIdColumn} ?? 0);
            if ($roleId <= 0) {
                continue;
            }

            $roleName = $nameColumn ? strtolower(trim((string) ($role->{$nameColumn} ?? ''))) : '';
            $defaults = $this->defaultsForRole($roleName);
            if (empty($defaults)) {
                continue;
            }

            $key = 'role_perm_' . $roleId;
            $current = GlobalSettings::where('key', $key)->value('value');
            $existingPermissions = $this->normalizeList($current ? json_decode((string) $current, true) : []);

            $denied = $this->extractDeniedPermissions($existingPermissions);

            $toAdd = array_values(array_filter($defaults, function (string $permission) use ($denied) {
                return !in_array($permission, $denied, true);
            }));

            $merged = $this->normalizeList(array_merge($existingPermissions, $toAdd));

            GlobalSettings::updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($merged)]
            );
        }
    }

    private function defaultsForRole(string $roleName): array
    {
        $isSuperAdmin = str_contains($roleName, 'super') && str_contains($roleName, 'admin');
        $isSchoolAdmin = $roleName === 'admin' || str_contains($roleName, 'school admin') || str_contains($roleName, 'administrator');
        $isTeacher = str_contains($roleName, 'teacher') || str_contains($roleName, 'lecturer');
        $isStudent = str_contains($roleName, 'student');

        if ($isSuperAdmin) {
            return self::ONLINE_EXAM_KEYS;
        }

        if ($isSchoolAdmin) {
            return [
                'view_online_exams',
                'create_online_exams',
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
            ];
        }

        if ($isTeacher) {
            return [
                'view_online_exams',
                'create_online_exams',
                'edit_own_online_exams',
                'manage_exam_questions',
                'view_exam_attempts',
                'mark_exam_answers',
                'view_exam_results',
            ];
        }

        if ($isStudent) {
            return [
                'sit_online_exams',
            ];
        }

        return [];
    }

    private function normalizeList($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $entry) {
            if (is_array($entry) || is_object($entry)) {
                continue;
            }

            $stringEntry = trim((string) $entry);
            if ($stringEntry === '') {
                continue;
            }

            $normalized[] = $stringEntry;
        }

        return array_values(array_unique($normalized));
    }

    private function extractDeniedPermissions(array $existingPermissions): array
    {
        $denied = [];
        foreach ($existingPermissions as $entry) {
            if (str_starts_with($entry, '!')) {
                $denied[] = ltrim($entry, '!');
                continue;
            }

            if (str_starts_with($entry, '-')) {
                $denied[] = ltrim($entry, '-');
            }
        }

        return array_values(array_unique(array_filter($denied)));
    }
}
