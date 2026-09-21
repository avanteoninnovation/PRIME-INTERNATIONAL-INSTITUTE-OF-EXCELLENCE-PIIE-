<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('online_exams')) {
            return;
        }

        Schema::table('online_exams', function (Blueprint $table) {
            if (! Schema::hasColumn('online_exams', 'programme_id')) {
                $table->unsignedBigInteger('programme_id')->nullable()->index()->after('class_id');
            }

            if (! Schema::hasColumn('online_exams', 'session_id')) {
                $table->unsignedBigInteger('session_id')->nullable()->index()->after('programme_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('online_exams')) {
            return;
        }

        Schema::table('online_exams', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('online_exams', 'session_id')) {
                $columns[] = 'session_id';
            }
            if (Schema::hasColumn('online_exams', 'programme_id')) {
                $columns[] = 'programme_id';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
};
