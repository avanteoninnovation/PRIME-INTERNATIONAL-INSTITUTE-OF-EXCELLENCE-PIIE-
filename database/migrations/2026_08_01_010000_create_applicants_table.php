<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Applicants are deliberately NOT rows in `users`.
 *
 * A person applying to the institution is not yet a member of it: they have
 * no role_id, no menu_permission, and must never be reachable by any of the
 * role middleware that guards the staff/student areas. Keeping them in their
 * own table behind their own auth guard ("applicant") makes that structural
 * rather than a thing every future `*Middleware` has to remember to exclude.
 *
 * On enrolment the existing Admission → User conversion in
 * AdmissionsController::createStudentFromAdmission() still runs untouched and
 * creates the real student account; the applicant row is then linked to it
 * via `converted_user_id` so the portal can hand the person over to the
 * student portal instead of dead-ending.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150);
            $table->string('phone', 30)->nullable();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('email_verification_token', 64)->nullable()->index();
            $table->boolean('is_active')->default(1);
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedBigInteger('converted_user_id')->nullable()->index();
            $table->rememberToken();
            $table->timestamps();

            // Unique per school, not globally: the same person may legitimately
            // apply to two institutions on a multi-tenant deployment.
            $table->unique(['school_id', 'email'], 'applicants_school_email_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('applicants');
    }
};
