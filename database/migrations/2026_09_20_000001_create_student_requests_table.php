<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A single, reusable "student affairs" request-and-review workflow —
 * transfer applications, complaints, and fee-discount appeals are all the
 * same shape underneath (a student submits something, admin reviews and
 * responds), so this is one table with a `type` discriminator rather than
 * three near-identical ones. Nothing like this existed before: a student
 * had no formal channel to request anything and get a tracked response.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('type', 40); // transfer | complaint | fee_discount_appeal
            $table->string('subject', 191);
            $table->text('details');
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('admin_response')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_requests');
    }
};
