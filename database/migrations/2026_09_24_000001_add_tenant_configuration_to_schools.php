<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            foreach ([
                'primary_locale' => fn () => $table->string('primary_locale', 12)->nullable(),
                'country_code' => fn () => $table->char('country_code', 2)->nullable(),
                'timezone' => fn () => $table->string('timezone', 64)->nullable(),
                'academic_calendar_pattern' => fn () => $table->string('academic_calendar_pattern', 32)->nullable(),
                'terminology_overrides' => fn () => $table->json('terminology_overrides')->nullable(),
            ] as $column => $addColumn) {
                if (! Schema::hasColumn('schools', $column)) {
                    $addColumn();
                }
            }
        });
    }

    public function down(): void
    {
        // Tenant configuration is compatibility metadata; retain it on rollback.
    }
};
