<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the existing `subjects` table (the app's one Subject/Course
 * concept — see Subject::class, already linked to `programmes` via
 * `programme_id` since 2026_07_02_130004) with the fields the client's
 * "Courses" requirements need: a code, a level (mirroring Programme's
 * standardized list), and the CAT/Exam mark split agreed with the client
 * (cats_marks + exam_marks = 100, existing `pass_mark` column reused as-is).
 *
 * `class_id`/`session_id` become nullable because a Programme-linked HEI
 * course has no K-12 class/academic-session — existing K-12 subject rows
 * are untouched (they keep their real class_id/session_id values).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'code')) {
                $table->string('code', 30)->nullable()->after('name');
            }
            if (! Schema::hasColumn('subjects', 'level')) {
                $table->enum('level', [
                    'Certificate', 'Diploma', 'Degree', 'Masters', 'PhD', 'Short Course', 'Bachelors', 'PGD',
                ])->nullable()->after('course_type');
            }
            if (! Schema::hasColumn('subjects', 'cats_marks')) {
                $table->tinyInteger('cats_marks')->nullable()->default(30)->after('pass_mark');
            }
            if (! Schema::hasColumn('subjects', 'exam_marks')) {
                $table->tinyInteger('exam_marks')->nullable()->default(70)->after('cats_marks');
            }
        });

        DB::statement('ALTER TABLE `subjects` MODIFY `class_id` INT NULL');
        DB::statement('ALTER TABLE `subjects` MODIFY `session_id` INT NULL');

        if (! $this->indexExists('subjects', 'subjects_school_id_code_unique')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->unique(['school_id', 'code'], 'subjects_school_id_code_unique');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('subjects', 'subjects_school_id_code_unique')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->dropUnique('subjects_school_id_code_unique');
            });
        }

        Schema::table('subjects', function (Blueprint $table) {
            foreach (['code', 'level', 'cats_marks', 'exam_marks'] as $col) {
                if (Schema::hasColumn('subjects', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        // class_id/session_id intentionally left nullable on rollback —
        // narrowing back to NOT NULL could fail against rows saved without them.
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])) > 0;
    }
};
