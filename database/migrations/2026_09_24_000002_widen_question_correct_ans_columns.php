<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Stability repair M7 — question_banks.correct_ans and online_exam_questions.correct_ans
 * were created as VARCHAR(5) (sized for option keys A–D / true / false), but the question
 * engine validates correct_ans up to 255 characters and stores the reference answer of
 * short-answer questions there. On MySQL any short answer longer than 5 characters failed
 * with SQLSTATE 22001 "Data too long for column 'correct_ans'" (HTTP 500); SQLite does not
 * enforce VARCHAR length, which is why the test suite never caught it.
 *
 * Widening only: no value is rewritten, no row is touched, existing keys stay valid.
 * SQLite needs nothing (lengths are not enforced). The rollback is intentionally a no-op:
 * narrowing back to 5 characters would truncate stored answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['question_banks', 'online_exam_questions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'correct_ans')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `correct_ans` VARCHAR(255) NULL");
            }
        }
    }

    public function down(): void
    {
        // Intentionally no-op: shrinking the column could truncate stored answers.
    }
};
