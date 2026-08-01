<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Files and links a lecturer attaches to a class — slides, readings,
 * recording notes. `type` distinguishes an uploaded file (stored_name
 * points at public/assets/uploads/live_class_materials) from a bare link
 * (link_url only); a row is always exactly one or the other, never both.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('live_class_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('live_class_id')->index();
            $table->string('type', 10)->default('file'); // file | link
            $table->string('title', 200);
            $table->string('original_name', 255)->nullable();
            $table->string('stored_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('link_url', 500)->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('live_class_materials');
    }
};
