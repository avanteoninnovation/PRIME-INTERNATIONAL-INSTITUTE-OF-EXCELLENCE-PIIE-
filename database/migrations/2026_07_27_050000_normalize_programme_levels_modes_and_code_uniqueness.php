<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Standardizes Programme `level`/`mode` to the client's requested values
 * without touching existing data: both enums are extended (never reduced),
 * so the two live records using the legacy 'Degree' value and the two using
 * 'online'/'parttime' keep working exactly as before while new programmes
 * get the client's preferred option list (see admin/programme/modal.blade.php).
 *
 * Also adds a per-school unique constraint on `code` — verified against
 * production data first (two schools happen to share the code "BITC", but
 * never within the same school), so this is safe to add as-is.
 */
return new class extends Migration
{
    public function up()
    {
        $this->extendEnum('programmes', 'level', [
            'Certificate', 'Diploma', 'Degree', 'Masters', 'PhD', 'Short Course', 'Bachelors', 'PGD',
        ], 'Degree');

        $this->extendEnum('programmes', 'mode', [
            'fulltime', 'parttime', 'online', 'blended', 'ODEL', 'Full Time', 'Weekend',
        ], 'fulltime');

        $duplicateExists = DB::table('programmes')
            ->select('school_id', 'code')
            ->groupBy('school_id', 'code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $duplicateExists && ! $this->indexExists('programmes', 'programmes_school_id_code_unique')) {
            Schema::table('programmes', function ($table) {
                $table->unique(['school_id', 'code'], 'programmes_school_id_code_unique');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('programmes', 'programmes_school_id_code_unique')) {
            Schema::table('programmes', function ($table) {
                $table->dropUnique('programmes_school_id_code_unique');
            });
        }
        // Enum values are intentionally never removed on rollback — narrowing
        // an enum that already has rows using the new values would fail/lose data.
    }

    private function extendEnum(string $table, string $column, array $values, string $default): void
    {
        $current = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column])[0]->Type ?? '';

        $missing = array_filter($values, fn ($v) => ! str_contains($current, "'{$v}'"));
        if (empty($missing)) {
            return;
        }

        $enumList = implode(',', array_map(fn ($v) => "'" . str_replace("'", "\\'", $v) . "'", $values));
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` ENUM({$enumList}) NOT NULL DEFAULT '{$default}'");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($rows) > 0;
    }
};
