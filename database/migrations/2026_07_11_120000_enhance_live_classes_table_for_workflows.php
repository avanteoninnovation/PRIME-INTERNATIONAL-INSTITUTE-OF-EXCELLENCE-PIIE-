<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('live_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('live_classes', 'description')) {
                $table->text('description')->nullable()->after('title');
            }

            if (!Schema::hasColumn('live_classes', 'programme_id')) {
                $table->unsignedBigInteger('programme_id')->nullable()->after('class_id');
                $table->index('programme_id');
            }

            if (!Schema::hasColumn('live_classes', 'academic_session_id')) {
                $table->unsignedBigInteger('academic_session_id')->nullable()->after('programme_id');
                $table->index('academic_session_id');
            }

            if (!Schema::hasColumn('live_classes', 'meeting_id')) {
                $table->string('meeting_id', 150)->nullable()->after('meeting_url');
            }

            if (!Schema::hasColumn('live_classes', 'meeting_password')) {
                $table->string('meeting_password', 150)->nullable()->after('meeting_id');
            }

            if (!Schema::hasColumn('live_classes', 'start_date')) {
                $table->date('start_date')->nullable()->after('meeting_password');
            }

            if (!Schema::hasColumn('live_classes', 'start_time')) {
                $table->time('start_time')->nullable()->after('start_date');
            }

            if (!Schema::hasColumn('live_classes', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }

            if (!Schema::hasColumn('live_classes', 'timezone')) {
                $table->string('timezone', 64)->default(config('app.timezone', 'UTC'))->after('end_time');
            }

            if (!Schema::hasColumn('live_classes', 'is_published')) {
                $table->boolean('is_published')->default(false)->after('status');
            }

            if (!Schema::hasColumn('live_classes', 'attendance_enabled')) {
                $table->boolean('attendance_enabled')->default(false)->after('is_published');
            }

            if (!Schema::hasColumn('live_classes', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('attendance_enabled');
                $table->index('created_by');
            }

            if (!Schema::hasColumn('live_classes', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
                $table->index('updated_by');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `live_classes` MODIFY `platform` ENUM('jitsi','google_meet','zoom','bigbluebutton','custom') NOT NULL DEFAULT 'jitsi'");
            DB::statement("ALTER TABLE `live_classes` MODIFY `status` ENUM('draft','scheduled','live','ended','cancelled') NOT NULL DEFAULT 'draft'");
        }

        DB::table('live_classes')
            ->whereNull('created_by')
            ->whereNotNull('teacher_id')
            ->update(['created_by' => DB::raw('teacher_id')]);

        DB::table('live_classes')
            ->whereNull('start_date')
            ->whereNotNull('scheduled_at')
            ->update(['start_date' => DB::raw('DATE(scheduled_at)')]);

        DB::table('live_classes')
            ->whereNull('start_time')
            ->whereNotNull('scheduled_at')
            ->update(['start_time' => DB::raw('TIME(scheduled_at)')]);

        DB::table('live_classes')
            ->whereNull('end_time')
            ->whereNotNull('ends_at')
            ->update(['end_time' => DB::raw('TIME(ends_at)')]);

        DB::table('live_classes')
            ->whereNull('is_published')
            ->update(['is_published' => 0]);

        DB::table('live_classes')
            ->where('status', 'scheduled')
            ->where('is_published', 0)
            ->update(['status' => 'draft']);
    }

    public function down()
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `live_classes` MODIFY `platform` ENUM('jitsi','zoom','google_meet','teams','other') NOT NULL DEFAULT 'jitsi'");
            DB::statement("ALTER TABLE `live_classes` MODIFY `status` ENUM('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled'");
        }

        Schema::table('live_classes', function (Blueprint $table) {
            if (Schema::hasColumn('live_classes', 'updated_by')) {
                $table->dropIndex(['updated_by']);
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('live_classes', 'created_by')) {
                $table->dropIndex(['created_by']);
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('live_classes', 'attendance_enabled')) {
                $table->dropColumn('attendance_enabled');
            }

            if (Schema::hasColumn('live_classes', 'is_published')) {
                $table->dropColumn('is_published');
            }

            if (Schema::hasColumn('live_classes', 'timezone')) {
                $table->dropColumn('timezone');
            }

            if (Schema::hasColumn('live_classes', 'end_time')) {
                $table->dropColumn('end_time');
            }

            if (Schema::hasColumn('live_classes', 'start_time')) {
                $table->dropColumn('start_time');
            }

            if (Schema::hasColumn('live_classes', 'start_date')) {
                $table->dropColumn('start_date');
            }

            if (Schema::hasColumn('live_classes', 'meeting_password')) {
                $table->dropColumn('meeting_password');
            }

            if (Schema::hasColumn('live_classes', 'meeting_id')) {
                $table->dropColumn('meeting_id');
            }

            if (Schema::hasColumn('live_classes', 'academic_session_id')) {
                $table->dropIndex(['academic_session_id']);
                $table->dropColumn('academic_session_id');
            }

            if (Schema::hasColumn('live_classes', 'programme_id')) {
                $table->dropIndex(['programme_id']);
                $table->dropColumn('programme_id');
            }

            if (Schema::hasColumn('live_classes', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
