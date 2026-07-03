<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('level', ['Certificate','Diploma','Degree','Masters','PhD','Short Course'])->default('Degree');
            $table->string('duration', 50)->nullable();
            $table->enum('mode', ['fulltime','parttime','online','blended'])->default('fulltime');
            $table->decimal('tuition_fee', 15, 2)->default(0);
            $table->unsignedBigInteger('department_id')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programmes');
    }
};
