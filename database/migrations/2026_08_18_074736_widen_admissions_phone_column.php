<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * admissions.phone was varchar(20), sized for a plain local number. The
 * applicant portal's phone field now stores "<dial code> <number>" (e.g.
 * "+256 700000000") so 20 is too tight for some real combinations
 * (longer dial codes, formatted North American numbers).
 *
 * Raw SQL rather than Schema::table(...)->change() — the latter needs
 * doctrine/dbal, which this project doesn't have installed, and adding a
 * new composer dependency isn't possible on the production host (no
 * SSH/composer access there — see DEPLOYMENT_INSTRUCTIONS.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('admissions', 'phone')) {
            DB::statement('ALTER TABLE `admissions` MODIFY `phone` VARCHAR(30) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('admissions', 'phone')) {
            DB::statement('ALTER TABLE `admissions` MODIFY `phone` VARCHAR(20) NULL');
        }
    }
};
