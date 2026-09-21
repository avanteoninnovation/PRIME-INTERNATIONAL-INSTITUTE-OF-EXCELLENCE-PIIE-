<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('online_exam_user_notifications')) {
            return;
        }

        Schema::create('online_exam_user_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index('oeun_school_idx');
            $table->unsignedBigInteger('user_id')->index('oeun_user_idx');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('online_exam_id')->nullable()->index('oeun_exam_idx');
            $table->unsignedBigInteger('submission_id')->nullable()->index('oeun_submission_idx');
            $table->string('type', 60);
            $table->string('title', 190);
            $table->text('message');
            $table->string('action_url', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('event_key', 190)->nullable();
            $table->timestamps();

            $table->index(['school_id', 'user_id', 'read_at'], 'oeun_unread_idx');
            $table->index(['school_id', 'created_at'], 'oeun_date_idx');
            // event_key is nullable so non-idempotent messages remain valid;
            // MariaDB permits multiple NULLs, while populated keys are unique
            // per recipient and school.
            $table->unique(['school_id', 'user_id', 'event_key'], 'oeun_recipient_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_exam_user_notifications');
    }
};
