<?php

namespace Database\Seeders;

use App\Models\GlobalSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveClassPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            'View Live Classes',
            'Create Live Classes',
            'Edit Live Classes',
            'Delete Live Classes',
            'Cancel Live Classes',
            'Publish Live Classes',
            'Join Live Classes',
            'Manage Live Class Platforms',
        ];

        $roleKeyColumn = Schema::hasColumn('roles', 'role_id') ? 'role_id' : 'id';
        $roleNameColumn = Schema::hasColumn('roles', 'name') ? 'name' : null;

        $roles = DB::table('roles')->get();
        foreach ($roles as $role) {
            $roleId = (int) ($role->{$roleKeyColumn} ?? 0);
            if ($roleId <= 0) {
                continue;
            }

            $roleName = $roleNameColumn ? strtolower((string) $role->{$roleNameColumn}) : '';
            $default = $this->defaultPermissionsForRole($roleId, $roleName, $permissions);

            $key = "role_perm_{$roleId}";
            $existing = GlobalSettings::where('key', $key)->value('value');
            $decoded = $existing ? json_decode($existing, true) : [];
            if (!is_array($decoded)) {
                $decoded = [];
            }

            $merged = array_values(array_unique(array_merge($decoded, $default)));
            GlobalSettings::updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($merged)]
            );
        }

        $platformDefaults = [
            'live_class_platform_jitsi' => '1',
            'live_class_platform_google_meet' => '1',
            'live_class_platform_zoom' => '1',
            'live_class_platform_bigbluebutton' => '0',
            'live_class_platform_custom' => '0',
            'live_class_jitsi_base_url' => 'https://meet.jit.si',
        ];

        foreach ($platformDefaults as $key => $value) {
            if (GlobalSettings::where('key', $key)->doesntExist()) {
                GlobalSettings::create(['key' => $key, 'value' => $value]);
            }
        }
    }

    private function defaultPermissionsForRole(int $roleId, string $roleName, array $all): array
    {
        if (in_array($roleId, [1, 2, 14], true) || str_contains($roleName, 'admin') || str_contains($roleName, 'director')) {
            return $all;
        }

        if (in_array($roleId, [10, 12], true) || str_contains($roleName, 'registrar') || str_contains($roleName, 'hod')) {
            return [
                'View Live Classes',
                'Create Live Classes',
                'Edit Live Classes',
                'Cancel Live Classes',
                'Join Live Classes',
            ];
        }

        if (in_array($roleId, [3], true) || str_contains($roleName, 'teacher') || str_contains($roleName, 'lecturer')) {
            return [
                'View Live Classes',
                'Create Live Classes',
                'Edit Live Classes',
                'Cancel Live Classes',
                'Join Live Classes',
            ];
        }

        if ((int) $roleId === 7 || str_contains($roleName, 'student')) {
            return [
                'View Live Classes',
                'Join Live Classes',
            ];
        }

        return [];
    }
}
