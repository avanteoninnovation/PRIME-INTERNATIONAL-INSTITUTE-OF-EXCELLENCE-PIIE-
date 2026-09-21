<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A digital student-association election: an Election has one or more
 * Positions (e.g. "President", "Secretary General"), each Position has
 * Candidates, and each eligible student casts one Vote per Position. The
 * one hard integrity rule the whole feature exists to enforce — a student
 * cannot vote twice for the same position — is a database UNIQUE
 * constraint on (position_id, voter_id) in election_votes, not just an
 * application-level check: the check-then-insert in ElectionController::
 * castVote() is wrapped in that constraint specifically so a double-submit
 * (double click, retried request) can never slip through a race condition
 * between the check and the insert.
 *
 * Election "open/closed" is computed from start_at/end_at (same pattern
 * OnlineExam/LiveClass already use for their own computed_status), not a
 * separately-toggled status column that could drift out of sync with the
 * actual dates.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 191);
            $table->text('description')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->boolean('results_published')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('election_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title', 191);
            $table->timestamps();
        });

        Schema::create('election_candidates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('position_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('student_id');
            $table->text('manifesto')->nullable();
            $table->timestamps();

            $table->unique(['position_id', 'student_id']);
        });

        Schema::create('election_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('election_id')->index();
            $table->unsignedBigInteger('position_id')->index();
            $table->unsignedBigInteger('candidate_id')->index();
            $table->unsignedBigInteger('voter_id')->index();
            $table->unsignedBigInteger('school_id')->index();
            $table->timestamp('created_at')->nullable();

            // The integrity guarantee this whole table exists for.
            $table->unique(['position_id', 'voter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_votes');
        Schema::dropIfExists('election_candidates');
        Schema::dropIfExists('election_positions');
        Schema::dropIfExists('elections');
    }
};
