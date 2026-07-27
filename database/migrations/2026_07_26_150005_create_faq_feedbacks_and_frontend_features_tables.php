<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Global (no school_id), no timestamps: model has $timestamps = false and
        // this content is superseded by the WebsiteSection/WebsiteItem CMS on the
        // live public site — kept for the still-wired superadmin CRUD screen.
        if (!Schema::hasTable('faq')) {
            Schema::create('faq', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
            });
        }

        // School-scoped: staff (admin/teacher) write one-way feedback notes about
        // a student, visible to that student's parent.
        // Table name is singular "feedback" (not "feedbacks") to match Eloquent's
        // actual pluralization of the Feedback model — "feedback" is a mass noun,
        // so Str::plural() leaves it unchanged; confirmed via (new Feedback)->getTable().
        if (!Schema::hasTable('feedback')) {
            Schema::create('feedback', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('feedback_text')->nullable();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('class_id')->nullable();
                $table->unsignedBigInteger('section_id')->nullable();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('session_id')->nullable();
                $table->timestamps();
            });
        }

        // Global (no school_id): legacy public-homepage marketing "feature" cards,
        // also superseded by the newer CMS but still managed from superadmin.
        if (!Schema::hasTable('frontend_features')) {
            Schema::create('frontend_features', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->string('description')->nullable();
                $table->string('icon')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('frontend_features');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('faq');
    }
};
