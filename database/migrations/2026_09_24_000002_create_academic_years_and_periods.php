<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_years')) Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('label', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('planned');
            $table->timestamps();
            $table->unique(['school_id', 'id']);
            $table->index(['school_id', 'status']);
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('restrict');
        });

        if (! Schema::hasTable('academic_periods')) Schema::create('academic_periods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('academic_year_id');
            $table->string('type', 32);
            $table->string('label', 100);
            $table->unsignedSmallInteger('sequence');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('planned');
            $table->timestamps();
            $table->unique(['school_id', 'id']);
            $table->unique(['school_id', 'academic_year_id', 'id']);
            $table->unique(['academic_year_id', 'sequence']);
            $table->index(['school_id', 'academic_year_id', 'type']);
            $table->foreign(['school_id', 'academic_year_id'])->references(['school_id', 'id'])->on('academic_years')->onDelete('restrict');
        });

        Schema::table('schools', function (Blueprint $table) {
            if (! Schema::hasColumn('schools', 'current_academic_year_id')) $table->unsignedBigInteger('current_academic_year_id')->nullable()->after('academic_calendar_pattern');
            if (! Schema::hasColumn('schools', 'current_academic_period_id')) $table->unsignedBigInteger('current_academic_period_id')->nullable()->after('current_academic_year_id');
            if (! self::constraintExists('schools', 'schools_id_current_academic_year_id_index')) $table->index(['id', 'current_academic_year_id']);
            if (! self::constraintExists('schools', 'schools_id_current_academic_year_id_foreign')) $table->foreign(['id', 'current_academic_year_id'])->references(['school_id', 'id'])->on('academic_years')->onDelete('restrict');
            if (! self::constraintExists('schools', 'schools_current_academic_period_tenant_fk')) $table->foreign(['id', 'current_academic_year_id', 'current_academic_period_id'], 'schools_current_academic_period_tenant_fk')->references(['school_id', 'academic_year_id', 'id'])->on('academic_periods')->onDelete('restrict');
        });

        Schema::table('sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('sessions', 'academic_year_id')) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->index();
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['id', 'current_academic_year_id']);
            $table->dropForeign('schools_current_academic_period_tenant_fk');
            $table->dropIndex(['id', 'current_academic_year_id']);
            $table->dropColumn(['current_academic_year_id', 'current_academic_period_id']);
        });
        Schema::dropIfExists('academic_periods');
        Schema::dropIfExists('academic_years');
    }

    private static function constraintExists(string $table, string $name): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $name)
            ->exists()
            || DB::table('information_schema.statistics')
                ->where('table_schema', DB::connection()->getDatabaseName())
                ->where('table_name', $table)
                ->where('index_name', $name)
                ->exists();
    }
};
