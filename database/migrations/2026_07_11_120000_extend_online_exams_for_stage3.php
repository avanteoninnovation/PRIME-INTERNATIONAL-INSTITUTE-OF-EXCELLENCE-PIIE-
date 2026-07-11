<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('online_exams')) {
            return;
        }

        Schema::table('online_exams', function (Blueprint $table) {
            if (!Schema::hasColumn('online_exams', 'workflow_state')) {
                $table->string('workflow_state', 30)->default('draft')->after('instructions')->index();
            }

            if (!Schema::hasColumn('online_exams', 'max_attempts')) {
                $table->unsignedTinyInteger('max_attempts')->default(1)->after('pass_mark');
            }

            if (!Schema::hasColumn('online_exams', 'shuffle_questions')) {
                $table->boolean('shuffle_questions')->default(false)->after('max_attempts');
            }

            if (!Schema::hasColumn('online_exams', 'shuffle_options')) {
                $table->boolean('shuffle_options')->default(false)->after('shuffle_questions');
            }

            if (!Schema::hasColumn('online_exams', 'allow_previous_navigation')) {
                $table->boolean('allow_previous_navigation')->default(true)->after('shuffle_options');
            }

            if (!Schema::hasColumn('online_exams', 'result_release_policy')) {
                $table->string('result_release_policy', 30)->default('immediate')->after('allow_previous_navigation');
            }

            if (!Schema::hasColumn('online_exams', 'webcam_required')) {
                $table->boolean('webcam_required')->default(false)->after('result_release_policy');
            }

            if (!Schema::hasColumn('online_exams', 'fullscreen_required')) {
                $table->boolean('fullscreen_required')->default(false)->after('webcam_required');
            }

            if (!Schema::hasColumn('online_exams', 'creator_id')) {
                $table->unsignedBigInteger('creator_id')->nullable()->after('created_by')->index();
            }

            if (!Schema::hasColumn('online_exams', 'updater_id')) {
                $table->unsignedBigInteger('updater_id')->nullable()->after('creator_id')->index();
            }

            if (!Schema::hasColumn('online_exams', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('updater_id')->index();
            }

            if (!Schema::hasColumn('online_exams', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }

            if (!Schema::hasColumn('online_exams', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('reviewed_at');
            }

            if (!Schema::hasColumn('online_exams', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }

            if (!Schema::hasColumn('online_exams', 'locked_at')) {
                $table->timestamp('locked_at')->nullable()->after('cancellation_reason');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('online_exams')) {
            return;
        }

        Schema::table('online_exams', function (Blueprint $table) {
            $columns = [
                'workflow_state',
                'max_attempts',
                'shuffle_questions',
                'shuffle_options',
                'allow_previous_navigation',
                'result_release_policy',
                'webcam_required',
                'fullscreen_required',
                'creator_id',
                'updater_id',
                'reviewed_by',
                'reviewed_at',
                'cancelled_at',
                'cancellation_reason',
                'locked_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('online_exams', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
