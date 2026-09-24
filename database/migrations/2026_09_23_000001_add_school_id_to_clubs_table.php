<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Security Phase 2H: a club belongs to exactly one school. Members and
 * notices belong to a school through their club (club_id), so only the
 * clubs table gains school_id.
 *
 * Follows the existing convention (unsignedBigInteger + index, no foreign
 * key constraint to schools anywhere in this schema, so no cascade either).
 *
 * Existing clubs are attributed only when it is certain:
 *   1. the advisor's school, when the advisor is a user with a school; else
 *   2. the one school every member belongs to, when all members agree.
 * Anything else stays NULL — visible to no school — rather than guessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('clubs', 'school_id')) {
            Schema::table('clubs', function (Blueprint $table) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id')->index();
            });
        }

        foreach (DB::table('clubs')->whereNull('school_id')->get(['id', 'advisor_id']) as $club) {
            $schoolId = $club->advisor_id
                ? DB::table('users')->where('id', $club->advisor_id)->whereNotNull('school_id')->value('school_id')
                : null;

            if (!$schoolId) {
                $memberSchools = DB::table('club_members')
                    ->join('users', 'users.id', '=', 'club_members.student_id')
                    ->where('club_members.club_id', $club->id)
                    ->distinct()
                    ->pluck('users.school_id');
                $schoolId = $memberSchools->count() === 1 ? $memberSchools->first() : null;
            }

            if ($schoolId) {
                DB::table('clubs')->where('id', $club->id)->update(['school_id' => $schoolId]);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('clubs', 'school_id')) {
            return;
        }

        Schema::table('clubs', function (Blueprint $table) {
            $table->dropIndex(['school_id']);
        });

        if (DB::getDriverName() === 'sqlite') {
            // Laravel 9 needs doctrine/dbal to drop columns on SQLite; SQLite >= 3.35 can do it natively.
            DB::statement('ALTER TABLE clubs DROP COLUMN school_id');
        } else {
            Schema::table('clubs', function (Blueprint $table) {
                $table->dropColumn('school_id');
            });
        }
    }
};
