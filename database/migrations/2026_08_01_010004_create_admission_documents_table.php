<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per uploaded file, replacing the old `admissions.documents` JSON
 * array of bare filenames.
 *
 * The JSON column could not answer the questions the portal has to answer —
 * which requirement is this file for, has a reviewer accepted it, why was it
 * rejected, who replaced it and when — so each upload becomes a first-class
 * reviewable record. The legacy column is left in place and still read by
 * older screens; nothing is migrated out of it destructively.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('admission_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('admission_id')->index();
            $table->string('requirement_key', 60)->nullable()->index();
            $table->string('label', 150)->nullable();
            $table->string('original_name', 255);
            $table->string('stored_name', 255);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            // pending | verified | rejected
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->unsignedBigInteger('uploaded_by_applicant_id')->nullable();
            $table->unsignedBigInteger('uploaded_by_user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_documents');
    }
};
