<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Separate from `password_resets` because that table is keyed on email alone
 * and is shared by the staff/student `users` broker — an applicant and a
 * member of staff can hold the same address, and a reset issued for one must
 * never be redeemable for the other.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('applicant_password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('applicant_password_resets');
    }
};
