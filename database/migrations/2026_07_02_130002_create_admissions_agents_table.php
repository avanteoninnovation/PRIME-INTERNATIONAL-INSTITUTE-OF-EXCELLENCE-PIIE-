<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('admissions_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 150);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admissions_agents');
    }
};
