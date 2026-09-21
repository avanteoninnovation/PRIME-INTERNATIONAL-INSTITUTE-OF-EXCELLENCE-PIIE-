<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the structured fields the "My Transfers" form needs (Transfer Type,
 * Transfer to Programme, Reason for Transfer, phone, supporting document) —
 * the generic Student Affairs request only ever had subject/details free
 * text, which doesn't fit a transfer application's fixed set of choices.
 * Nullable throughout since complaint/fee-appeal requests never use them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->string('transfer_type', 60)->nullable()->after('type');
            $table->unsignedBigInteger('transfer_to_programme_id')->nullable()->after('transfer_type');
            $table->string('transfer_reason', 60)->nullable()->after('transfer_to_programme_id');
            $table->string('phone_number', 30)->nullable()->after('transfer_reason');
            $table->string('document_path', 255)->nullable()->after('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('student_requests', function (Blueprint $table) {
            $table->dropColumn(['transfer_type', 'transfer_to_programme_id', 'transfer_reason', 'phone_number', 'document_path']);
        });
    }
};
