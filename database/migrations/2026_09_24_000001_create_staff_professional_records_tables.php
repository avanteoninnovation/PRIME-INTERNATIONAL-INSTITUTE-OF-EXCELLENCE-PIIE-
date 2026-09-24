<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staff Management — professional staff records (additive only).
 *
 * users remains the authoritative staff identity (name, email, base role,
 * school, department/designation, employment type, staff status, staff
 * number, contact details and photo in user_information). These tables only
 * ADD what users cannot hold:
 *
 *   staff_profiles                    0..1 per user: personal extras, NIN (encrypted
 *                                     + keyed hash), emergency contact, teaching profile
 *   staff_qualifications              0..N per user
 *   staff_professional_registrations  0..N per user
 *   staff_experiences                 0..N per user
 *   staff_documents                   0..N per user: metadata of PRIVATE files
 *
 * Every row carries school_id (tenant boundary). Project convention: plain
 * unsignedBigInteger + index, no foreign-key constraints (so no cascades).
 * Nothing existing is altered or backfilled; historical staff keep working
 * without a profile. The rollback drops only these new tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staff_profiles')) {
            Schema::create('staff_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->unsignedBigInteger('school_id')->index();

                $table->string('title', 30)->nullable();
                $table->string('middle_name', 100)->nullable();
                $table->string('nationality', 100)->nullable();
                $table->string('marital_status', 30)->nullable();
                $table->string('religion', 100)->nullable();

                // NIN: encrypted at rest; nin_hash is a keyed HMAC of the normalized value for
                // duplicate detection (unique per school). Never selected by directory queries.
                $table->text('nin_encrypted')->nullable();
                $table->string('nin_hash', 64)->nullable();

                $table->string('alternative_phone', 50)->nullable();
                $table->string('city', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->date('date_joined')->nullable();

                $table->string('emergency_contact_name', 150)->nullable();
                $table->string('emergency_contact_relationship', 60)->nullable();
                $table->string('emergency_contact_phone', 50)->nullable();
                $table->string('emergency_contact_alternative_phone', 50)->nullable();
                $table->string('emergency_contact_address', 255)->nullable();

                // Teaching profile (teachers only; always nullable).
                $table->string('academic_title', 100)->nullable();
                $table->string('specialisation', 191)->nullable();
                $table->unsignedSmallInteger('years_teaching_experience')->nullable();
                $table->text('research_interests')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['school_id', 'nin_hash']);
            });
        }

        if (!Schema::hasTable('staff_qualifications')) {
            Schema::create('staff_qualifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('qualification_level', 50);
                $table->string('qualification_name', 191);
                $table->string('specialisation', 191)->nullable();
                $table->string('institution', 191);
                $table->string('country', 100)->nullable();
                $table->unsignedSmallInteger('start_year')->nullable();
                $table->unsignedSmallInteger('completion_year')->nullable();
                $table->string('grade_or_class', 100)->nullable();
                $table->string('certificate_number', 100)->nullable();
                $table->unsignedBigInteger('evidence_document_id')->nullable()->index();
                $table->string('verification_status', 20)->default('pending');
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('staff_professional_registrations')) {
            Schema::create('staff_professional_registrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('professional_body', 191);
                $table->string('membership_number', 100)->nullable();
                $table->string('registration_number', 100)->nullable();
                $table->date('issue_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->unsignedBigInteger('evidence_document_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('staff_experiences')) {
            Schema::create('staff_experiences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('employer', 191);
                $table->string('position', 191);
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->boolean('currently_working')->default(false);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('staff_documents')) {
            Schema::create('staff_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('school_id')->index();
                $table->string('category', 40);
                // Server-generated key relative to the private staff-documents root (never public/).
                $table->string('storage_key', 255)->unique();
                $table->string('original_name', 255);
                $table->string('mime_type', 100);
                $table->unsignedBigInteger('size_bytes');
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('verification_status', 20)->default('pending');
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->text('verification_notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('staff_experiences');
        Schema::dropIfExists('staff_professional_registrations');
        Schema::dropIfExists('staff_qualifications');
        Schema::dropIfExists('staff_profiles');
    }
};
