<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the global_settings key that the public Apply Now flow uses to
 * resolve "this institution" automatically (no school dropdown, no domain
 * resolution — see App\Support\PublicTenantResolver). Only inserts the key
 * if it's missing; never overwrites a value an admin has already set.
 */
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('global_settings') || ! Schema::hasTable('schools')) {
            return;
        }

        $exists = DB::table('global_settings')->where('key', 'primary_school_id')->exists();

        if ($exists) {
            return;
        }

        $defaultSchoolId = DB::table('schools')->orderBy('id')->value('id');

        DB::table('global_settings')->insert([
            'key' => 'primary_school_id',
            'value' => $defaultSchoolId,
        ]);
    }

    public function down()
    {
        if (! Schema::hasTable('global_settings')) {
            return;
        }

        DB::table('global_settings')->where('key', 'primary_school_id')->delete();
    }
};
