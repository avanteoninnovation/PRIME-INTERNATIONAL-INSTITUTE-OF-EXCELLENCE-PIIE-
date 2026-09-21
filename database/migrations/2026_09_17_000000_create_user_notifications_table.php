<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A real, per-user notification inbox. Before this, "notifications" in this
 * app meant two separate things, neither of which a user could actually
 * review: the school-wide Noticeboard (no per-user read state — everyone
 * sees the same feed forever) and email (silently skipped whenever SMTP
 * isn't configured, with nothing else to fall back on — see
 * LiveClassNotifier/OnlineExamAnnouncementNotifier's own docblocks). This
 * table is what the new notification bell (any role's navigation layout)
 * and "My Notifications" page actually read from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('type', 40)->default('general');
            $table->string('title', 191);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
