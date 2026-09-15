<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MarzPay collections are asynchronous (push a USSD prompt, wait for a
 * webhook) so, unlike the old offline flow, a fee row needs to remember
 * which gateway reference it's waiting on and the last payload received,
 * so the webhook handler can find the right row and so a "check status
 * now" fallback has something to poll.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('student_fee_managers', function (Blueprint $table) {
            if (! Schema::hasColumn('student_fee_managers', 'gateway_reference')) {
                $table->string('gateway_reference', 191)->nullable()->index()->after('payment_method');
            }
            if (! Schema::hasColumn('student_fee_managers', 'gateway_payload')) {
                $table->json('gateway_payload')->nullable()->after('gateway_reference');
            }
        });

        Schema::table('hostel_fees', function (Blueprint $table) {
            if (! Schema::hasColumn('hostel_fees', 'gateway_reference')) {
                $table->string('gateway_reference', 191)->nullable()->index()->after('payment_method');
            }
            if (! Schema::hasColumn('hostel_fees', 'gateway_payload')) {
                $table->json('gateway_payload')->nullable()->after('gateway_reference');
            }
        });
    }

    public function down()
    {
        Schema::table('student_fee_managers', function (Blueprint $table) {
            foreach (['gateway_reference', 'gateway_payload'] as $column) {
                if (Schema::hasColumn('student_fee_managers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('hostel_fees', function (Blueprint $table) {
            foreach (['gateway_reference', 'gateway_payload'] as $column) {
                if (Schema::hasColumn('hostel_fees', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
