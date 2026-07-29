<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * StudentFeeInvoiceGenerator (HEI-only) has always had to write class_id=0
 * as a documented sentinel for "not class-based," since student_fee_managers
 * had no programme_id column — meaning HEI invoices were invisible to any
 * programme-scoped view (only reachable via an unfiltered/by-student
 * lookup). This adds the missing column so a "by programme" admin view can
 * actually find them. Existing rows are untouched; the class_id=0 sentinel
 * stays as-is for backward compatibility with anything already reading it.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('student_fee_managers', function (Blueprint $table) {
            if (! Schema::hasColumn('student_fee_managers', 'programme_id')) {
                $table->unsignedBigInteger('programme_id')->nullable()->after('class_id');
            }
        });
    }

    public function down()
    {
        Schema::table('student_fee_managers', function (Blueprint $table) {
            if (Schema::hasColumn('student_fee_managers', 'programme_id')) {
                $table->dropColumn('programme_id');
            }
        });
    }
};
