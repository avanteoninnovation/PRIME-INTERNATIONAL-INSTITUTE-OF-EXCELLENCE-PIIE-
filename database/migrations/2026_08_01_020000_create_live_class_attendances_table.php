<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (live_class, user) who ever clicked Join.
 *
 * `joined_at` is written for every platform the moment the app hands the
 * user off to the meeting — it is the one signal every platform gives us
 * for free. `left_at`/`duration_seconds` are only ever populated for the
 * embedded Jitsi room (see resources/views/admin/live_class/meeting_room.blade.php),
 * which can fire a beacon on page unload; Zoom/Google Meet/BigBlueButton
 * open away from this app entirely, so there is no event to hear a "left"
 * from. The attendance report is expected to show that distinction rather
 * than pretend a duration is known when it isn't.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('live_class_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('live_class_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedTinyInteger('role_id')->nullable();
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamps();

            // A user can leave and rejoin; each join is its own row so the
            // report can show re-entries rather than silently overwrite the
            // first one.
            $table->index(['live_class_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_class_attendances');
    }
};
