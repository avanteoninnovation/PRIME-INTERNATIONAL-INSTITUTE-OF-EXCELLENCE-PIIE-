<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguishes applications submitted through the public /apply form
 * (PublicApplicationController, source=public) from ones a School Admin or
 * Admissions Staff typed in directly (AdmissionsController::store,
 * source=staff_entry). Defaults to staff_entry so every pre-existing row —
 * all of which were staff-entered, since the public form didn't exist before
 * this feature — is safely backfilled by the column default with no data
 * migration needed.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('admissions', function (Blueprint $table) {
            if (! Schema::hasColumn('admissions', 'source')) {
                $table->enum('source', ['public', 'staff_entry'])->default('staff_entry')->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('admissions', function (Blueprint $table) {
            if (Schema::hasColumn('admissions', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
