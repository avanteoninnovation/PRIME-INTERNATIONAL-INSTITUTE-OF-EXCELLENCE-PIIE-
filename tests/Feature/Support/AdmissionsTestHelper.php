<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait AdmissionsTestHelper
{
    protected function bootAdmissionsTestSchema(): void
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
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('code')->nullable();
            $table->longText('user_information')->nullable();
            $table->longText('student_info')->nullable();
            $table->longText('documents')->nullable();
            $table->integer('status')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('designation')->nullable();
            $table->string('language')->nullable();
            $table->unsignedInteger('school_role')->nullable();
            $table->string('account_status')->default('active');
            $table->text('menu_permission')->nullable();
            $table->boolean('force_password_change')->default(false);
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedInteger('running_session')->nullable();
            $table->timestamps();
        });

        Schema::create('enrollment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->timestamps();
        });

        Schema::create('programmes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->string('level')->default('Degree');
            $table->string('duration')->nullable();
            $table->string('mode')->nullable();
            $table->decimal('tuition_fee', 15, 2)->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });

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

        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('app_number', 30)->unique();
            $table->unsignedBigInteger('intake_session_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('dob')->nullable();
            $table->string('gender', 10)->nullable();
            $table->string('nationality', 80)->nullable();
            $table->text('qualifications')->nullable();
            $table->json('documents')->nullable();
            $table->string('status')->default('submitted');
            $table->string('source')->default('staff_entry');
            $table->date('offer_date')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->unsignedBigInteger('intake_session_id')->nullable();
            $table->unsignedTinyInteger('year_of_study')->nullable();
            $table->string('nationality', 80)->nullable();
            $table->string('national_id_or_passport', 50)->nullable();
            $table->text('next_of_kin_address')->nullable();
            $table->string('next_of_kin_contact', 30)->nullable();
            $table->string('additional_image')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('name', 150);
            $table->string('fee_type')->default('tuition');
            $table->decimal('amount', 15, 2);
            $table->tinyInteger('is_mandatory')->default(1);
            $table->tinyInteger('per_semester')->default(1);
            $table->unsignedBigInteger('class_id')->nullable();
            $table->unsignedBigInteger('programme_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->timestamps();
        });

        Schema::create('student_fee_managers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('total_amount');
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('discounted_price', 10, 2)->nullable();
            $table->integer('class_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('student_id');
            $table->unsignedBigInteger('fee_structure_id')->nullable()->index();
            $table->string('payment_method');
            $table->integer('paid_amount');
            $table->string('status');
            $table->integer('school_id');
            $table->integer('session_id')->nullable();
            $table->integer('timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('payment_keys')->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->string('mode')->nullable();
            $table->string('currency')->nullable();
            $table->string('currency_position')->nullable();
            $table->unsignedBigInteger('school_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
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

        Schema::create('language', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phrase');
            $table->text('translated')->nullable();
        });

        // Legacy messaging tables queried unconditionally by admin/navigation.blade.php
        // (the shared layout every full admin page renders through) — not
        // created by any Laravel migration in this repo (pre-dates the
        // migration-based tables), so the minimal columns the layout reads
        // are recreated here rather than left missing.
        Schema::create('message_thrades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->unsignedBigInteger('reciver_id')->nullable();
            $table->timestamps();
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_thrade')->nullable();
            $table->unsignedBigInteger('reciver_id')->nullable();
            $table->tinyInteger('read_status')->default(0);
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_title')->nullable();
        });

        // School-scoped tables the (school) Admin dashboard reads from —
        // see AdminController::schoolDashboardData().
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('session_id');
            $table->timestamps();
        });

        Schema::create('routines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->integer('starting_hour');
            $table->integer('ending_hour');
            $table->integer('starting_minute');
            $table->integer('ending_minute');
            $table->string('day');
            $table->unsignedBigInteger('teacher_id');
            $table->unsignedBigInteger('room_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('school_id');
            $table->timestamps();
        });

        Schema::create('daily_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id')->index();
            $table->unsignedBigInteger('section_id')->nullable();
            $table->unsignedBigInteger('student_id')->index();
            $table->integer('status');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('school_id')->index();
            $table->integer('timestamp')->nullable();
            $table->timestamps();
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('author');
            $table->integer('copies');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('session_id');
            $table->integer('timestamp');
            $table->timestamps();
        });

        Schema::create('book_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('student_id');
            $table->string('issue_date');
            $table->integer('status');
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('session_id');
            $table->integer('timestamp');
            $table->timestamps();
        });

        Schema::create('noticeboard', function (Blueprint $table) {
            $table->id();
            $table->longText('notice_title');
            $table->longText('notice');
            $table->string('start_date');
            $table->string('start_time');
            $table->string('end_date');
            $table->string('end_time');
            $table->integer('status');
            $table->integer('show_on_website');
            $table->string('image')->nullable();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('session_id');
            $table->timestamps();
        });

        Schema::create('frontend_events', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->integer('timestamp')->nullable();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('school_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('academic_calendar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id')->index();
            $table->string('title');
            $table->string('event_type')->default('other');
            $table->date('event_date');
            $table->date('end_date')->nullable();
            $table->string('color', 10)->default('#1a3a6b');
            $table->text('description')->nullable();
            $table->tinyInteger('is_public')->default(1);
            $table->timestamps();
        });

        // Platform-wide tables — only superadmin.dashboard reads these.
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->integer('package_id');
            $table->integer('school_id');
            $table->float('paid_amount');
            $table->string('payment_method')->nullable();
            $table->longText('transaction_keys')->nullable();
            $table->integer('expire_date')->nullable();
            $table->integer('date_added')->nullable();
            $table->integer('active')->default(0);
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('price')->default(0);
            $table->string('package_type')->nullable();
            $table->string('interval')->nullable();
            $table->integer('days')->nullable();
            $table->integer('status')->default(1);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    protected function makeSuperAdmin(int $schoolId): User
    {
        return User::factory()->create([
            'role_id' => 1,
            'school_id' => $schoolId,
            'account_status' => 'active',
        ]);
    }

    protected function makeSchool(array $overrides = []): int
    {
        return (int) DB::table('schools')->insertGetId(array_merge([
            'title' => 'Test School',
            'running_session' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    protected function makeAdminUser(int $schoolId): User
    {
        return User::factory()->create([
            'role_id' => 2,
            'school_id' => $schoolId,
            'account_status' => 'active',
        ]);
    }

    protected function makeProgramme(int $schoolId, array $overrides = []): int
    {
        return (int) DB::table('programmes')->insertGetId(array_merge([
            'school_id' => $schoolId,
            'code' => 'PRG',
            'name' => 'Test Programme',
            'level' => 'Degree',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    protected function makeIntakeSession(int $schoolId, array $overrides = []): int
    {
        return (int) DB::table('intake_sessions')->insertGetId(array_merge([
            'school_id' => $schoolId,
            'name' => 'January Intake',
            'is_open' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    protected function makeFeeStructure(int $schoolId, array $overrides = []): int
    {
        return (int) DB::table('fee_structures')->insertGetId(array_merge([
            'school_id' => $schoolId,
            'name' => 'Tuition Fee',
            'fee_type' => 'tuition',
            'amount' => 1000,
            'is_mandatory' => 1,
            'per_semester' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * Portal-activation email sending is gated on DB-stored settings
     * (get_settings('smtp_*')), not .env — seed them so tests can assert
     * the activation email guard actually lets a send through.
     */
    protected function enableSmtpSettings(): void
    {
        foreach ([
            'smtp_user'    => 'noreply@example.test',
            'smtp_pass'    => 'secret',
            'smtp_host'    => 'smtp.example.test',
            'smtp_port'    => '587',
            'system_title' => 'Test School',
        ] as $key => $value) {
            DB::table('global_settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
        }
    }

    protected function makeAdmission(int $schoolId, array $overrides = []): int
    {
        return (int) DB::table('admissions')->insertGetId(array_merge([
            'school_id' => $schoolId,
            'app_number' => 'APP-' . strtoupper(uniqid()),
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe.' . uniqid() . '@example.com',
            'phone' => '0700000000',
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
