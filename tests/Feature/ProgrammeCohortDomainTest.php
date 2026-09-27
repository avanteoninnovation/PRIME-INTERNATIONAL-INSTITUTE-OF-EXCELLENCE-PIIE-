<?php

namespace Tests\Feature;

use App\Models\ProgrammeCohort;
use App\Models\ProgrammeCohortMembership;
use App\Models\StudentCurriculumAssignment;
use App\Models\User;
use App\Support\ProgrammeCohorts\ProgrammeCohortService;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProgrammeCohortDomainTest extends TestCase
{
    private ProgrammeCohortService $service;
    private User $admin;
    private User $student;
    private int $programme;
    private int $intake;
    private int $year;
    private int $plan;
    private int $stage;
    private int $foreignStage;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        $this->schema();

        DB::table('schools')->insert([['id' => 1, 'school_type' => 'higher_ed'], ['id' => 2, 'school_type' => 'k12']]);
        $this->admin = User::create(['id' => 1, 'name' => 'Cohort Admin', 'email' => 'cohort-admin@test.local', 'password' => 'x', 'role_id' => 2, 'school_id' => 1, 'code' => 'CA1', 'account_status' => 'active']);
        $this->student = User::create(['id' => 2, 'name' => 'Cohort Student', 'email' => 'cohort-student@test.local', 'password' => 'x', 'role_id' => 7, 'school_id' => 1, 'code' => 'CS1', 'account_status' => 'active']);
        User::create(['id' => 3, 'name' => 'Foreign Student', 'email' => 'foreign-student@test.local', 'password' => 'x', 'role_id' => 7, 'school_id' => 2, 'code' => 'FS1', 'account_status' => 'active']);
        DB::table('programmes')->insert([['id' => 1, 'school_id' => 1, 'code' => 'P1', 'name' => 'Programme One', 'is_active' => 1], ['id' => 2, 'school_id' => 2, 'code' => 'P2', 'name' => 'Programme Two', 'is_active' => 1]]);
        DB::table('intake_sessions')->insert([['id' => 1, 'school_id' => 1, 'name' => 'Intake A'], ['id' => 2, 'school_id' => 2, 'name' => 'Intake B']]);
        DB::table('academic_years')->insert([['id' => 1, 'school_id' => 1, 'label' => '2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active'], ['id' => 2, 'school_id' => 2, 'label' => '2026B', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'status' => 'active']]);
        $this->plan = DB::table('curricula')->insertGetId(['school_id' => 1, 'programme_id' => 1, 'version' => 'v1', 'effective_academic_year_id' => 1, 'status' => 'approved']);
        $foreignPlan = DB::table('curricula')->insertGetId(['school_id' => 2, 'programme_id' => 2, 'version' => 'v1', 'effective_academic_year_id' => 2, 'status' => 'approved']);
        $this->stage = DB::table('curriculum_stages')->insertGetId(['school_id' => 1, 'curriculum_id' => $this->plan, 'label' => 'Foundation', 'sequence' => 1]);
        $this->foreignStage = DB::table('curriculum_stages')->insertGetId(['school_id' => 2, 'curriculum_id' => $foreignPlan, 'label' => 'Foreign Stage', 'sequence' => 1]);
        DB::table('student_profiles')->insert(['user_id' => 2, 'school_id' => 1, 'programme_id' => 1, 'intake_session_id' => 1, 'year_of_study' => 1, 'status' => 'active']);
        $this->programme = 1;
        $this->intake = 1;
        $this->year = 1;
        $this->service = new ProgrammeCohortService();
    }

    public function test_cohort_creation_parallel_codes_activation_lifecycle_and_delete_protection(): void
    {
        $first = $this->draft(['code' => 'COH-A']);
        $updated = $this->service->updateDraft($this->admin, $first->id, [
            'programme_id' => 1,
            'intake_session_id' => 1,
            'entry_academic_year_id' => 1,
            'curriculum_id' => $this->plan,
            'name' => 'Test Cohort Updated',
            'code' => 'COH-A',
            'expected_completion_date' => null,
        ]);
        $this->assertSame('Test Cohort Updated', $updated->name);
        $parallel = $this->draft(['code' => 'COH-B']);
        $this->assertNotSame($first->id, $parallel->id);
        $this->assertNotEmpty($this->service->suggestedCode(1, $this->programme));
        $this->expectException(ValidationException::class);
        $this->draft(['code' => 'COH-A']);
    }

    public function test_activation_requires_an_approved_matching_study_plan_and_lifecycle_is_one_way(): void
    {
        DB::table('curricula')->where('id', $this->plan)->update(['status' => 'draft']);
        $cohort = $this->draft(['code' => 'LIFE-1']);
        try {
            $this->service->transition($this->admin, $cohort->id, 'active');
            $this->fail('Draft Study Plan should not activate a cohort.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('approved', $exception->getMessage());
        }
        DB::table('curricula')->where('id', $this->plan)->update(['status' => 'approved']);
        $active = $this->service->transition($this->admin, $cohort->id, 'active');
        $this->assertSame('active', $active->status);
        $completed = $this->service->transition($this->admin, $cohort->id, 'completed');
        $this->assertSame('completed', $completed->status);
        $this->expectException(DomainException::class);
        $completed->delete();
    }

    public function test_membership_assign_defer_resume_transfer_withdraw_complete_and_history(): void
    {
        $source = $this->activeCohort('SOURCE');
        $destination = $this->activeCohort('DEST');
        $first = $this->service->assign($this->admin, $source->id, $this->student->id);
        $sourceAssignment = $this->service->placeStudent($this->admin, $source->id, $first->id, $this->stage, 2);
        try {
            $first->started_at = now()->addDay();
            $first->save();
            $this->fail('Membership start time must be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }
        try {
            $first->fresh()->delete();
            $this->fail('Membership history cannot be hard-deleted.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot be deleted', $exception->getMessage());
        }
        $deferred = $this->service->defer($this->admin, $first->id, 'Leave approved');
        $this->assertSame('deferred', $deferred->status);
        try {
            $this->service->assign($this->admin, $destination->id, $this->student->id);
            $this->fail('Deferred membership should reserve current slot.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('student_id', $exception->errors());
        }
        $resumed = $this->service->resume($this->admin, $first->id);
        $this->assertSame('active', $resumed->status);
        $new = $this->service->transfer($this->admin, $first->id, $destination->id, 'Programme change');
        $this->assertSame('transferred', $first->fresh()->status);
        $this->assertNotNull($first->fresh()->ended_at);
        $this->assertNotNull($sourceAssignment->fresh()->ended_at, 'Transfer must close the prior Study Plan assignment as history.');
        $this->assertSame('active', $new->status);
        $this->service->placeStudent($this->admin, $destination->id, $new->id, $this->stage, 2);
        $ended = $this->service->withdraw($this->admin, $new->id, 'Student withdrew');
        $this->assertSame('withdrawn', $ended->status);
        $replacement = $this->service->assign($this->admin, $source->id, $this->student->id);
        $completed = $this->service->completeMembership($this->admin, $replacement->id, 'Programme completed');
        $this->assertSame('completed', $completed->status);
        $this->assertSame(3, ProgrammeCohortMembership::where('school_id', 1)->where('student_id', $this->student->id)->whereNotNull('ended_at')->count());
    }

    public function test_academic_placement_requires_explicit_year_and_matching_entry_stage(): void
    {
        $cohort = $this->activeCohort('PLACE');
        $membership = $this->service->assign($this->admin, $cohort->id, $this->student->id);
        $otherCohort = $this->activeCohort('PLACE-OTHER');
        try {
            $this->service->placeStudent($this->admin, $cohort->id, $membership->id, $this->stage, 0);
            $this->fail('Year of Study must be explicitly selected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('year_of_study', $exception->errors());
        }
        try {
            $this->service->placeStudent($this->admin, $otherCohort->id, $membership->id, $this->stage, 1);
            $this->fail('Tampered cohort/membership IDs must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('membership_id', $exception->errors());
        }
        $assignment = $this->service->placeStudent($this->admin, $cohort->id, $membership->id, $this->stage, 3);
        $this->assertSame($membership->id, $assignment->programme_cohort_membership_id);
        $this->assertSame($this->stage, $assignment->entry_curriculum_stage_id);
        $this->assertSame(3, (int) DB::table('student_profiles')->where('user_id', $this->student->id)->value('year_of_study'));
        $this->assertSame(0, StudentCurriculumAssignment::where('entry_curriculum_stage_id', 0)->count());
        $replacement = $this->activeCohort('WRONG-STAGE');
        $secondStudent = User::create(['name' => 'Second Student', 'email' => 'second@test.local', 'password' => 'x', 'role_id' => 7, 'school_id' => 1, 'code' => 'CS2', 'account_status' => 'active']);
        DB::table('student_profiles')->insert(['user_id' => $secondStudent->id, 'school_id' => 1, 'programme_id' => 1, 'intake_session_id' => 1, 'year_of_study' => 1, 'status' => 'active']);
        $secondMembership = $this->service->assign($this->admin, $replacement->id, $secondStudent->id);
        $this->expectException(ValidationException::class);
        $this->service->placeStudent($this->admin, $replacement->id, $secondMembership->id, $this->foreignStage, 1);
    }

    public function test_pending_placement_count_tracks_only_current_unplaced_memberships(): void
    {
        $cohort = $this->activeCohort('PENDING');
        $membership = $this->service->assign($this->admin, $cohort->id, $this->student->id);

        $this->assertSame(1, $cohort->pendingPlacementMemberships()->count());

        $this->service->placeStudent($this->admin, $cohort->id, $membership->id, $this->stage, 1);
        $this->assertSame(0, $cohort->pendingPlacementMemberships()->count());

        $this->service->withdraw($this->admin, $membership->id, 'Test completed');
        $this->assertSame(0, $cohort->pendingPlacementMemberships()->count());
    }

    public function test_admission_provenance_and_tenant_tampering_are_validated(): void
    {
        $cohort = $this->activeCohort('ADMISSION');
        $admission = DB::table('admissions')->insertGetId(['school_id' => 1, 'programme_id' => 1, 'intake_session_id' => 1, 'app_number' => 'APP-TEST-1']);
        $membership = $this->service->assignFromAdmission($this->admin, $cohort->id, $this->student->id, $admission);
        $this->assertSame('APP-TEST-1', $membership->admission_reference);
        DB::table('admissions')->where('id', $admission)->delete();
        $this->assertNull($membership->fresh()->admission_id);
        $this->assertSame('APP-TEST-1', $membership->fresh()->admission_reference);
        try {
            $this->service->createDraft($this->admin, ['programme_id' => 2, 'intake_session_id' => 1, 'entry_academic_year_id' => 1, 'curriculum_id' => $this->plan, 'name' => 'Tampered', 'code' => 'TAMPER']);
            $this->fail('Cross-tenant programme ID should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('programme_id', $exception->errors());
        }
        $foreignStudent = User::findOrFail(3);
        $this->expectException(ValidationException::class);
        $this->service->assign($this->admin, $cohort->id, $foreignStudent->id);
    }

    public function test_foreign_intake_entry_year_and_study_plan_ids_are_rejected(): void
    {
        foreach ([
            ['intake_session_id' => 2, 'curriculum_id' => $this->plan],
            ['entry_academic_year_id' => 2, 'curriculum_id' => $this->plan],
            ['curriculum_id' => DB::table('curricula')->where('school_id', 2)->value('id')],
        ] as $index => $override) {
            try {
                $this->draft(array_merge(['code' => 'FOREIGN-'.$index], $override));
                $this->fail('Foreign tenant identifiers must not be accepted.');
            } catch (ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
    }

    public function test_non_admin_roles_cannot_bypass_cohort_permissions_and_k12_structure_is_unchanged(): void
    {
        $this->assertSame('class_based', \App\Models\School::findOrFail(2)->academicStructure());
        $student = User::findOrFail($this->student->id);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->service->createDraft($student, ['programme_id' => 1, 'intake_session_id' => 1, 'entry_academic_year_id' => 1, 'curriculum_id' => $this->plan, 'name' => 'Unauthorized', 'code' => 'NOPE']);
    }

    public function test_unauthorized_student_cannot_open_the_admin_cohort_routes(): void
    {
        $this->actingAs($this->student)->get(route('admin.programme_cohorts.index'))->assertRedirect();
        $teacher = User::create(['name' => 'Unprivileged Teacher', 'email' => 'teacher@test.local', 'password' => 'x', 'role_id' => 3, 'school_id' => 1, 'code' => 'T1', 'account_status' => 'active']);
        $this->actingAs($teacher)->get(route('admin.programme_cohorts.index'))->assertForbidden();
        $k12Admin = User::create(['name' => 'K12 Admin', 'email' => 'k12-admin@test.local', 'password' => 'x', 'role_id' => 2, 'school_id' => 2, 'code' => 'KA1', 'account_status' => 'active']);
        $this->actingAs($k12Admin)->get(route('admin.programme_cohorts.index'))->assertNotFound();
    }

    private function draft(array $override = []): ProgrammeCohort
    {
        return $this->service->createDraft($this->admin, array_merge([
            'programme_id' => $this->programme,
            'intake_session_id' => $this->intake,
            'entry_academic_year_id' => $this->year,
            'curriculum_id' => $this->plan,
            'name' => 'Test Cohort',
            'code' => null,
        ], $override));
    }

    private function activeCohort(string $code): ProgrammeCohort
    {
        $cohort = $this->draft(['code' => $code]);

        return $this->service->transition($this->admin, $cohort->id, 'active');
    }

    private function schema(): void
    {
        Schema::create('schools', function (Blueprint $t) { $t->id(); $t->string('school_type')->default('higher_ed'); });
        Schema::create('global_settings', function (Blueprint $t) { $t->id(); $t->string('key')->unique(); $t->text('value')->nullable(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('email')->unique(); $t->string('password'); $t->string('role_id')->nullable(); $t->integer('school_id')->nullable(); $t->string('code'); $t->string('account_status')->default('active'); $t->timestamps(); });
        Schema::create('programmes', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->string('code'); $t->string('name'); $t->boolean('is_active')->default(true); $t->unique(['school_id', 'id']); });
        Schema::create('intake_sessions', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->string('name'); $t->unique(['school_id', 'id']); });
        Schema::create('academic_years', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->string('label'); $t->date('start_date'); $t->date('end_date'); $t->string('status'); $t->unique(['school_id', 'id']); });
        Schema::create('curricula', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('programme_id'); $t->string('version'); $t->unsignedBigInteger('effective_academic_year_id')->nullable(); $t->string('status'); $t->timestamps(); $t->unique(['school_id', 'programme_id', 'id']); });
        Schema::create('curriculum_stages', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('curriculum_id'); $t->string('label'); $t->unsignedSmallInteger('sequence'); $t->timestamps(); $t->unique(['school_id', 'curriculum_id', 'id']); });
        Schema::create('admissions', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('programme_id')->nullable(); $t->unsignedBigInteger('intake_session_id')->nullable(); $t->string('app_number')->unique(); });
        Schema::create('programme_cohorts', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('programme_id'); $t->unsignedBigInteger('intake_session_id'); $t->unsignedBigInteger('entry_academic_year_id'); $t->unsignedBigInteger('curriculum_id'); $t->string('name'); $t->string('code'); $t->string('status'); $t->date('expected_completion_date')->nullable(); $t->unsignedBigInteger('created_by'); $t->timestamps(); $t->unique(['school_id', 'code']); $t->unique(['school_id', 'id']); });
        Schema::create('programme_cohort_memberships', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('student_id'); $t->unsignedBigInteger('programme_cohort_id'); $t->unsignedBigInteger('admission_id')->nullable(); $t->string('admission_reference', 30)->nullable(); $t->string('status'); $t->dateTime('started_at'); $t->dateTime('ended_at')->nullable(); $t->string('reason')->nullable(); $t->unsignedBigInteger('assigned_by'); $t->unsignedBigInteger('active_student_id')->nullable(); $t->timestamps(); $t->foreign('admission_id')->references('id')->on('admissions')->nullOnDelete(); });
        Schema::create('student_profiles', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('programme_id')->nullable(); $t->unsignedBigInteger('intake_session_id')->nullable(); $t->unsignedTinyInteger('year_of_study')->nullable(); $t->string('status')->nullable(); $t->timestamps(); });
        Schema::create('student_curriculum_assignments', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('student_id'); $t->unsignedBigInteger('programme_id'); $t->unsignedBigInteger('curriculum_id'); $t->unsignedBigInteger('entry_academic_year_id'); $t->unsignedBigInteger('effective_from_academic_year_id'); $t->unsignedBigInteger('programme_cohort_membership_id')->nullable(); $t->unsignedBigInteger('entry_curriculum_stage_id')->nullable(); $t->timestamp('ended_at')->nullable(); $t->string('reason')->nullable(); $t->unsignedBigInteger('assigned_by'); $t->timestamps(); });
        Schema::create('audit_logs', function (Blueprint $t) { $t->id(); foreach (['school_id','user_id','user_name','role_id','role_name','action','event_type','module','route_name','url','method','description','record_type','record_id','old_values','new_values','ip_address','user_agent','device_type','browser','platform','status'] as $field) $t->text($field)->nullable(); $t->timestamp('created_at')->nullable(); });
    }
}
