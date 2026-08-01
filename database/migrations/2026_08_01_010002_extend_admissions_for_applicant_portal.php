<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns `admissions` from a single-shot contact-form capture into a
 * resumable application record owned by an applicant.
 *
 * Two things are load-bearing here:
 *  - `status` moves from an ENUM to a VARCHAR. The portal adds 'draft' and
 *    'needs_correction', and a workflow that gains a state every few releases
 *    should not need an ALTER on a table that will grow to every applicant
 *    the institution has ever had. Allowed values now live in
 *    Admission::STATUSES and are enforced by validation.
 *  - existing rows are backfilled to a sane portal state rather than left
 *    with NULL step tracking, so the admin review screens don't have to
 *    special-case pre-portal applications.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->unsignedBigInteger('applicant_id')->nullable()->after('school_id')->index();

            // Wizard state
            $table->string('current_step', 40)->nullable()->after('source');
            $table->json('completed_steps')->nullable()->after('current_step');
            $table->timestamp('submitted_at')->nullable()->after('completed_steps');
            $table->timestamp('declaration_accepted_at')->nullable()->after('submitted_at');

            // Expanded personal details
            $table->string('title', 10)->nullable()->after('applicant_id');
            $table->string('middle_name', 100)->nullable()->after('first_name');
            $table->string('marital_status', 20)->nullable()->after('gender');
            $table->string('religion', 50)->nullable()->after('marital_status');
            $table->string('country_of_residence', 80)->nullable()->after('nationality');
            $table->string('national_id_no', 50)->nullable()->after('country_of_residence');
            $table->string('passport_no', 50)->nullable()->after('national_id_no');
            $table->text('physical_address')->nullable()->after('passport_no');
            $table->string('city', 80)->nullable()->after('physical_address');
            $table->boolean('has_disability')->default(0)->after('city');
            $table->text('disability_details')->nullable()->after('has_disability');

            // Next of kin / emergency contact
            $table->string('nok_name', 150)->nullable()->after('disability_details');
            $table->string('nok_relationship', 60)->nullable()->after('nok_name');
            $table->string('nok_phone', 30)->nullable()->after('nok_relationship');
            $table->string('nok_email', 150)->nullable()->after('nok_phone');
            $table->text('nok_address')->nullable()->after('nok_email');

            // Sponsorship
            $table->string('sponsor_type', 30)->nullable()->after('nok_address');
            $table->string('sponsor_name', 150)->nullable()->after('sponsor_type');
            $table->string('sponsor_phone', 30)->nullable()->after('sponsor_name');
            $table->string('sponsor_email', 150)->nullable()->after('sponsor_phone');

            // Programme choice
            $table->unsignedBigInteger('second_choice_programme_id')->nullable()->after('programme_id');
            $table->string('study_mode', 30)->nullable()->after('second_choice_programme_id');
            $table->string('how_did_you_hear', 100)->nullable()->after('study_mode');

            // Fee + decision
            $table->string('fee_status', 20)->default('unpaid')->after('declaration_accepted_at');
            $table->text('correction_note')->nullable()->after('notes');
            $table->text('decision_note')->nullable()->after('correction_note');
            $table->timestamp('decided_at')->nullable()->after('decision_note');
        });

        // The index goes in its own statement. Declared inside the blueprint
        // above it is silently dropped on SQLite — the migration reports
        // success and the index simply never exists — which would leave the
        // status-filtered admissions queue doing a full scan on the largest
        // table in the schema.
        Schema::table('admissions', function (Blueprint $table) {
            $table->index(['school_id', 'status'], 'admissions_school_id_status_index');
        });

        // ENUM → VARCHAR. MySQL/MariaDB need a raw MODIFY (doctrine/dbal is
        // not installed, so ->change() is unavailable); SQLite already stores
        // enums as free-text so there is nothing to do there.
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `admissions` MODIFY `status` VARCHAR(30) NOT NULL DEFAULT 'submitted'");
        }

        // Backfill: every pre-portal application is, by definition, already
        // past the wizard — mark it fully stepped-through and submitted at
        // its creation time so progress bars and timelines read correctly.
        //
        // fee_status is set to 'waived' rather than left at the 'unpaid'
        // default. These applications predate the application-fee flow
        // entirely: whatever was or wasn't paid happened outside this system.
        // Defaulting them to 'unpaid' would light up the new Fee column on
        // every historical row and invent a backlog of debts nobody is owed.
        $allSteps = json_encode(['personal', 'education', 'programme', 'documents', 'payment', 'review']);

        DB::table('admissions')
            ->whereNull('current_step')
            ->update([
                'current_step'    => 'review',
                'completed_steps' => $allSteps,
                'submitted_at'    => DB::raw('created_at'),
                'fee_status'      => 'waived',
            ]);
    }

    public function down()
    {
        // Tolerated rather than assumed: dropping columns on SQLite rebuilds
        // the table and takes its indexes with it, so by the time the column
        // drop below runs the index may already be gone.
        try {
            Schema::table('admissions', function (Blueprint $table) {
                $table->dropIndex('admissions_school_id_status_index');
            });
        } catch (\Throwable $e) {
            // Index already absent — nothing to undo.
        }

        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn([
                'applicant_id', 'current_step', 'completed_steps', 'submitted_at',
                'declaration_accepted_at', 'title', 'middle_name', 'marital_status',
                'religion', 'country_of_residence', 'national_id_no', 'passport_no',
                'physical_address', 'city', 'has_disability', 'disability_details',
                'nok_name', 'nok_relationship', 'nok_phone', 'nok_email', 'nok_address',
                'sponsor_type', 'sponsor_name', 'sponsor_phone', 'sponsor_email',
                'second_choice_programme_id', 'study_mode', 'how_did_you_hear',
                'fee_status', 'correction_note', 'decision_note', 'decided_at',
            ]);
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `admissions` MODIFY `status` ENUM('submitted','under_review','accepted','rejected','enrolled','withdrawn') NOT NULL DEFAULT 'submitted'");
        }
    }
};
