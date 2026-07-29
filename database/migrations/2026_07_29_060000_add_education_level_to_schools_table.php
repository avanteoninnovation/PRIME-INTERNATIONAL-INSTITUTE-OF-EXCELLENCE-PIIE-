<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * education_level (primary/secondary/tertiary/vocational/mixed) is
 * descriptive onboarding/reporting metadata, deliberately kept separate
 * from schools.school_type, which is the functional switch actual code
 * branches on (see School::ACADEMIC_STRUCTURE_MAP / academicStructure()).
 * Nothing reads this column yet — it exists so the Super Admin can record
 * it at onboarding time; wiring behavior to it is a future concern.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'education_level')) {
                $table->enum('education_level', ['primary', 'secondary', 'tertiary', 'vocational', 'mixed'])
                    ->nullable()
                    ->after('school_type');
            }
        });
    }

    public function down()
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'education_level')) {
                $table->dropColumn('education_level');
            }
        });
    }
};
