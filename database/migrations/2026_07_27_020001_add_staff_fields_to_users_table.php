<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the remaining Staff Module fields not already on `users`
 * (department_id/designation already exist — see
 * 2026_07_26_130000_add_department_id_and_designation_to_users_table.php).
 *
 * first_name/last_name are additive: `name` stays authoritative and stays
 * in sync (set from first+last on save) so the hundreds of existing
 * `->name` reads across the app (emails, audit labels, exports, id cards)
 * are completely unaffected.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'employment_type')) {
                $table->string('employment_type', 30)->nullable()->after('designation');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['first_name', 'last_name', 'employment_type'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
