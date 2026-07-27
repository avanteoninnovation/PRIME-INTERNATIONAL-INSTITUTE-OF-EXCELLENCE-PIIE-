<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait LeaveTestHelper
{
    protected function bootLeaveTestSchema(): void
    {
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string('remember_token', 100)->nullable();
            $table->unsignedInteger('role_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('language')->nullable();
            $table->string('account_status')->default('active');
            $table->text('menu_permission')->nullable();
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 100);
            $table->integer('max_days')->default(0);
            $table->tinyInteger('is_paid')->default(1);
            $table->timestamps();
        });

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
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->text('admin_comment')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->unsignedTinyInteger('role_id')->nullable();
            $table->string('role_name', 60)->nullable();
            $table->string('action');
            $table->string('event_type', 20)->default('ACTION');
            $table->string('module');
            $table->string('route_name', 150)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('method', 10)->nullable();
            $table->text('description');
            $table->string('record_type', 100)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('platform', 60)->nullable();
            $table->string('status', 20)->nullable();
            $table->dateTime('created_at')->nullable();
        });

        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('language', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phrase');
            $table->text('translated')->nullable();
        });

        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('unique_identifier')->unique();
            $table->string('status')->default('0');
            $table->timestamps();
        });

        Schema::create('message_thrades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('reciver_id')->nullable();
            $table->timestamps();
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_thrade')->nullable();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('reciver_id')->nullable();
            $table->tinyInteger('read_status')->default(0);
            $table->text('message')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_title')->nullable();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamps();
        });

        DB::table('schools')->insert([
            'id' => 1,
            'title' => 'Test School',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeUser(int $roleId, int $schoolId, string $status = 'active'): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'school_id' => $schoolId,
            'account_status' => $status,
            'menu_permission' => null,
        ]);
    }

    protected function makeLeaveType(int $schoolId, string $name = 'Annual Leave', int $maxDays = 21): int
    {
        return (int) DB::table('leave_types')->insertGetId([
            'school_id' => $schoolId,
            'name' => $name,
            'max_days' => $maxDays,
            'is_paid' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeLeave(array $overrides = []): int
    {
        $defaults = [
            'school_id' => 1,
            'user_id' => 1,
            'leave_type_id' => null,
            'leave_type' => 'Annual Leave',
            'from_date' => now()->toDateString(),
            'to_date' => now()->addDays(2)->toDateString(),
            'days' => 3,
            'reason' => 'Family event',
            'status' => 'pending',
            'approved_by' => null,
            'admin_comment' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        return (int) DB::table('leavelists')->insertGetId(array_merge($defaults, $overrides));
    }
}
