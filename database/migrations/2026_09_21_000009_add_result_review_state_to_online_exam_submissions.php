<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('online_exam_submissions', 'result_review_state')) {
            Schema::table('online_exam_submissions', function (Blueprint $table) {
                $table->string('result_review_state', 30)->nullable()->after('status');
                $table->index(['school_id', 'result_review_state'], 'oes_school_result_review_idx');
                $table->index(['online_exam_id', 'result_review_state'], 'oes_exam_result_review_idx');
            });
        }

        DB::table('online_exam_submissions')
            ->where('status', 'result_published')
            ->update(['result_review_state' => 'published']);

        DB::table('online_exam_submissions')
            ->where('status', 'finalized')
            ->update(['result_review_state' => 'pending_review']);

        DB::table('online_exam_submissions')
            ->whereNull('result_review_state')
            ->update(['result_review_state' => 'not_ready']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('online_exam_submissions', 'result_review_state')) {
            Schema::table('online_exam_submissions', function (Blueprint $table) {
                $table->dropIndex('oes_school_result_review_idx');
                $table->dropIndex('oes_exam_result_review_idx');
                $table->dropColumn('result_review_state');
            });
        }
    }
};
