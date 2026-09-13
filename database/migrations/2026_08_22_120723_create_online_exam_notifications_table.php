<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dedupe ledger for the 24h/1h "exam starting soon" reminder job
 * (App\Console\Commands\SendOnlineExamStartReminders), same pattern as
 * live_class_notifications for App\Console\Commands\SendLiveClassReminders.
 *
 * The reminder command runs every few minutes; without a record of what has
 * already gone out, an exam sitting inside the "1 hour before" window across
 * several runs would email every student again on every run. One row per
 * (online_exam, type) marks that reminder as sent, checked before send —
 * never after, so a crash mid-send can at worst under-notify, not spam.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_exam_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('online_exam_id')->index();
            $table->string('type', 30); // reminder_24h | reminder_1h
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['online_exam_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_exam_notifications');
    }
};
