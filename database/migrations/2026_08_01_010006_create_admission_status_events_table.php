<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The append-only history behind "Track My Application".
 *
 * Distinct from `audit_logs` on purpose: audit entries are internal, staff
 * facing and deliberately verbose, whereas these rows are shown to the
 * applicant. `is_visible_to_applicant` lets staff record an internal-only
 * transition (e.g. moving a file between reviewers) without it surfacing in
 * the applicant's timeline.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('admission_status_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('admission_id')->index();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('title', 150);
            $table->text('note')->nullable();

            // applicant | staff | system
            $table->string('actor_type', 20)->default('system');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name', 150)->nullable();

            $table->boolean('is_visible_to_applicant')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_status_events');
    }
};
