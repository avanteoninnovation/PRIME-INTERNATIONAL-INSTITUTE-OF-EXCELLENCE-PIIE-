<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Application-fee payments, kept separate from `student_fee_managers` and
 * `payment_histories`.
 *
 * Those tables are keyed on `users.id` — an applicant has no user row yet, so
 * reusing them would mean either creating premature student accounts or
 * storing dangling foreign keys. The fee is also conceptually different: it
 * is charged once, before any relationship with the institution exists, and
 * is not part of a student's fee ledger.
 *
 * Offline (bank deposit) payments land here as 'pending' with a proof file
 * and are confirmed by staff; gateway payments are written as 'paid' only
 * after the provider confirms.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('application_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('admission_id')->index();
            $table->unsignedBigInteger('applicant_id')->nullable()->index();

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->nullable();

            // offline | stripe | paypal | razorpay | paytm | waived
            $table->string('method', 30)->default('offline');
            // pending | paid | failed | waived | rejected
            $table->string('status', 20)->default('pending');

            $table->string('reference', 191)->nullable()->index();
            $table->string('gateway_txn_id', 191)->nullable();
            $table->json('gateway_payload')->nullable();

            $table->string('proof_file', 255)->nullable();
            $table->text('note')->nullable();

            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('application_payments');
    }
};
