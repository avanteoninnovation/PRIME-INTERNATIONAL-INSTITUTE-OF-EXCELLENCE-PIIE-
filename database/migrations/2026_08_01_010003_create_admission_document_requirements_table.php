<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What an applicant must upload, configured per school rather than hardcoded.
 *
 * Different programme levels ask for different evidence (a Masters applicant
 * needs a degree transcript; a Certificate applicant does not), so a
 * requirement can be scoped to specific levels via `applies_to_levels` — a
 * NULL/empty value means "every applicant". The requirement `key` is what
 * uploaded documents are filed under, so it is stable and immutable once in
 * use; the `label` is free to be re-worded at any time.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('admission_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('key', 60);
            $table->string('label', 150);
            $table->string('description', 500)->nullable();
            $table->boolean('is_required')->default(1);
            $table->boolean('allow_multiple')->default(0);
            $table->json('applies_to_levels')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->unique(['school_id', 'key'], 'admission_doc_req_school_key_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('admission_document_requirements');
    }
};
