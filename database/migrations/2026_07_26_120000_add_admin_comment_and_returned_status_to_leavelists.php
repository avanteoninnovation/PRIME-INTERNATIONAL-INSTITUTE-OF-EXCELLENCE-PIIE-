<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('leavelists', 'admin_comment')) {
            Schema::table('leavelists', function (Blueprint $table) {
                $table->text('admin_comment')->nullable()->after('approved_by');
            });
        }

        if (Schema::hasColumn('leavelists', 'status') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE leavelists MODIFY status ENUM('pending','approved','returned','rejected') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down()
    {
        if (Schema::hasColumn('leavelists', 'admin_comment')) {
            Schema::table('leavelists', function (Blueprint $table) {
                $table->dropColumn('admin_comment');
            });
        }

        if (Schema::hasColumn('leavelists', 'status') && DB::connection()->getDriverName() === 'mysql') {
            DB::statement("UPDATE leavelists SET status = 'rejected' WHERE status = 'returned'");
            DB::statement("ALTER TABLE leavelists MODIFY status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
        }
    }
};
