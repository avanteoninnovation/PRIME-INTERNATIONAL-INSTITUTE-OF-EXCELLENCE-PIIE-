<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('intake_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 100);
            $table->date('open_date')->nullable();
            $table->date('close_date')->nullable();
            $table->decimal('application_fee', 10, 2)->default(0);
            $table->tinyInteger('is_open')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('intake_sessions');
    }
};
