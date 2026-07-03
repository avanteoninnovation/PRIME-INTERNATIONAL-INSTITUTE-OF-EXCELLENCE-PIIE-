<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->enum('school_type', ['k12', 'higher_ed', 'mixed'])->default('k12')->after('status');
        });

        Schema::table('grades', function (Blueprint $table) {
            $table->decimal('gpa_points', 3, 2)->default(0.00)->after('grade_point');
            $table->string('classification', 50)->nullable()->after('gpa_points');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->tinyInteger('credits')->default(3)->after('name');
            $table->enum('course_type', ['compulsory','elective','general','dissertation'])->default('compulsory')->after('credits');
            $table->tinyInteger('pass_mark')->default(50)->after('course_type');
            $table->unsignedBigInteger('programme_id')->nullable()->after('pass_mark');
        });
    }

    public function down()
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('school_type');
        });
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['gpa_points', 'classification']);
        });
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['credits', 'course_type', 'pass_mark', 'programme_id']);
        });
    }
};
