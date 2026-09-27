<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['schools', 'programmes', 'intake_sessions', 'academic_years', 'curricula', 'users'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Cannot create Programme Cohorts: required table {$table} is missing.");
            }
        }

        $this->assertUniqueIndex('intake_sessions', ['school_id', 'id']);
        $this->assertUniqueIndex('programmes', ['school_id', 'id']);
        $this->assertUniqueIndex('academic_years', ['school_id', 'id']);
        $this->assertUniqueIndex('curricula', ['school_id', 'programme_id', 'id']);

        Schema::create('programme_cohorts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('programme_id');
            $table->unsignedBigInteger('intake_session_id');
            $table->unsignedBigInteger('entry_academic_year_id');
            $table->unsignedBigInteger('curriculum_id');
            $table->string('name', 150);
            $table->string('code', 60);
            $table->string('status', 20)->default('draft');
            $table->date('expected_completion_date')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->unique(['school_id', 'id'], 'pc_school_id_id_uq');
            $table->unique(['school_id', 'code'], 'pc_school_code_uq');
            $table->index(['school_id', 'programme_id', 'status'], 'pc_programme_status_idx');
            $table->index(['school_id', 'intake_session_id', 'entry_academic_year_id'], 'pc_intake_entry_year_idx');
            $table->index(['school_id', 'entry_academic_year_id', 'status'], 'pc_entry_year_status_idx');
            $table->index(['school_id', 'programme_id', 'curriculum_id'], 'pc_curriculum_lookup_idx');

            $table->foreign('school_id', 'pc_school_fk')
                ->references('id')->on('schools')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'programme_id'], 'pc_programme_tenant_fk')
                ->references(['school_id', 'id'])->on('programmes')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'intake_session_id'], 'pc_intake_tenant_fk')
                ->references(['school_id', 'id'])->on('intake_sessions')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'entry_academic_year_id'], 'pc_entry_year_tenant_fk')
                ->references(['school_id', 'id'])->on('academic_years')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign(['school_id', 'programme_id', 'curriculum_id'], 'pc_curriculum_programme_fk')
                ->references(['school_id', 'programme_id', 'id'])->on('curricula')->onDelete('restrict')->onUpdate('restrict');
            $table->foreign('created_by', 'pc_creator_fk')
                ->references('id')->on('users')->onDelete('restrict')->onUpdate('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_cohorts');
    }

    private function assertUniqueIndex(string $table, array $columns): void
    {
        $rows = DB::select(
            'SELECT INDEX_NAME AS index_name, COLUMN_NAME AS column_name, NON_UNIQUE AS non_unique '
            .'FROM information_schema.statistics '
            .'WHERE table_schema = DATABASE() AND table_name = ? '
            .'ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [$table]
        );

        $indexes = [];
        foreach ($rows as $row) {
            $indexes[$row->index_name]['columns'][] = $row->column_name;
            $indexes[$row->index_name]['non_unique'] = (int) $row->non_unique;
        }

        foreach ($indexes as $index) {
            if ($index['columns'] === $columns && $index['non_unique'] === 0) {
                return;
            }
        }

        throw new RuntimeException("Cannot create Programme Cohorts: {$table} has no unique index for (".implode(',', $columns).').');
    }
};
