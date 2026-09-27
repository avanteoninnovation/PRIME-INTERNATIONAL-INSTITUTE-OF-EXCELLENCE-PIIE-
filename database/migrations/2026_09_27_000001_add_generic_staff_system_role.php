<?php

use App\Support\Roles\SystemRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('roles')) {
            return;
        }

        $existing = DB::table('roles')->where('role_id', SystemRole::GENERIC_STAFF)->first();
        if ($existing && strtolower((string) $existing->name) !== 'staff') {
            throw new RuntimeException('Role ID 20 is occupied; Generic Staff role was not installed.');
        }
        if (!$existing) {
            DB::table('roles')->insert([
                'role_id' => SystemRole::GENERIC_STAFF,
                'name' => 'staff',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        // Additive identity is retained on rollback to avoid orphaning accounts.
    }
};
