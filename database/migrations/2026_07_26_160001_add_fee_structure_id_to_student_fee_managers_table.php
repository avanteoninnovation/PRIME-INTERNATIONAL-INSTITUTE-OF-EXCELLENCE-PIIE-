<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a stable link back to the fee_structures row an invoice was
 * generated from, so automatic invoice generation (see
 * App\Support\StudentFeeInvoiceGenerator) can check "has this student
 * already been billed for this fee structure this session?" without
 * fragile string-matching on title, preventing duplicate invoices if
 * student/admission creation is retried. Nullable — existing
 * manually-created invoices have no fee_structure_id and keep working.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('student_fee_managers', function (Blueprint $table) {
            if (! Schema::hasColumn('student_fee_managers', 'fee_structure_id')) {
                $table->unsignedBigInteger('fee_structure_id')->nullable()->after('student_id')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('student_fee_managers', function (Blueprint $table) {
            if (Schema::hasColumn('student_fee_managers', 'fee_structure_id')) {
                $table->dropColumn('fee_structure_id');
            }
        });
    }
};
