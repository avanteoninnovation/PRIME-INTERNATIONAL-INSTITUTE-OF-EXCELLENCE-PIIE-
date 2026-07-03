<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 100);
            $table->string('icon', 50)->default('fas fa-box');
            $table->string('color', 10)->default('#1a3a6b');
            $table->timestamps();
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('asset_tag', 50)->nullable();
            $table->string('name');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();
            $table->string('location', 150)->nullable();
            $table->enum('condition', ['new','good','fair','poor','condemned'])->default('good');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamps();
        });

        Schema::create('procurement_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->string('vendor', 150)->nullable();
            $table->enum('status', ['draft','submitted','approved','ordered','received','rejected'])->default('draft');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('user_name', 150)->nullable();
            $table->string('action', 100)->nullable();
            $table->string('module', 80)->nullable();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('procurement_requests');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
