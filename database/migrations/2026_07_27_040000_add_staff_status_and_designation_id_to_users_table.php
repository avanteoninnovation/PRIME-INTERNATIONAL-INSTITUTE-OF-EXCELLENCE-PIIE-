<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `staff_status` is the Staff Module's own employment/administrative status
 * (active/suspended/inactive) — deliberately separate from `account_status`,
 * which stays exactly as-is for general portal enable/disable. Suspended
 * and inactive are both treated as portal-blocking (see User::isStaffPortalBlocked()),
 * consistent with how account_status='disable' already works, but the two
 * columns are never conflated so existing account_status behaviour for all
 * other roles is untouched.
 *
 * `designation_id` replaces the free-text `designation` column with a proper
 * FK-style reference into the existing `designations` lookup table (see
 * 2026_07_27_020000_create_designations_table.php). The legacy `designation`
 * string column is left in place (nullable, unused going forward) rather
 * than dropped, since dropping columns on a shared `users` table is
 * unnecessary risk for a column nothing else reads.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'designation_id')) {
                $table->unsignedBigInteger('designation_id')->nullable()->after('designation');
            }
            if (!Schema::hasColumn('users', 'staff_status')) {
                $table->string('staff_status', 20)->nullable()->after('employment_type');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['designation_id', 'staff_status'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
