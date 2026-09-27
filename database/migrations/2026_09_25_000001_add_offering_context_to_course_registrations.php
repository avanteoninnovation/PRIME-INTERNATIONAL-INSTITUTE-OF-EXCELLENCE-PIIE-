<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OFFERING_UNIQUE = 'cr_school_student_offering_uq';
    private const OFFERING_SUBJECT_FK = 'cr_offering_subject_tenant_fk';
    private const APPLICABILITY_FK = 'cr_offering_membership_fk';

    public function up(): void
    {
        foreach (['course_registrations', 'course_offerings', 'course_offering_curriculum_memberships'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException("Cannot add Offering registration context: required table {$table} is missing.");
            }
        }

        $this->assertUnsignedBigint('course_registrations', 'school_id');
        $this->assertUnsignedBigint('course_registrations', 'student_id');
        $this->assertUnsignedBigint('course_registrations', 'subject_id');
        $this->assertUnsignedBigint('course_offerings', 'school_id');
        $this->assertUnsignedBigint('course_offerings', 'id');
        $this->assertUnsignedBigint('course_offerings', 'subject_id');
        $this->assertUnsignedBigint('course_offering_curriculum_memberships', 'school_id');
        $this->assertUnsignedBigint('course_offering_curriculum_memberships', 'course_offering_id');
        $this->assertUnsignedBigint('course_offering_curriculum_memberships', 'curriculum_membership_id');

        $this->assertUniqueIndex('course_offerings', 'course_offerings_school_id_subject_unique', ['school_id', 'id', 'subject_id']);
        $this->assertUniqueIndex('course_offering_curriculum_memberships', 'PRIMARY', ['school_id', 'course_offering_id', 'curriculum_membership_id']);

        if (! Schema::hasColumn('course_registrations', 'course_offering_id')) {
            Schema::table('course_registrations', fn (Blueprint $table) => $table->unsignedBigInteger('course_offering_id')->nullable());
        }
        if (! Schema::hasColumn('course_registrations', 'curriculum_membership_id')) {
            Schema::table('course_registrations', fn (Blueprint $table) => $table->unsignedBigInteger('curriculum_membership_id')->nullable());
        }
        if (! Schema::hasColumn('course_registrations', 'registered_credits')) {
            Schema::table('course_registrations', fn (Blueprint $table) => $table->decimal('registered_credits', 6, 2)->unsigned()->nullable());
        }
        if (! Schema::hasColumn('course_registrations', 'registered_classification')) {
            Schema::table('course_registrations', fn (Blueprint $table) => $table->enum('registered_classification', ['compulsory', 'elective'])->nullable());
        }

        $this->assertUnsignedBigint('course_registrations', 'course_offering_id');
        $this->assertUnsignedBigint('course_registrations', 'curriculum_membership_id');
        $this->assertNullableDecimal('course_registrations', 'registered_credits', 6, 2, true);
        $this->assertNullableEnum('course_registrations', 'registered_classification', ['compulsory', 'elective']);

        $duplicate = DB::table('course_registrations')
            ->select('school_id', 'student_id', 'course_offering_id')
            ->whereNotNull('course_offering_id')
            ->groupBy('school_id', 'student_id', 'course_offering_id')
            ->havingRaw('COUNT(*) > 1')
            ->first();
        if ($duplicate) {
            throw new RuntimeException('Cannot add Offering registration uniqueness: duplicate Offering-backed registration identities already exist.');
        }

        $this->ensureUniqueIndex('course_registrations', self::OFFERING_UNIQUE, ['school_id', 'student_id', 'course_offering_id']);
        $this->ensureForeignKey(
            self::OFFERING_SUBJECT_FK,
            'course_registrations',
            ['school_id', 'course_offering_id', 'subject_id'],
            'course_offerings',
            ['school_id', 'id', 'subject_id']
        );
        $this->ensureForeignKey(
            self::APPLICABILITY_FK,
            'course_registrations',
            ['school_id', 'course_offering_id', 'curriculum_membership_id'],
            'course_offering_curriculum_memberships',
            ['school_id', 'course_offering_id', 'curriculum_membership_id']
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('course_registrations')) {
            return;
        }

        $this->dropForeignIfExists(self::APPLICABILITY_FK);
        $this->dropForeignIfExists(self::OFFERING_SUBJECT_FK);
        $this->dropIndexIfExists(self::OFFERING_UNIQUE);

        $columns = array_values(array_filter(
            ['registered_classification', 'registered_credits', 'curriculum_membership_id', 'course_offering_id'],
            fn (string $column) => Schema::hasColumn('course_registrations', $column)
        ));
        if ($columns) {
            Schema::table('course_registrations', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }

    private function assertUnsignedBigint(string $table, string $column): void
    {
        $row = DB::selectOne('SELECT COLUMN_TYPE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]);
        if (! $row || ! preg_match('/^bigint(?:\(\d+\))? unsigned$/i', $row->COLUMN_TYPE)) {
            throw new RuntimeException("Cannot add tenant-safe registration keys: {$table}.{$column} must be BIGINT UNSIGNED.");
        }
    }

    private function assertUniqueIndex(string $table, string $name, array $columns): void
    {
        $rows = DB::select('SELECT COLUMN_NAME, NON_UNIQUE FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? ORDER BY SEQ_IN_INDEX', [$table, $name]);
        $actual = array_map(fn ($row) => $row->COLUMN_NAME, $rows);
        if ($actual !== $columns || ! $rows || (int) $rows[0]->NON_UNIQUE !== 0) {
            throw new RuntimeException("Required unique key {$table}.{$name}({$columns[0]}) is missing or incompatible.");
        }
    }

    private function ensureUniqueIndex(string $table, string $name, array $columns): void
    {
        $rows = DB::select('SELECT COLUMN_NAME, NON_UNIQUE FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? ORDER BY SEQ_IN_INDEX', [$table, $name]);
        if ($rows) {
            $actual = array_map(fn ($row) => $row->COLUMN_NAME, $rows);
            if ($actual !== $columns || (int) $rows[0]->NON_UNIQUE !== 0) {
                throw new RuntimeException("Existing index {$table}.{$name} is incompatible; refusing to replace it.");
            }
            return;
        }
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($columns, $name));
    }

    private function ensureForeignKey(string $name, string $table, array $columns, string $parent, array $parentColumns): void
    {
        $rows = DB::select('SELECT k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE, r.UPDATE_RULE FROM information_schema.key_column_usage k JOIN information_schema.referential_constraints r ON r.constraint_schema = k.constraint_schema AND r.constraint_name = k.constraint_name AND r.table_name = k.table_name WHERE k.constraint_schema = DATABASE() AND k.table_name = ? AND k.constraint_name = ? ORDER BY k.ORDINAL_POSITION', [$table, $name]);
        if ($rows) {
            $actualColumns = array_map(fn ($row) => $row->COLUMN_NAME, $rows);
            $actualParentColumns = array_map(fn ($row) => $row->REFERENCED_COLUMN_NAME, $rows);
            if ($actualColumns !== $columns || $actualParentColumns !== $parentColumns || $rows[0]->REFERENCED_TABLE_NAME !== $parent || strtoupper($rows[0]->DELETE_RULE) !== 'RESTRICT' || strtoupper($rows[0]->UPDATE_RULE) !== 'RESTRICT') {
                throw new RuntimeException("Existing foreign key {$name} is incompatible; refusing to replace it.");
            }
            return;
        }
        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->foreign($columns, $name)->references($parentColumns)->on($parent)->onDelete('restrict')->onUpdate('restrict'));
    }

    private function assertNullableDecimal(string $table, string $column, int $precision, int $scale, bool $unsigned): void
    {
        $row = DB::selectOne('SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]);
        $expected = 'decimal('.$precision.','.$scale.')'.($unsigned ? ' unsigned' : '');
        if (! $row || strtolower($row->COLUMN_TYPE) !== $expected || $row->IS_NULLABLE !== 'YES') {
            throw new RuntimeException("Existing {$table}.{$column} does not match nullable {$expected}.");
        }
    }

    private function assertNullableEnum(string $table, string $column, array $values): void
    {
        $row = DB::selectOne('SELECT COLUMN_TYPE, IS_NULLABLE FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?', [$table, $column]);
        $expected = "enum('".implode("','", $values)."')";
        if (! $row || strtolower($row->COLUMN_TYPE) !== $expected || $row->IS_NULLABLE !== 'YES') {
            throw new RuntimeException("Existing {$table}.{$column} does not match nullable {$expected}.");
        }
    }

    private function dropForeignIfExists(string $name): void
    {
        $exists = DB::table('information_schema.key_column_usage')->where('constraint_schema', DB::connection()->getDatabaseName())->where('table_name', 'course_registrations')->where('constraint_name', $name)->exists();
        if ($exists) {
            Schema::table('course_registrations', fn (Blueprint $table) => $table->dropForeign($name));
        }
    }

    private function dropIndexIfExists(string $name): void
    {
        $exists = DB::table('information_schema.statistics')->where('table_schema', DB::connection()->getDatabaseName())->where('table_name', 'course_registrations')->where('index_name', $name)->exists();
        if ($exists) {
            Schema::table('course_registrations', fn (Blueprint $table) => $table->dropUnique($name));
        }
    }
};
