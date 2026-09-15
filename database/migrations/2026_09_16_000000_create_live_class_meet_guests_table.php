<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Google Meet events created by this app (LiveClassController::
 * createGoogleMeetUrl()) never invited anyone — the calendar event had zero
 * attendees, so every joiner, including the teacher running the class, was
 * an anonymous link-holder to Google and had to "Ask to join" regardless of
 * which Google account they were signed into.
 *
 * This table is a school-wide, admin-managed list of email addresses that
 * get added as attendees on every Google Meet event this school creates —
 * so a teacher signed into one of these addresses is a recognised, named
 * guest on the invite (and gets an emailed calendar invite via
 * ?sendUpdates=all), rather than an anonymous stranger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_class_meet_guests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('email', 191);
            $table->string('label', 191)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_class_meet_guests');
    }
};
