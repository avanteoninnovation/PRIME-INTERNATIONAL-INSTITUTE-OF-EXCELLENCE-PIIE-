<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Note: matching the original vendor schema and current code, these three
        // tables are NOT school-scoped (no school_id) — the Club module predates
        // this app's multi-tenant conversion and no controller filters by school_id.

        if (!Schema::hasTable('clubs')) {
            Schema::create('clubs', function (Blueprint $table) {
                $table->id();
                $table->string('club_name');
                $table->string('school_name')->nullable();
                $table->longText('description')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('club_members')) {
            Schema::create('club_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('club_id')->index();
                $table->unsignedBigInteger('student_id')->index();
                $table->tinyInteger('status')->default(0)->comment('0=Pending,1=Approved,2=Rejected');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('club_notices')) {
            Schema::create('club_notices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('club_id')->index();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->unsignedBigInteger('advisor_id')->nullable();
                $table->string('title');
                $table->text('description');
                $table->date('notice_date');
                $table->string('image')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('club_notices');
        Schema::dropIfExists('club_members');
        Schema::dropIfExists('clubs');
    }
};
