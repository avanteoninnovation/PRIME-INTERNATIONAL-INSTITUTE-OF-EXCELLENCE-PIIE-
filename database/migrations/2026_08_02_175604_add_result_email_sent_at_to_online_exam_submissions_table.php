<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_exam_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('online_exam_submissions', 'result_email_sent_at')) {
                $table->dateTime('result_email_sent_at')->nullable()->after('passed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('online_exam_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('online_exam_submissions', 'result_email_sent_at')) {
                $table->dropColumn('result_email_sent_at');
            }
        });
    }
};
