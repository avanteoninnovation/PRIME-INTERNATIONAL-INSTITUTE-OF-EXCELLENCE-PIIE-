<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hostels')) {
            Schema::create('hostels', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('name')->nullable();
                $table->string('type')->nullable();
                $table->string('address')->nullable();
                $table->unsignedBigInteger('warden_id')->nullable();
                $table->decimal('fee', 10, 2)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hostel_rooms')) {
            Schema::create('hostel_rooms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('hostel_id')->nullable()->index();
                $table->string('room_no')->nullable();
                $table->integer('capacity')->nullable();
                $table->integer('occupied')->default(0);
                $table->integer('seat_fee')->nullable();
                $table->longText('description')->nullable();
                $table->string('status')->default('1');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hostel_room_allocations')) {
            Schema::create('hostel_room_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->string('allocated_on')->nullable();
                $table->string('vacated_on')->nullable();
                $table->string('status')->default('1');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hostel_applications')) {
            Schema::create('hostel_applications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('hostel_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable()->index();
                $table->string('status')->default('0');
                $table->longText('note')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hostel_fees')) {
            Schema::create('hostel_fees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->unsignedBigInteger('hostel_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('room_id')->nullable();
                $table->string('title')->nullable();
                $table->string('document_image')->nullable();
                $table->decimal('amount', 10, 2)->nullable();
                $table->decimal('paid_amount', 10, 2)->nullable();
                $table->string('payment_method')->nullable();
                $table->date('fee_payment_date')->nullable();
                $table->dateTime('payment_date')->nullable();
                $table->date('due_date')->nullable();
                // Stored as a string, not int: existing code writes both numeric
                // codes (0/1/2) and literal strings ('paid'/'unpaid') into this column.
                $table->string('status', 20)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('hostel_fees');
        Schema::dropIfExists('hostel_applications');
        Schema::dropIfExists('hostel_room_allocations');
        Schema::dropIfExists('hostel_rooms');
        Schema::dropIfExists('hostels');
    }
};
