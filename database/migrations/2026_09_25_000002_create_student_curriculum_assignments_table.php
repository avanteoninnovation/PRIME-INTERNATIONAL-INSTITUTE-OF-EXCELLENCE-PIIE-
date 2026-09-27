<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CURRICULUM_PROGRAMME_INDEX = 'curricula_school_programme_id_unique';

    public function up(): void
    {
        foreach (['users', 'schools', 'programmes', 'curricula', 'academic_years'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Cannot create student Curriculum assignments: required table {$table} is missing.");
            }
        }

        foreach ([
            ['users', 'id'], ['users', 'school_id'], ['schools', 'id'],
            ['programmes', 'school_id'], ['programmes', 'id'],
            ['curricula', 'school_id'], ['curricula', 'programme_id'], ['curricula', 'id'],
            ['academic_years', 'school_id'], ['academic_years', 'id'],
        ] as [$table, $column]) {
            $this->assertColumnType($table, $column, $table === 'users' && $column === 'school_id' ? '/^int(?:\(\d+\))?$/i' : '/^bigint(?:\(\d+\))? unsigned$/i');
        }

        $this->ensureUniqueIndex('curricula', self::CURRICULUM_PROGRAMME_INDEX, ['school_id', 'programme_id', 'id']);

        Schema::create('student_curriculum_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('programme_id');
            $table->unsignedBigInteger('curriculum_id');
            $table->unsignedBigInteger('entry_academic_year_id');
            $table->unsignedBigInteger('effective_from_academic_year_id');
            $table->timestamp('ended_at')->nullable();
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'ended_at'], 'sca_student_current_idx');
            $table->index(['school_id', 'student_id', 'effective_from_academic_year_id'], 'sca_student_history_idx');
            $table->index(['school_id', 'curriculum_id'], 'sca_curriculum_idx');

            $table->foreign('school_id', 'sca_school_fk')->references('id')->on('schools')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('student_id', 'sca_student_fk')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'programme_id'], 'sca_programme_tenant_fk')->references(['school_id', 'id'])->on('programmes')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'programme_id', 'curriculum_id'], 'sca_curriculum_programme_fk')->references(['school_id', 'programme_id', 'id'])->on('curricula')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'entry_academic_year_id'], 'sca_entry_year_tenant_fk')->references(['school_id', 'id'])->on('academic_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'effective_from_academic_year_id'], 'sca_effective_year_tenant_fk')->references(['school_id', 'id'])->on('academic_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('assigned_by', 'sca_assigned_by_fk')->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_curriculum_assignments');
        $this->dropIndexIfExists('curricula', self::CURRICULUM_PROGRAMME_INDEX);
    }

    private function assertColumnType(string $table, string $column, string $pattern): void
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );

        if (! $row || ! preg_match($pattern, $row->column_type)) {
            throw new RuntimeException("Cannot create tenant-safe student Curriculum assignments: {$table}.{$column} has an incompatible type.");
        }
    }

    private function ensureUniqueIndex(string $table, string $name, array $columns): void
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME AS column_name, NON_UNIQUE AS non_unique FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? ORDER BY SEQ_IN_INDEX',
            [$table, $name]
        );
        if ($rows) {
            $actual = array_map(fn ($row) => $row->column_name, $rows);
            if ($actual !== $columns || (int) $rows[0]->non_unique !== 0) {
                throw new RuntimeException("Existing index {$table}.{$name} is incompatible.");
            }
            return;
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $name));
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        $exists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', $table)->where('index_name', $name)->exists();
        if ($exists) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($name));
        }
    }
};
