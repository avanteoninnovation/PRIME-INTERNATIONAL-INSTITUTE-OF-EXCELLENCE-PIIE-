<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the existing `departments` table exactly (see
 * 2022_05_26_061601_create_departments_table.php) — a configurable,
 * school-scoped lookup list rather than a hardcoded dropdown, so new
 * designations can be added later without a code change. Seeds a starter
 * set per existing school so the Staff forms have something to select from
 * immediately.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('school_id');
            $table->timestamps();
        });

        $defaults = [
            'Director', 'Registrar', 'Lecturer', 'IT Officer', 'Accountant',
            'Administrator', 'Human Resource Officer', 'Librarian', 'Warden',
        ];

        $schoolIds = DB::table('schools')->pluck('id');
        $now = now();

        foreach ($schoolIds as $schoolId) {
            foreach ($defaults as $name) {
                DB::table('designations')->insert([
                    'name' => $name,
                    'school_id' => $schoolId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('designations');
    }
};
