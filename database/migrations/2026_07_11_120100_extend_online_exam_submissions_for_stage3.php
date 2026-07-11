<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('online_exam_submissions')) {
            return;
        }

        Schema::table('online_exam_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('online_exam_submissions', 'attempt_no')) {
                $table->unsignedInteger('attempt_no')->default(1)->after('student_id');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'expires_at')) {
                $table->dateTime('expires_at')->nullable()->after('started_at');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'last_activity_at')) {
                $table->dateTime('last_activity_at')->nullable()->after('expires_at');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'total_marks_snapshot')) {
                $table->integer('total_marks_snapshot')->nullable()->after('score');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'objective_score')) {
                $table->decimal('objective_score', 8, 2)->nullable()->after('total_marks_snapshot');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'manual_score')) {
                $table->decimal('manual_score', 8, 2)->nullable()->after('objective_score');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'passed')) {
                $table->boolean('passed')->nullable()->after('manual_score');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'submitted_via')) {
                $table->string('submitted_via', 20)->nullable()->after('submitted_at');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'timeout_at')) {
                $table->dateTime('timeout_at')->nullable()->after('submitted_via');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'camera_consent_at')) {
                $table->dateTime('camera_consent_at')->nullable()->after('timeout_at');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'camera_permission_granted')) {
                $table->boolean('camera_permission_granted')->default(false)->after('camera_consent_at');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'camera_ready_at')) {
                $table->dateTime('camera_ready_at')->nullable()->after('camera_permission_granted');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'fullscreen_started_at')) {
                $table->dateTime('fullscreen_started_at')->nullable()->after('camera_ready_at');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'browser_session_token')) {
                $table->string('browser_session_token', 80)->nullable()->after('fullscreen_started_at')->index();
            }

            if (!Schema::hasColumn('online_exam_submissions', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('browser_session_token');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('user_agent');
            }

            if (!Schema::hasColumn('online_exam_submissions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        if (Schema::hasColumn('online_exam_submissions', 'status')) {
            DB::statement("ALTER TABLE `online_exam_submissions` MODIFY `status` VARCHAR(40) NOT NULL DEFAULT 'in_progress'");
        }

        Schema::table('online_exam_submissions', function (Blueprint $table) {
            $table->index(['online_exam_id', 'student_id', 'attempt_no'], 'oes_exam_student_attempt_idx');
            $table->index(['online_exam_id', 'student_id', 'status'], 'oes_exam_student_status_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('online_exam_submissions')) {
            return;
        }

        Schema::table('online_exam_submissions', function (Blueprint $table) {
            $indexes = [
                'oes_exam_student_attempt_idx',
                'oes_exam_student_status_idx',
            ];

            foreach ($indexes as $index) {
                try {
                    $table->dropIndex($index);
                } catch (\Throwable $e) {
                    // Ignore missing index in rollback safety mode.
                }
            }
        });

        if (Schema::hasColumn('online_exam_submissions', 'status')) {
            DB::statement("UPDATE `online_exam_submissions` SET `status` = 'submitted' WHERE `status` NOT IN ('in_progress','submitted','graded')");
            DB::statement("ALTER TABLE `online_exam_submissions` MODIFY `status` ENUM('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress'");
        }

        Schema::table('online_exam_submissions', function (Blueprint $table) {
            $columns = [
                'attempt_no',
                'expires_at',
                'last_activity_at',
                'total_marks_snapshot',
                'objective_score',
                'manual_score',
                'passed',
                'submitted_via',
                'timeout_at',
                'camera_consent_at',
                'camera_permission_granted',
                'camera_ready_at',
                'fullscreen_started_at',
                'browser_session_token',
                'ip_address',
                'user_agent',
                'created_at',
                'updated_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('online_exam_submissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
