<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MEMBERSHIP_SUBJECT_INDEX = 'curriculum_memberships_offering_subject_unique';

    public function up(): void
    {
        foreach ([
            ['subjects', 'school_id'], ['subjects', 'id'],
            ['academic_years', 'school_id'], ['academic_years', 'id'],
            ['academic_periods', 'school_id'], ['academic_periods', 'academic_year_id'], ['academic_periods', 'id'],
            ['curricula', 'school_id'], ['curricula', 'id'],
            ['curriculum_memberships', 'school_id'], ['curriculum_memberships', 'curriculum_id'], ['curriculum_memberships', 'id'], ['curriculum_memberships', 'subject_id'],
            ['schools', 'id'],
        ] as [$table, $column]) {
            $this->assertUnsignedBigint($table, $column);
        }

        $this->ensureUniqueIndex('curriculum_memberships', self::MEMBERSHIP_SUBJECT_INDEX, ['school_id', 'curriculum_id', 'id', 'subject_id']);

        if (! Schema::hasTable('course_offerings')) {
            Schema::create('course_offerings', function (Blueprint $table): void {
                $table->unsignedBigInteger('id')->autoIncrement();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('academic_year_id');
                $table->unsignedBigInteger('academic_period_id');
                $table->string('reference', 50)->nullable();
                $table->string('status', 20)->default('draft');
                $table->timestamps();
            });
        }
        $this->assertColumns('course_offerings', ['id', 'school_id', 'subject_id', 'academic_year_id', 'academic_period_id', 'reference', 'status', 'created_at', 'updated_at']);
        foreach (['id', 'school_id', 'subject_id', 'academic_year_id', 'academic_period_id'] as $column) {
            $this->assertUnsignedBigint('course_offerings', $column);
        }
        $this->assertVarchar('course_offerings', 'reference', 50, true);
        $this->assertVarchar('course_offerings', 'status', 20, false);
        $this->assertIndex('course_offerings', 'PRIMARY', ['id'], true);
        $this->ensureUniqueIndex('course_offerings', 'course_offerings_school_id_id_unique', ['school_id', 'id']);
        $this->ensureUniqueIndex('course_offerings', 'course_offerings_school_reference_unique', ['school_id', 'reference']);
        $this->ensureUniqueIndex('course_offerings', 'course_offerings_school_id_subject_unique', ['school_id', 'id', 'subject_id']);
        $this->ensureIndex('course_offerings', 'course_offerings_subject_period_idx', ['school_id', 'subject_id', 'academic_year_id', 'academic_period_id']);
        $this->ensureIndex('course_offerings', 'course_offerings_period_status_idx', ['school_id', 'academic_year_id', 'academic_period_id', 'status']);
        $this->ensureForeignKey('course_offerings_school_fk', 'course_offerings', ['school_id'], 'schools', ['id']);
        $this->ensureForeignKey('course_offerings_subject_tenant_fk', 'course_offerings', ['school_id', 'subject_id'], 'subjects', ['school_id', 'id']);
        $this->ensureForeignKey('course_offerings_year_tenant_fk', 'course_offerings', ['school_id', 'academic_year_id'], 'academic_years', ['school_id', 'id']);
        $this->ensureForeignKey('course_offerings_period_tenant_fk', 'course_offerings', ['school_id', 'academic_year_id', 'academic_period_id'], 'academic_periods', ['school_id', 'academic_year_id', 'id']);

        if (! Schema::hasTable('course_offering_curriculum_memberships')) {
            Schema::create('course_offering_curriculum_memberships', function (Blueprint $table): void {
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('course_offering_id');
                $table->unsignedBigInteger('curriculum_id');
                $table->unsignedBigInteger('curriculum_membership_id');
                $table->unsignedBigInteger('subject_id');
                $table->timestamps();
                $table->primary(['school_id', 'course_offering_id', 'curriculum_membership_id'], 'course_offering_membership_pk');
            });
        }
        $this->assertColumns('course_offering_curriculum_memberships', ['school_id', 'course_offering_id', 'curriculum_id', 'curriculum_membership_id', 'subject_id', 'created_at', 'updated_at']);
        foreach (['school_id', 'course_offering_id', 'curriculum_id', 'curriculum_membership_id', 'subject_id'] as $column) {
            $this->assertUnsignedBigint('course_offering_curriculum_memberships', $column);
        }
        $this->assertIndex('course_offering_curriculum_memberships', 'PRIMARY', ['school_id', 'course_offering_id', 'curriculum_membership_id'], true);
        $this->ensureIndex('course_offering_curriculum_memberships', 'cofm_curriculum_membership_idx', ['school_id', 'curriculum_id', 'curriculum_membership_id']);
        $this->ensureForeignKey('cofm_offering_subject_tenant_fk', 'course_offering_curriculum_memberships', ['school_id', 'course_offering_id', 'subject_id'], 'course_offerings', ['school_id', 'id', 'subject_id']);
        $this->ensureForeignKey('cofm_membership_subject_tenant_fk', 'course_offering_curriculum_memberships', ['school_id', 'curriculum_id', 'curriculum_membership_id', 'subject_id'], 'curriculum_memberships', ['school_id', 'curriculum_id', 'id', 'subject_id']);
    }

    public function down(): void
    {
        Schema::dropIfExists('course_offering_curriculum_memberships');
        Schema::dropIfExists('course_offerings');
        if ($this->indexExists('curriculum_memberships', self::MEMBERSHIP_SUBJECT_INDEX)) {
            Schema::table('curriculum_memberships', function (Blueprint $table): void {
                $table->dropUnique(self::MEMBERSHIP_SUBJECT_INDEX);
            });
        }
    }

    private function assertUnsignedBigint(string $table, string $column): void
    {
        $row = DB::selectOne('SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]);
        if (! $row || ! preg_match('/^bigint(?:\(\d+\))? unsigned$/i', $row->COLUMN_TYPE)) {
            throw new RuntimeException("Cannot create tenant-safe Course Offering foreign keys: {$table}.{$column} must be BIGINT UNSIGNED.");
        }
    }

    private function assertVarchar(string $table, string $column, int $length, bool $nullable): void
    {
        $row = DB::selectOne('SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]);
        if (! $row || strtolower($row->COLUMN_TYPE) !== "varchar({$length})" || ($row->IS_NULLABLE === 'YES') !== $nullable) {
            throw new RuntimeException("Existing {$table}.{$column} does not match the required VARCHAR({$length}) nullability.");
        }
    }

    private function assertColumns(string $table, array $expected): void
    {
        $actual = array_map(fn ($row) => $row->COLUMN_NAME, DB::select('SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION', [$table]));
        foreach ($expected as $column) {
            if (! in_array($column, $actual, true)) {
                throw new RuntimeException("Existing {$table} is incomplete; missing {$column}. Refusing to continue automatically.");
            }
        }
    }

    private function ensureUniqueIndex(string $table, string $name, array $columns): void
    {
        if ($this->indexExists($table, $name)) {
            $this->assertIndex($table, $name, $columns, true);
            return;
        }
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $name));
    }

    private function ensureIndex(string $table, string $name, array $columns): void
    {
        if ($this->indexExists($table, $name)) {
            $this->assertIndex($table, $name, $columns, false);
            return;
        }
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
    }

    private function assertIndex(string $table, string $name, array $columns, bool $unique): void
    {
        $rows = DB::select('SELECT COLUMN_NAME, NON_UNIQUE FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? ORDER BY SEQ_IN_INDEX', [$table, $name]);
        $actual = array_map(fn ($row) => $row->COLUMN_NAME, $rows);
        $isUnique = count($rows) > 0 && (int) $rows[0]->NON_UNIQUE === 0;
        if ($actual !== $columns || $isUnique !== $unique) {
            throw new RuntimeException("Existing index {$table}.{$name} does not match the required definition.");
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return DB::table('information_schema.statistics')->where('table_schema', DB::connection()->getDatabaseName())->where('table_name', $table)->where('index_name', $name)->exists();
    }

    private function ensureForeignKey(string $name, string $table, array $columns, string $parent, array $parentColumns): void
    {
        $rows = DB::select('SELECT k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, r.UPDATE_RULE FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema = k.constraint_schema AND r.constraint_name = k.constraint_name AND r.table_name = k.table_name WHERE k.constraint_schema = DATABASE() AND k.table_name = ? AND k.constraint_name = ? ORDER BY k.ORDINAL_POSITION', [$table, $name]);
        if ($rows) {
            $actualColumns = array_map(fn ($row) => $row->COLUMN_NAME, $rows);
            $actualParentColumns = array_map(fn ($row) => $row->REFERENCED_COLUMN_NAME, $rows);
            if ($actualColumns !== $columns || $actualParentColumns !== $parentColumns || $rows[0]->REFERENCED_TABLE_NAME !== $parent || strtoupper($rows[0]->DELETE_RULE) !== 'RESTRICT' || strtoupper($rows[0]->UPDATE_RULE) !== 'RESTRICT') {
                throw new RuntimeException("Existing foreign key {$name} does not match the required restrictive tenant definition.");
            }
            return;
        }
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($columns, $name)->references($parentColumns)->on($parent)->onDelete('restrict')->onUpdate('restrict'));
    }
};
