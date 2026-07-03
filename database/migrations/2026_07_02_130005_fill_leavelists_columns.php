<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leavelists')) {
            Schema::create('leavelists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->default(0)->index();
                $table->unsignedBigInteger('user_id')->default(0);
                $table->unsignedBigInteger('leave_type_id')->nullable();
                $table->string('leave_type', 50)->nullable();
                $table->date('from_date')->nullable();
                $table->date('to_date')->nullable();
                $table->integer('days')->default(0);
                $table->text('reason')->nullable();
                $table->enum('status', ['pending','approved','rejected'])->default('pending');
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('leavelists', function (Blueprint $table) {
                if (!Schema::hasColumn('leavelists', 'school_id')) {
                    $table->unsignedBigInteger('school_id')->default(0)->after('id')->index();
                }
                if (!Schema::hasColumn('leavelists', 'user_id')) {
                    $table->unsignedBigInteger('user_id')->default(0)->after('school_id');
                }
                if (!Schema::hasColumn('leavelists', 'leave_type_id')) {
                    $table->unsignedBigInteger('leave_type_id')->nullable()->after('user_id');
                }
                if (!Schema::hasColumn('leavelists', 'leave_type')) {
                    $table->string('leave_type', 50)->nullable()->after('leave_type_id');
                }
                if (!Schema::hasColumn('leavelists', 'from_date')) {
                    $table->date('from_date')->nullable()->after('leave_type');
                }
                if (!Schema::hasColumn('leavelists', 'to_date')) {
                    $table->date('to_date')->nullable()->after('from_date');
                }
                if (!Schema::hasColumn('leavelists', 'days')) {
                    $table->integer('days')->default(0)->after('to_date');
                }
                if (!Schema::hasColumn('leavelists', 'reason')) {
                    $table->text('reason')->nullable()->after('days');
                }
                if (!Schema::hasColumn('leavelists', 'status')) {
                    $table->enum('status', ['pending','approved','rejected'])->default('pending')->after('reason');
                }
                if (!Schema::hasColumn('leavelists', 'approved_by')) {
                    $table->unsignedBigInteger('approved_by')->nullable()->after('status');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('leavelists');
    }
};
