<?php

namespace Tests\Feature\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends AdmissionsTestHelper's in-memory schema with the Staff Module's
 * own additions (users.first_name/last_name/employment_type/designation_id/
 * staff_status, plus the departments/designations lookup tables) so Staff
 * Module tests don't need to duplicate the whole base schema (users/schools/
 * classes/message_thrades/etc. that admin/navigation.blade.php and the
 * shared admin views already depend on).
 */
trait StaffModuleTestHelper
{
    use AdmissionsTestHelper;

    protected function bootStaffModuleTestSchema(): void
    {
        $this->bootAdmissionsTestSchema();

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('employment_type', 30)->nullable();
            $table->unsignedBigInteger('designation_id')->nullable();
            $table->string('staff_status', 20)->nullable();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('school_id');
            $table->timestamps();
        });

        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('school_id');
            $table->timestamps();
        });
    }

    protected function makeDepartment(int $schoolId, string $name = 'ICT'): int
    {
        return (int) DB::table('departments')->insertGetId([
            'name' => $name, 'school_id' => $schoolId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function makeDesignation(int $schoolId, string $name = 'Lecturer'): int
    {
        return (int) DB::table('designations')->insertGetId([
            'name' => $name, 'school_id' => $schoolId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
