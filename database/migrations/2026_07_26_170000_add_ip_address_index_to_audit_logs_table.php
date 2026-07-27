<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The ip_address index was added to 2026_07_26_160000_..., but that
 * migration had already run in this environment by the time the index
 * was added, so it never took effect here — this applies it directly
 * instead of duplicating the whole audit_logs context migration.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $exists = collect(Schema::getConnection()->select(
                "SHOW INDEX FROM `audit_logs` WHERE Key_name = ?",
                ['audit_logs_ip_address_index']
            ))->isNotEmpty();

            if (!$exists) {
                $table->index('ip_address', 'audit_logs_ip_address_index');
            }
        });
    }

    public function down()
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_ip_address_index');
        });
    }
};
