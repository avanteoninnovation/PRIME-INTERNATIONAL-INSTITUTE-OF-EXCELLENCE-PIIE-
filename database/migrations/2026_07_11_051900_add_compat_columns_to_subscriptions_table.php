<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('subscriptions', 'status')) {
                $table->string('status', 10)->default('1')->after('active');
            }

            if (!Schema::hasColumn('subscriptions', 'studentLimit')) {
                $table->string('studentLimit')->nullable()->after('expire_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('subscriptions')) {
            return;
        }

        Schema::table('subscriptions', function (Blueprint $table) {
            if (Schema::hasColumn('subscriptions', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('subscriptions', 'studentLimit')) {
                $table->dropColumn('studentLimit');
            }
        });
    }
};
