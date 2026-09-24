<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // These fields exist in assets/install.sql and are used by the models,
        // but were omitted from the original create-table migrations.
        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'running_session')) {
                $table->integer('running_session')->nullable();
            }
            foreach (['school_currency', 'currency_position', 'school_logo', 'email_title', 'email_details', 'warning_text', 'socialLink1', 'socialLink2', 'socialLink3', 'email_logo', 'socialLogo1', 'socialLogo2', 'socialLogo3', 'off_pay_ins_text', 'off_pay_ins_file'] as $column) {
                if (!Schema::hasColumn('schools', $column)) {
                    $table->string($column)->nullable();
                }
            }
        });
        Schema::table('sessions', function (Blueprint $table) {
            if (!Schema::hasColumn('sessions', 'school_id')) {
                $table->integer('school_id')->nullable();
            }
        });
        Schema::table('users', function (Blueprint $table) {
            foreach (['status', 'school_role'] as $column) {
                if (!Schema::hasColumn('users', $column)) {
                    $table->integer($column)->nullable();
                }
            }
            if (!Schema::hasColumn('users', 'language')) {
                $table->string('language')->nullable();
            }
            if (!Schema::hasColumn('users', 'menu_permission')) {
                $table->text('menu_permission')->nullable();
            }
        });
    }

    public function down()
    {
        // Preserve compatibility columns and school configuration on rollback.
    }
};
