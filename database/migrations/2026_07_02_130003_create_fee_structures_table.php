<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 150);
            $table->enum('fee_type', ['tuition','registration','library','ict','medical','exam','hostel','other'])->default('tuition');
            $table->decimal('amount', 15, 2);
            $table->tinyInteger('is_mandatory')->default(1);
            $table->tinyInteger('per_semester')->default(1);
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fee_structures');
    }
};
