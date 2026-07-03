<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payroll', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('staff_id')->index();
            $table->date('pay_period');
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('deductions', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('nssf', 15, 2)->default(0);
            $table->decimal('net_pay', 15, 2)->default(0);
            $table->enum('payment_method', ['bank','cash','mobile_money'])->default('bank');
            $table->string('bank_account', 50)->nullable();
            $table->enum('status', ['draft','approved','paid'])->default('draft');
            $table->dateTime('paid_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });

        Schema::create('salary_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->decimal('basic', 15, 2)->default(0);
            $table->decimal('housing', 15, 2)->default(0);
            $table->decimal('transport', 15, 2)->default(0);
            $table->decimal('medical', 15, 2)->default(0);
            $table->date('effective_from')->nullable();
            $table->timestamps();
        });

        Schema::create('graduation_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->decimal('cgpa', 4, 2)->nullable();
            $table->string('classification', 50)->nullable();
            $table->tinyInteger('fees_cleared')->default(0);
            $table->tinyInteger('academics_cleared')->default(0);
            $table->year('ceremony_year')->nullable();
            $table->enum('status', ['pending','approved','graduated','deferred'])->default('pending');
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('graduation_applications');
        Schema::dropIfExists('salary_structures');
        Schema::dropIfExists('payroll');
    }
};
