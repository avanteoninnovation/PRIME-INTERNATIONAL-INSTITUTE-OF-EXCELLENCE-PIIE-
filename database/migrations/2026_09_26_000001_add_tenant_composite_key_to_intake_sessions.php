<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'intake_sessions_school_id_id_unique';

    public function up(): void
    {
        if (! Schema::hasTable('intake_sessions')
            || ! Schema::hasColumn('intake_sessions', 'school_id')
            || ! Schema::hasColumn('intake_sessions', 'id')) {
            throw new RuntimeException('Cannot add the Intake Session tenant key: required table or columns are missing.');
        }

        $this->assertUnsignedBigint('school_id');
        $this->assertUnsignedBigint('id');

        $indexes = DB::select(
            'SELECT INDEX_NAME AS index_name, NON_UNIQUE AS non_unique, '
            .'GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS column_list '
            .'FROM information_schema.statistics '
            .'WHERE table_schema = DATABASE() AND table_name = ? '
            .'GROUP BY INDEX_NAME, NON_UNIQUE',
            ['intake_sessions']
        );

        foreach ($indexes as $index) {
            if ($index->index_name === self::INDEX) {
                throw new RuntimeException('The Intake Session tenant-key index name already exists; inspect it before applying this migration.');
            }
            if ((int) $index->non_unique === 0 && $index->column_list === 'school_id,id') {
                return;
            }
        }

        Schema::table('intake_sessions', function (Blueprint $table): void {
            $table->unique(['school_id', 'id'], self::INDEX);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('intake_sessions')) {
            return;
        }

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', DB::connection()->getDatabaseName())
            ->where('table_name', 'intake_sessions')
            ->where('index_name', self::INDEX)
            ->exists();

        if ($indexExists) {
            Schema::table('intake_sessions', function (Blueprint $table): void {
                $table->dropUnique(self::INDEX);
            });
        }
    }

    private function assertUnsignedBigint(string $column): void
    {
        $row = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type FROM information_schema.columns '
            .'WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            ['intake_sessions', $column]
        );

        if (! $row || ! preg_match('/^bigint(?:\(\d+\))? unsigned$/i', $row->column_type)) {
            throw new RuntimeException("Cannot add the Intake Session tenant key: intake_sessions.{$column} is not BIGINT UNSIGNED.");
        }
    }
};
