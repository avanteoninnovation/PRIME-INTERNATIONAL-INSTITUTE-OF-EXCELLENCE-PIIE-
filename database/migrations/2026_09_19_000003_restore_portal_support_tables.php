<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Match the application's installer schema, without importing demo data.
        if (!Schema::hasTable('addons')) {
            Schema::create('addons', function (Blueprint $table) {
                $table->increments('id');
                foreach (['title', 'features', 'purchase_code', 'unique_identifier'] as $column) {
                    $table->string($column)->nullable();
                }
                $table->integer('parent_id')->nullable();
                $table->float('version')->nullable();
                $table->integer('status');
            });
        }
        if (!Schema::hasTable('chats')) {
            Schema::create('chats', function (Blueprint $table) {
                $table->increments('id');
                foreach (['message_thrade', 'reciver_id', 'sender_id', 'reply_id', 'school_id', 'read_status'] as $column) {
                    $table->integer($column)->nullable();
                }
                $table->longText('message')->nullable();
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('message_thrades')) {
            Schema::create('message_thrades', function (Blueprint $table) {
                $table->increments('id');
                foreach (['reciver_id', 'sender_id', 'school_id'] as $column) {
                    $table->integer($column)->nullable();
                }
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('payment_gateways')) {
            Schema::create('payment_gateways', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->integer('status')->nullable();
                $table->longText('configuration');
                $table->integer('created_at');
                $table->integer('updated_at');
            });
        }
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->longText('payment_keys');
                $table->string('image')->nullable();
                $table->integer('status')->default(0);
                $table->string('mode')->default('test');
                $table->string('created_at')->nullable();
                $table->string('updated_at')->nullable();
                $table->integer('school_id')->nullable();
                $table->string('currency', 10)->nullable();
                $table->string('currency_position', 10)->nullable();
            });
        }
    }

    public function down()
    {
        // Preserve portal data: these tables may predate this compatibility migration.
    }
};
