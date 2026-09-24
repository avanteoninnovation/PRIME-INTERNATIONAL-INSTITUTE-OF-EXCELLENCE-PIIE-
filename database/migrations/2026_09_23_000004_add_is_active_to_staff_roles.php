<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RBAC Phase 3B — custom staff roles can be deactivated instead of deleted.
 *
 * A deactivated role stays visible (with its history and assignments) but
 * grants nothing and cannot be newly assigned; reactivating it restores the
 * existing assignments. Additive only: every existing role stays active.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('staff_roles') && !Schema::hasColumn('staff_roles', 'is_active')) {
            Schema::table('staff_roles', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('description');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('staff_roles') || !Schema::hasColumn('staff_roles', 'is_active')) {
            return;
        }

        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            // Laravel 9 needs doctrine/dbal to drop columns on SQLite; SQLite >= 3.35 can do it natively.
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE staff_roles DROP COLUMN is_active');
        } else {
            Schema::table('staff_roles', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
