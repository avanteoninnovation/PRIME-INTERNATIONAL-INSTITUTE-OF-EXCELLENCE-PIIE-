<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\User;
use App\Support\CourseOffering\CourseOfferingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

class CourseOfferingAdministrationTest extends TestCase
{
    use StaffModuleTestHelper;

    private array $tenants;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('school_type')->default('k12');
            $table->string('academic_calendar_pattern')->nullable();
            $table->unsignedBigInteger('current_academic_year_id')->nullable();
            $table->unsignedBigInteger('current_academic_period_id')->nullable();
        });
        Schema::create('user_permissions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('user_id');
            $table->string('permission', 100); $table->unsignedBigInteger('granted_by')->nullable(); $table->timestamps();
            $table->unique(['user_id', 'permission']);
        });
        Schema::create('staff_roles', function (Blueprint $table): void { $table->id(); $table->unsignedBigInteger('school_id'); $table->string('name'); $table->string('description')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable(); $table->timestamps(); });
        Schema::create('staff_role_permissions', function (Blueprint $table): void { $table->id(); $table->unsignedBigInteger('staff_role_id'); $table->string('permission',100); $table->timestamps(); });
        Schema::create('user_staff_roles', function (Blueprint $table): void { $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('staff_role_id'); $table->unsignedBigInteger('assigned_by')->nullable(); $table->timestamps(); });
        $this->createAcademicTables();
        Schema::create('live_classes', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('course_offering_id')->nullable(); $table->unsignedBigInteger('teacher_id')->nullable();
            $table->string('title'); $table->text('description')->nullable(); $table->string('platform')->nullable();
            $table->text('meeting_url')->nullable(); $table->string('meeting_id')->nullable(); $table->string('meeting_password')->nullable();
            $table->dateTime('scheduled_at')->nullable(); $table->dateTime('ends_at')->nullable(); $table->string('timezone')->nullable();
            $table->date('start_date')->nullable(); $table->time('start_time')->nullable(); $table->time('end_time')->nullable();
            $table->string('status')->default('draft'); $table->boolean('is_published')->default(false); $table->boolean('attendance_enabled')->default(true);
            $table->text('recording_url')->nullable(); $table->unsignedBigInteger('created_by')->nullable(); $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
        Schema::create('live_class_materials', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('live_class_id');
            $table->string('type')->default('file'); $table->string('category')->default('resource'); $table->string('title');
            $table->string('original_name')->nullable(); $table->string('stored_name')->nullable(); $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable(); $table->text('link_url')->nullable(); $table->unsignedBigInteger('uploaded_by')->nullable(); $table->timestamps();
        });
        Schema::create('course_offering_lecturer_allocations', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('course_offering_id');
            $table->unsignedBigInteger('user_id'); $table->string('role', 32); $table->date('starts_on');
            $table->date('ends_on')->nullable(); $table->string('status', 16)->default('planned'); $table->timestamps();
        });
        Schema::create('staff_profiles', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id'); $table->unsignedBigInteger('school_id');
            $table->string('academic_title')->nullable(); $table->string('specialisation')->nullable(); $table->timestamps();
        });
        $this->tenants = [1 => $this->tenant(1, 'higher_ed'), 2 => $this->tenant(2, 'higher_ed')];
    }

    public function test_view_routes_are_authorized_tenant_scoped_and_menu_permission_does_not_grant_access(): void
    {
        $a = $this->tenants[1];
        $draft = $this->createOffering($a, 'A-OWN');
        $generatedDraft = app(CourseOfferingService::class)->createDraft(1, $a['subject'], $a['year'], $a['period']);
        $foreign = $this->createOffering($this->tenants[2], 'B-FOREIGN');
        $viewer = $this->staff(1, ['academic.course_offering.view', 'academic.course_offering.lecturer.view', 'academic.course_registration.view']);

        $this->actingAs($viewer)->get(route('admin.course_offerings.index'))->assertOk()->assertSee('A-OWN')->assertDontSee('B-FOREIGN');
        $this->get(route('admin.course_offerings.show', $draft->id))->assertOk()
            ->assertSee('A-OWN')->assertSee('CORE-1 — Core 1')
            ->assertSee('Offering Reference:')->assertSee('Academic Year:')->assertSee('2026')
            ->assertSee('Semester:')->assertSee('Semester 1')
            ->assertSee('No Programme Study Plans have been linked to this Course Offering yet.')
            ->assertSee('No lecturers have been assigned yet.')
            ->assertSee('No students are registered for this Course Offering yet.')
            ->assertSee('A Programme Study Plan must be linked before this Course Offering can be opened.')
            ->assertSee('View Teaching Team')
            ->assertSee(route('admin.course_offerings.lecturers.index', $draft->id), false)
            ->assertSee(route('admin.course_offerings.eligible_students', $draft->id), false);
        $this->get(route('admin.course_offerings.show', $generatedDraft->id))->assertOk()
            ->assertSee('CORE-1-2026-S1')->assertSee('Offering Reference:');
        $this->get(route('admin.course_offerings.show', $foreign->id))->assertNotFound();

        $ungranted = $this->staff(1, [], ['menu_permission' => json_encode(['admin.course_offerings.index'])]);
        $this->actingAs($ungranted)->get(route('admin.course_offerings.index'))->assertForbidden();
        $this->get(route('admin.course_offerings.show', $draft->id))->assertForbidden();
    }

    public function test_manage_and_lifecycle_permissions_are_enforced_on_real_mutation_routes(): void
    {
        $a = $this->tenants[1];
        $draft = $this->createOffering($a, 'DRAFT-A');
        $open = $this->createOffering($a, 'OPEN-A');
        $this->attach($a, $open, $a['member']);
        app(CourseOfferingService::class)->open(1, $open->id);
        $inProgress = $this->createOffering($a, 'IP-A');
        $this->attach($a, $inProgress, $a['member']);
        app(CourseOfferingService::class)->open(1, $inProgress->id);
        app(CourseOfferingService::class)->start(1, $inProgress->id);
        $memberB = $this->tenants[2]['member'];
        $viewOnly = $this->staff(1, ['academic.course_offering.view']);

        $this->actingAs($viewOnly)->post(route('admin.course_offerings.store'), $this->draftPayload($a))->assertForbidden();
        $this->put(route('admin.course_offerings.update', $draft->id), $this->draftPayload($a, 'EDITED'))->assertForbidden();
        $this->post(route('admin.course_offerings.applicability.store', $draft->id), ['curriculum_membership_id' => $a['member']])->assertForbidden();
        $this->delete(route('admin.course_offerings.applicability.destroy', [$draft->id, $a['member']]))->assertForbidden();
        foreach ([['open', $draft], ['start', $open], ['complete', $inProgress], ['cancel', $draft]] as [$action, $offering]) {
            $this->post(route('admin.course_offerings.'.$action, $offering->id), ['reason' => 'testing'])->assertForbidden();
        }

        $manager = $this->staff(1, ['academic.course_offering.manage']);
        $this->actingAs($manager)->post(route('admin.course_offerings.store'), $this->draftPayload($a))->assertRedirect();
        $this->put(route('admin.course_offerings.update', $draft->id), $this->draftPayload($a, 'EDITED'))->assertRedirect();
        $this->post(route('admin.course_offerings.applicability.store', $draft->id), ['curriculum_membership_id' => $memberB])->assertSessionHasErrors('curriculum_membership_id');
    }

    public function test_cross_tenant_offering_mutations_and_membership_attachment_are_rejected(): void
    {
        $b = $this->tenants[2];
        $foreign = $this->createOffering($b, 'B-OFFER');
        $manager = $this->staff(1, ['academic.course_offering.manage', 'academic.course_offering.lifecycle']);
        $this->actingAs($manager);
        $this->put(route('admin.course_offerings.update', $foreign->id), $this->draftPayload($this->tenants[1], 'HACK'))->assertNotFound();
        $this->post(route('admin.course_offerings.applicability.store', $foreign->id), ['curriculum_membership_id' => $this->tenants[1]['member']])->assertNotFound();
        $this->delete(route('admin.course_offerings.applicability.destroy', [$foreign->id, $b['member']]))->assertNotFound();
        foreach (['open', 'start', 'complete', 'cancel'] as $action) {
            $this->post(route('admin.course_offerings.'.$action, $foreign->id), ['reason' => 'wrong tenant'])->assertNotFound();
        }

        $offeringA = $this->createOffering($this->tenants[1], 'A-OFFER');
        $this->post(route('admin.course_offerings.applicability.store', $offeringA->id), ['curriculum_membership_id' => $b['member']])->assertSessionHasErrors('curriculum_membership_id');
        $this->assertDatabaseMissing('course_offering_curriculum_memberships', ['school_id' => 1, 'course_offering_id' => $offeringA->id, 'curriculum_membership_id' => $b['member']]);
    }

    public function test_lecturer_http_workflows_tenant_isolation_eligibility_and_primary_conflicts(): void
    {
        $tenantA = $this->tenants[1];
        $tenantB = $this->tenants[2];
        $offeringA = $this->createOffering($tenantA, 'LECTURER-A');
        $offeringB = $this->createOffering($tenantB, 'LECTURER-B');
        $manager = $this->staff(1, ['academic.course_offering.lecturer.view', 'academic.course_offering.lecturer.manage']);
        $teacherA = User::factory()->create(['name' => 'Lecturer A', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'active']);
        $onLeave = User::factory()->create(['name' => 'Lecturer On Leave', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'on_leave']);
        $suspended = User::factory()->create(['name' => 'Lecturer Suspended', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'suspended']);
        User::factory()->create(['name' => 'Lecturer Inactive', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'inactive']);
        User::factory()->create(['name' => 'Lecturer Terminated', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'terminated']);
        User::factory()->create(['name' => 'Lecturer Disabled', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'disable', 'staff_status' => 'active']);
        $nonTeacher = User::factory()->create(['name' => 'Non Teacher', 'role_id' => 4, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'active']);
        $teacherB = User::factory()->create(['name' => 'Lecturer B', 'role_id' => 3, 'school_id' => $tenantB['school'], 'account_status' => 'active', 'staff_status' => 'active']);
        $replacement = User::factory()->create(['name' => 'Replacement Lecturer', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'active']);
        $viewer = $this->staff(1, ['academic.course_offering.lecturer.view']);
        $this->actingAs($viewer)->get(route('admin.course_offerings.lecturers.index', $offeringA->id))->assertOk();
        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $teacherA->id, 'role' => 'co_lecturer', 'starts_on' => '2026-02-01',
        ])->assertForbidden();
        $this->actingAs($this->staff(1, []))->get(route('admin.course_offerings.lecturers.index', $offeringA->id))->assertForbidden();

        $foreignAllocation = (int) DB::table('course_offering_lecturer_allocations')->insertGetId([
            'school_id' => $tenantB['school'], 'course_offering_id' => $offeringB->id, 'user_id' => $teacherB->id,
            'role' => 'primary_lecturer', 'starts_on' => '2026-02-01', 'ends_on' => '2026-02-28',
            'status' => 'planned', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('audit_logs')->insert([
            'school_id' => $tenantB['school'], 'user_id' => $manager->id, 'user_name' => 'Tenant B Actor',
            'action' => 'COURSE_OFFERING_LECTURER_ALLOCATION_CREATED', 'event_type' => 'COURSE_OFFERING_LECTURER_ALLOCATION',
            'module' => 'Course Offering Lecturer Allocations', 'description' => 'Foreign tenant audit fixture',
            'record_type' => \App\Models\CourseOfferingLecturerAllocation::class, 'record_id' => $foreignAllocation,
            'new_values' => json_encode(['user_id' => $teacherB->id, 'role' => 'primary_lecturer', 'starts_on' => '2026-02-01', 'status' => 'planned']),
            'created_at' => now(),
        ]);
        $this->actingAs($manager);

        $this->get(route('admin.course_offerings.lecturers.index', $offeringB->id))->assertNotFound();
        $this->get(route('admin.course_offerings.lecturers.history', $offeringB->id))->assertNotFound();
        $this->get(route('admin.course_offerings.lecturers.create', $offeringB->id))->assertNotFound();
        $this->post(route('admin.course_offerings.lecturers.store', $offeringB->id), [
            'user_id' => $teacherA->id, 'role' => 'co_lecturer', 'starts_on' => '2026-02-01',
        ])->assertNotFound();
        $this->put(route('admin.course_offerings.lecturers.update', [$offeringB->id, $foreignAllocation]), [
            'user_id' => $teacherB->id, 'role' => 'co_lecturer', 'starts_on' => '2026-02-01',
        ])->assertNotFound();
        foreach (['activate', 'end', 'cancel', 'replace'] as $action) {
            $payload = match ($action) {
                'end' => ['ends_on' => '2026-02-20'],
                'cancel' => ['reason' => 'Withdrawn'],
                'replace' => ['user_id' => $teacherA->id, 'role' => 'primary_lecturer', 'old_ends_on' => '2026-02-15', 'starts_on' => '2026-02-16'],
                default => [],
            };
            $this->post(route('admin.course_offerings.lecturers.'.$action, [$offeringB->id, $foreignAllocation]), $payload)->assertNotFound();
        }
        $this->put(route('admin.course_offerings.lecturers.update', [$offeringA->id, $foreignAllocation]), [
            'user_id' => $teacherA->id, 'role' => 'co_lecturer', 'starts_on' => '2026-02-01',
        ])->assertNotFound();
        foreach (['activate', 'end', 'cancel', 'replace'] as $action) {
            $payload = match ($action) {
                'end' => ['ends_on' => '2026-02-20'],
                'cancel' => ['reason' => 'Wrong Offering'],
                'replace' => ['user_id' => $teacherA->id, 'role' => 'primary_lecturer', 'old_ends_on' => '2026-02-15', 'starts_on' => '2026-02-16'],
                default => [],
            };
            $this->post(route('admin.course_offerings.lecturers.'.$action, [$offeringA->id, $foreignAllocation]), $payload)->assertNotFound();
        }
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $foreignAllocation, 'school_id' => $tenantB['school'], 'status' => 'planned']);

        $createPage = $this->get(route('admin.course_offerings.lecturers.create', $offeringA->id));
        $createPage->assertOk()->assertSee('Core 1')->assertSee('2026')->assertSee('Semester 1')
            ->assertSee('LECTURER-A')->assertSee('Draft')->assertSee('Lecturer A')->assertSee('On leave — planned only')
            ->assertDontSee('Lecturer Suspended')->assertDontSee('Lecturer Inactive')->assertDontSee('Lecturer Terminated')
            ->assertDontSee('Lecturer Disabled')->assertDontSee('Non Teacher')->assertDontSee('Lecturer B');

        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $teacherB->id, 'role' => 'co_lecturer', 'starts_on' => '2026-02-01',
        ])->assertSessionHasErrors('allocation');
        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $onLeave->id, 'role' => 'co_lecturer', 'starts_on' => '2026-02-01', 'ends_on' => '2026-02-20',
        ])->assertRedirect();
        $plannedOnLeave = DB::table('course_offering_lecturer_allocations')->where('course_offering_id', $offeringA->id)->where('user_id', $onLeave->id)->first();
        $this->assertSame('planned', $plannedOnLeave->status);
        $this->post(route('admin.course_offerings.lecturers.activate', [$offeringA->id, $plannedOnLeave->id]))->assertSessionHasErrors('allocation');
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $plannedOnLeave->id, 'status' => 'planned']);

        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $teacherA->id, 'role' => 'co_lecturer', 'starts_on' => '2026-02-01', 'ends_on' => '2026-02-28',
        ])->assertRedirect();
        $planned = DB::table('course_offering_lecturer_allocations')->where('course_offering_id', $offeringA->id)->where('user_id', $teacherA->id)->first();
        $this->put(route('admin.course_offerings.lecturers.update', [$offeringA->id, $planned->id]), [
            'user_id' => $teacherA->id, 'role' => 'co_lecturer', 'starts_on' => '2026-03-01', 'ends_on' => '2026-03-20',
        ])->assertRedirect();
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $planned->id, 'starts_on' => '2026-03-01', 'status' => 'planned']);
        $this->post(route('admin.course_offerings.lecturers.activate', [$offeringA->id, $planned->id]))->assertSessionHasErrors('allocation');

        $this->attach($tenantA, $offeringA, $tenantA['member']);
        app(CourseOfferingService::class)->open($tenantA['school'], $offeringA->id);
        $this->post(route('admin.course_offerings.lecturers.activate', [$offeringA->id, $planned->id]))->assertRedirect();
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $planned->id, 'status' => 'active']);
        $this->post(route('admin.course_offerings.lecturers.end', [$offeringA->id, $planned->id]), ['ends_on' => '2026-03-15'])->assertRedirect();
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $planned->id, 'status' => 'ended', 'ends_on' => '2026-03-15']);
        $this->post(route('admin.course_offerings.lecturers.cancel', [$offeringA->id, $plannedOnLeave->id]), ['reason' => 'Staffing plan withdrawn'])->assertRedirect();
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $plannedOnLeave->id, 'status' => 'cancelled', 'ends_on' => '2026-02-20']);

        $primary = (int) DB::table('course_offering_lecturer_allocations')->insertGetId([
            'school_id' => $tenantA['school'], 'course_offering_id' => $offeringA->id, 'user_id' => $teacherA->id,
            'role' => 'primary_lecturer', 'starts_on' => '2026-04-01', 'ends_on' => '2026-04-10',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $replacement->id, 'role' => 'primary_lecturer', 'starts_on' => '2026-04-05', 'ends_on' => '2026-04-15',
        ])->assertSessionHasErrors('allocation');
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $primary, 'status' => 'active', 'ends_on' => '2026-04-10']);
        $this->get(route('admin.course_offerings.lecturers.index', $offeringA->id))
            ->assertOk()->assertSee('Primary Lecturer conflict: Lecturer A')
            ->assertSee('Use Replace Primary Lecturer to preserve the existing history.');

        $this->post(route('admin.course_offerings.lecturers.replace', [$offeringA->id, $primary]), [
            'user_id' => $replacement->id, 'role' => 'primary_lecturer', 'old_ends_on' => '2026-04-30', 'starts_on' => '2026-05-01',
        ])->assertRedirect();
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $primary, 'user_id' => $teacherA->id, 'status' => 'ended', 'ends_on' => '2026-04-30']);
        $replacementAllocation = DB::table('course_offering_lecturer_allocations')->where('course_offering_id', $offeringA->id)->where('user_id', $replacement->id)->first();
        $this->assertSame('planned', $replacementAllocation->status);
        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $teacherA->id, 'role' => 'primary_lecturer', 'starts_on' => '2026-05-02', 'ends_on' => '2026-05-10',
        ])->assertSessionHasErrors('allocation');
        $this->get(route('admin.course_offerings.lecturers.index', $offeringA->id))
            ->assertOk()->assertSee('Cancel the conflicting planned Primary Lecturer allocation first.');
        $this->post(route('admin.course_offerings.lecturers.activate', [$offeringA->id, $replacementAllocation->id]))->assertRedirect();
        $this->post(route('admin.course_offerings.lecturers.cancel', [$offeringA->id, $replacementAllocation->id]), ['reason' => 'Replacement assignment withdrawn'])->assertRedirect();
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $replacementAllocation->id, 'status' => 'cancelled', 'starts_on' => '2026-05-01']);
        $openLecturer = User::factory()->create(['name' => 'Open State Lecturer', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'active']);
        $progressLecturer = User::factory()->create(['name' => 'In Progress Lecturer', 'role_id' => 3, 'school_id' => $tenantA['school'], 'account_status' => 'active', 'staff_status' => 'active']);
        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $openLecturer->id, 'role' => 'lab_instructor', 'starts_on' => '2026-05-10', 'ends_on' => '2026-05-15',
        ])->assertRedirect();
        $openAllocation = DB::table('course_offering_lecturer_allocations')->where('course_offering_id', $offeringA->id)->where('user_id', $openLecturer->id)->first();
        $this->post(route('admin.course_offerings.lecturers.activate', [$offeringA->id, $openAllocation->id]))->assertRedirect();
        $this->post(route('admin.course_offerings.lecturers.store', $offeringA->id), [
            'user_id' => $progressLecturer->id, 'role' => 'teaching_assistant', 'starts_on' => '2026-05-20', 'ends_on' => '2026-05-25',
        ])->assertRedirect();
        $progressAllocation = DB::table('course_offering_lecturer_allocations')->where('course_offering_id', $offeringA->id)->where('user_id', $progressLecturer->id)->first();
        app(CourseOfferingService::class)->start($tenantA['school'], $offeringA->id);
        $this->post(route('admin.course_offerings.lecturers.activate', [$offeringA->id, $progressAllocation->id]))->assertRedirect();
        $this->assertDatabaseHas('course_offering_lecturer_allocations', ['id' => $progressAllocation->id, 'status' => 'active']);
        DB::table('users')->where('id', $teacherA->id)->update(['staff_status' => 'terminated']);

        $history = $this->get(route('admin.course_offerings.lecturers.history', $offeringA->id));
        $history->assertOk()->assertSee('Lecturer assigned')->assertSee('Planned allocation updated')->assertSee('Allocation activated')
            ->assertSee('Allocation ended')->assertSee('Allocation cancelled')->assertSee('Lecturer replaced')
            ->assertSee('Staffing plan withdrawn')->assertSee('Replacement assignment withdrawn')->assertSee('Lecturer A')
            ->assertSee($manager->name)->assertSee(now()->format('Y-m-d'))
            ->assertDontSee('Lecturer B')->assertDontSee('Tenant B Actor');

        app(CourseOfferingService::class)->complete($tenantA['school'], $offeringA->id);
        $this->get(route('admin.course_offerings.lecturers.index', $offeringA->id))
            ->assertOk()->assertSee('This Offering is complete. Lecturer allocations are retained as history.')
            ->assertDontSee('Assign Lecturer')->assertDontSee('Activate');
        $cancelledOffering = $this->createOffering($tenantA, 'LECTURER-CANCELLED');
        app(CourseOfferingService::class)->cancel($tenantA['school'], $cancelledOffering->id, 'Offering withdrawn');
        $this->get(route('admin.course_offerings.lecturers.index', $cancelledOffering->id))
            ->assertOk()->assertSee('This Offering is cancelled. Lecturer allocations are read-only.')
            ->assertDontSee('Assign Lecturer');
    }

    public function test_lecturer_create_page_loads_without_optional_staff_profiles_table(): void
    {
        $tenant = $this->tenants[1];
        $offering = $this->createOffering($tenant, 'TEACHING-TEAM-CONTEXT');
        $manager = $this->staff(1, ['academic.course_offering.lecturer.view', 'academic.course_offering.lecturer.manage']);
        $lecturer = User::factory()->create([
            'name' => 'Eligible Lecturer Without Profile', 'role_id' => 3,
            'school_id' => $tenant['school'], 'account_status' => 'active', 'staff_status' => 'active',
        ]);
        Schema::drop('staff_profiles');

        $this->actingAs($manager)->get(route('admin.course_offerings.lecturers.index', $offering->id))
            ->assertOk()
            ->assertSee('Teaching Team')
            ->assertSee('CORE-1 — Core 1')
            ->assertSee('2026')
            ->assertSee('Semester 1')
            ->assertSee('TEACHING-TEAM-CONTEXT')
            ->assertSee('No teaching team members are assigned to this Course Offering yet.')
            ->assertSee('Assign Lecturer')
            ->assertSee('Back to Course Offering');

        $this->get(route('admin.course_offerings.lecturers.create', $offering->id))
            ->assertOk()
            ->assertSee('Assign Lecturer')
            ->assertSee('Eligible Lecturer Without Profile')
            ->assertSee('Choose a lecturer')
            ->assertSee('Teaching Role')
            ->assertSee('Primary Lecturer')
            ->assertSee('Co-Lecturer')
            ->assertSee('Teaching Assistant')
            ->assertSee('Lab Instructor')
            ->assertSee('Guest Lecturer')
            ->assertSee('Starts On')
            ->assertSee('Ends On')
            ->assertSee('Back to Teaching Team')
            ->assertSee('2026')
            ->assertSee('Semester 1')
            ->assertSee('TEACHING-TEAM-CONTEXT')
            ->assertSee('Eligible Lecturer Without Profile')
            ->assertSee('Offering Reference:')
            ->assertSee('CORE-1 — Core 1');
    }

    public function test_index_filters_are_tenant_scoped_and_paginates_without_foreign_rows(): void
    {
        $a = $this->tenants[1]; $b = $this->tenants[2];
        $ids = [];
        for ($i = 1; $i <= 27; $i++) $ids[] = $this->createOffering($a, 'A-REF-'.$i, $i === 1 ? $a['subject'] : $this->subject(1, 'Course Unit '.$i, 'CU-'.$i))->id;
        $foreign = $this->createOffering($b, 'B-REF-ONLY');
        $viewer = $this->staff(1, ['academic.course_offering.view']);
        $this->actingAs($viewer);
        $page1 = $this->get(route('admin.course_offerings.index'));
        $page1->assertOk()->assertSee('A-REF-1')->assertDontSee('B-REF-ONLY')->assertSee('page=2');
        $page2 = $this->get(route('admin.course_offerings.index', ['page' => 2]));
        $page2->assertOk()->assertDontSee('B-REF-ONLY');
        $this->assertNotSame([], $ids);

        $queryCases = [
            ['year_id' => $a['year']], ['period_id' => $a['period']], ['status' => 'draft'],
            ['reference' => 'A-REF-1'], ['search' => 'CU-1'],
            ['subject_id' => $a['subject']], ['subject_id' => $a['subject'], 'year_id' => $a['year'], 'period_id' => $a['period']],
            ['programme_id' => $a['programme']], ['curriculum_id' => $a['curriculum']], ['department_id' => $a['department']],
            ['programme_id' => $b['programme']], ['curriculum_id' => $b['curriculum']], ['department_id' => $b['department']],
            ['year_id' => $b['year']], ['period_id' => $b['period']], ['subject_id' => $b['subject']],
        ];
        $filterFixtureKeys = [
            'programme_id' => 'programme',
            'curriculum_id' => 'curriculum',
            'department_id' => 'department',
            'year_id' => 'year',
            'period_id' => 'period',
            'subject_id' => 'subject',
        ];
        foreach ($queryCases as $query) {
            $response = $this->get(route('admin.course_offerings.index', $query));
            $response->assertOk()->assertDontSee('B-REF-ONLY');
            $filterName = array_key_first($query);
            $fixtureKey = $filterFixtureKeys[$filterName] ?? null;
            if ($fixtureKey !== null && $query[$filterName] === $b[$fixtureKey] && $b[$fixtureKey] !== $a[$fixtureKey]) {
                $response->assertDontSee('A-REF-1');
            }
        }
        $combined = $this->get(route('admin.course_offerings.index', [
            'subject_id' => $a['subject'], 'year_id' => $a['year'], 'period_id' => $a['period'], 'search' => 'CORE-1',
        ]));
        $combined->assertOk()->assertSee('A-REF-1')->assertDontSee('A-REF-2')->assertDontSee('B-REF-ONLY');
        $page1->assertSee('offering-course-unit')->assertSee('CORE-1 — Core 1');
        $this->assertNotSame($foreign->id, $ids[0]);
    }

    public function test_index_presentation_uses_tenant_terms_and_distinguishes_empty_states(): void
    {
        $tenant = $this->tenants[1];
        $manager = $this->staff(1, ['academic.course_offering.view', 'academic.course_offering.manage']);
        $this->actingAs($manager);

        $empty = $this->get(route('admin.course_offerings.index'));
        $empty->assertOk()
            ->assertSee('Manage the Course Units being taught in each Academic Year and Semester, including teaching teams and student registration.')
            ->assertSee('Create Course Offering')
            ->assertSee('All Programme Study Plans')
            ->assertSee('Applicable Programme Study Plans')
            ->assertSee('Semester')
            ->assertSee('No Course Offerings have been created yet.');

        $this->createOffering($tenant, 'INDEX-EMPTY-STATE');
        $filtered = $this->get(route('admin.course_offerings.index', ['search' => 'no-such-course-unit']));
        $filtered->assertOk()->assertSee('No Course Offerings match these filters.')->assertSee('Clear filters');
        $this->assertSame(1, substr_count($filtered->getContent(), 'No Course Offerings match these filters.'));

        DB::table('schools')->where('id', $tenant['school'])->update(['school_type' => 'k12', 'academic_calendar_pattern' => 'term']);
        $k12Label = $this->get(route('admin.course_offerings.index', ['search' => 'no-such-course-unit']));
        $k12Label->assertOk()->assertSee('Academic Year and Term, including teaching teams and student registration.')
            ->assertSee('<label class="form-label" for="offering-period">Term</label>', false);
    }

    public function test_draft_endpoints_applicability_readiness_lifecycle_and_cancellation(): void
    {
        $a = $this->tenants[1];
        $manager = $this->staff(1, ['academic.course_offering.manage', 'academic.course_offering.lifecycle']);
        $this->actingAs($manager);

        $this->post(route('admin.course_offerings.store'), $this->draftPayload($a, 'CLIENT-SUPPLIED-REFERENCE'))->assertRedirect();
        $created = CourseOffering::where('school_id', 1)->latest('id')->firstOrFail();
        $this->assertSame('draft', $created->status);
        $this->assertSame('CORE-1-2026-S1', $created->reference);
        $this->assertNotSame('CLIENT-SUPPLIED-REFERENCE', $created->reference);
        $this->assertDatabaseMissing('course_offerings', ['id' => $created->id, 'programme_id' => $a['programme']]);

        $this->put(route('admin.course_offerings.update', $created->id), $this->draftPayload($a, 'UPDATED'))->assertRedirect();
        $this->assertSame('UPDATED', $created->fresh()->reference);
        $this->post(route('admin.course_offerings.open', $created->id))->assertSessionHasErrors('lifecycle');
        $this->post(route('admin.course_offerings.applicability.store', $created->id), ['curriculum_membership_id' => $a['member']])->assertRedirect();
        $this->assertDatabaseHas('course_offering_curriculum_memberships', ['school_id' => 1, 'course_offering_id' => $created->id, 'curriculum_membership_id' => $a['member']]);
        $this->delete(route('admin.course_offerings.applicability.destroy', [$created->id, $a['member']]))->assertRedirect();
        $this->post(route('admin.course_offerings.applicability.store', $created->id), ['curriculum_membership_id' => $a['member']])->assertRedirect();
        $this->post(route('admin.course_offerings.open', $created->id))->assertRedirect();
        $this->assertSame('open', $created->fresh()->status);
        $this->put(route('admin.course_offerings.update', $created->id), $this->draftPayload($a, 'NO'))->assertSessionHasErrors('offering');
        $this->post(route('admin.course_offerings.start', $created->id))->assertRedirect();
        $this->assertSame('in_progress', $created->fresh()->status);
        $this->post(route('admin.course_offerings.complete', $created->id))->assertRedirect();
        $this->assertSame('completed', $created->fresh()->status);
        $this->post(route('admin.course_offerings.start', $created->id))->assertSessionHasErrors('lifecycle');
        $this->put(route('admin.course_offerings.update', $created->id), $this->draftPayload($a, 'NO'))->assertSessionHasErrors('offering');

        $this->post(route('admin.course_offerings.store'), $this->draftPayload($a))->assertRedirect();
        $cancelDraft = CourseOffering::where('school_id', 1)->latest('id')->firstOrFail();
        $this->post(route('admin.course_offerings.cancel', $cancelDraft->id), ['reason' => '   '])->assertSessionHasErrors('reason');
        $this->post(route('admin.course_offerings.cancel', $cancelDraft->id), ['reason' => 'No longer scheduled'])->assertRedirect();
        $this->assertSame('cancelled', $cancelDraft->fresh()->status);
        $this->post(route('admin.course_offerings.start', $cancelDraft->id))->assertSessionHasErrors('lifecycle');

        foreach (['open', 'in_progress'] as $state) {
            $cancel = $this->createOffering($a, 'CANCEL-'.$state);
            $this->attach($a, $cancel, $a['member']);
            app(CourseOfferingService::class)->open(1, $cancel->id);
            if ($state === 'in_progress') app(CourseOfferingService::class)->start(1, $cancel->id);
            $this->post(route('admin.course_offerings.cancel', $cancel->id), ['reason' => 'Institutional change'])->assertRedirect();
            $this->assertSame('cancelled', $cancel->fresh()->status);
        }
    }

    public function test_applicability_http_rejects_same_tenant_wrong_subject_membership(): void
    {
        $tenant = $this->tenants[1];
        $otherSubject = $this->subject($tenant['school'], 'Other Unit', 'OTHER-UNIT');
        $membership = $this->createMembership($tenant, $tenant['curriculum'], $otherSubject, 'semester', 1);
        $offering = $this->createOffering($tenant, 'WRONG-SUBJECT');
        $manager = $this->staff(1, ['academic.course_offering.manage']);

        $this->actingAs($manager)
            ->post(route('admin.course_offerings.applicability.store', $offering->id), ['curriculum_membership_id' => $membership])
            ->assertSessionHasErrors('applicability');
        $this->assertDatabaseMissing('course_offering_curriculum_memberships', [
            'school_id' => $tenant['school'], 'course_offering_id' => $offering->id, 'curriculum_membership_id' => $membership,
        ]);
    }

    public function test_applicability_http_rejects_membership_from_draft_curriculum(): void
    {
        $tenant = $this->tenants[1];
        $draftCurriculum = $this->curriculumFor($tenant, 'draft', 'draft-applicability');
        $membership = $this->createMembership($tenant, $draftCurriculum, $tenant['subject'], 'semester', 1);
        $offering = $this->createOffering($tenant, 'DRAFT-CURRICULUM');
        $manager = $this->staff(1, ['academic.course_offering.manage']);

        $this->actingAs($manager)
            ->post(route('admin.course_offerings.applicability.store', $offering->id), ['curriculum_membership_id' => $membership])
            ->assertSessionHasErrors('applicability');
        $this->assertDatabaseMissing('course_offering_curriculum_memberships', [
            'school_id' => $tenant['school'], 'course_offering_id' => $offering->id, 'curriculum_membership_id' => $membership,
        ]);
    }

    public function test_applicability_http_rejects_incompatible_period_membership(): void
    {
        $tenant = $this->tenants[1];
        $membership = $this->createMembership($tenant, $tenant['curriculum'], $tenant['subject'], 'semester', 2);
        $offering = $this->createOffering($tenant, 'INCOMPATIBLE-PERIOD');
        $manager = $this->staff(1, ['academic.course_offering.manage']);

        $this->actingAs($manager)
            ->post(route('admin.course_offerings.applicability.store', $offering->id), ['curriculum_membership_id' => $membership])
            ->assertSessionHasErrors('applicability');
        $this->assertDatabaseMissing('course_offering_curriculum_memberships', [
            'school_id' => $tenant['school'], 'course_offering_id' => $offering->id, 'curriculum_membership_id' => $membership,
        ]);
    }

    public function test_completed_offering_rejects_all_lifecycle_http_actions(): void
    {
        $tenant = $this->tenants[1];
        $offering = $this->createOffering($tenant, 'TERMINAL-HTTP');
        $this->attach($tenant, $offering, $tenant['member']);
        $manager = $this->staff(1, ['academic.course_offering.manage', 'academic.course_offering.lifecycle']);
        $this->actingAs($manager);

        $this->post(route('admin.course_offerings.open', $offering->id))->assertRedirect();
        $this->post(route('admin.course_offerings.start', $offering->id))->assertRedirect();
        $this->post(route('admin.course_offerings.complete', $offering->id))->assertRedirect();
        $this->assertSame('completed', $offering->fresh()->status);

        foreach (['open', 'start', 'complete', 'cancel'] as $action) {
            $this->post(route('admin.course_offerings.'.$action, $offering->id), ['reason' => 'Terminal state check'])
                ->assertSessionHasErrors('lifecycle');
            $this->assertSame('completed', $offering->fresh()->status);
        }
    }

    public function test_programme_context_link_filters_central_offerings_by_applicability(): void
    {
        $tenant = $this->tenants[1];
        $otherProgramme = $this->makeProgramme($tenant['school'], ['code' => 'P1-OTHER', 'name' => 'Other Programme', 'department_id' => $tenant['department']]);
        $otherCurriculum = (int) DB::table('curricula')->insertGetId([
            'school_id' => $tenant['school'], 'programme_id' => $otherProgramme, 'version' => 'other-v1',
            'effective_academic_year_id' => $tenant['year'], 'status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherStage = (int) DB::table('curriculum_stages')->insertGetId([
            'school_id' => $tenant['school'], 'curriculum_id' => $otherCurriculum, 'label' => 'Year 1', 'sequence' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherMembership = $this->createMembership($tenant, $otherCurriculum, $tenant['subject'], 'semester', 1, $otherStage);
        $offeringA = $this->createOffering($tenant, 'PROGRAMME-A');
        $offeringB = $this->createOffering($tenant, 'PROGRAMME-B');
        $this->attach($tenant, $offeringA, $tenant['member']);
        $this->attach($tenant, $offeringB, $otherMembership);
        $viewer = $this->staff(1, ['academic.course_offering.view', 'academic.programmes']);
        $this->actingAs($viewer);

        $programmePage = $this->get(route('admin.programmes.index'));
        $programmePage->assertOk()->assertSee(route('admin.course_offerings.index', ['programme_id' => $tenant['programme']]), false);
        $this->get(route('admin.course_offerings.index', ['programme_id' => $tenant['programme']]))
            ->assertOk()->assertSee('PROGRAMME-A')->assertDontSee('PROGRAMME-B');
    }

    public function test_academic_structure_context_link_carries_year_and_period_filters(): void
    {
        $tenant = $this->tenants[1];
        $secondPeriod = (int) DB::table('academic_periods')->insertGetId([
            'school_id' => $tenant['school'], 'academic_year_id' => $tenant['year'], 'type' => 'semester', 'label' => 'Semester 2', 'sequence' => 2,
            'start_date' => '2026-07-01', 'end_date' => '2026-12-31', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $offeringOne = $this->createOffering($tenant, 'YEAR-PERIOD-ONE');
        $offeringTwo = app(CourseOfferingService::class)->createDraft($tenant['school'], $tenant['subject'], $tenant['year'], $secondPeriod, 'YEAR-PERIOD-TWO');
        $viewer = $this->staff(1, ['academic.course_offering.view', 'academic.structure.manage']);
        $this->actingAs($viewer);

        $structure = $this->get(route('admin.academic_structure.index'));
        $structure->assertOk()->assertSee(route('admin.course_offerings.index', ['year_id' => $tenant['year'], 'period_id' => $tenant['period']]));
        $this->get(route('admin.course_offerings.index', ['year_id' => $tenant['year'], 'period_id' => $tenant['period']]))
            ->assertOk()->assertSee('YEAR-PERIOD-ONE')->assertDontSee('YEAR-PERIOD-TWO');
        $this->assertNotSame($offeringOne->id, $offeringTwo->id);
    }

    public function test_authorized_hei_navigation_shows_course_offerings_item(): void
    {
        $viewer = $this->staff(1, ['academic.course_offering.view']);
        $response = $this->actingAs($viewer)->get(route('admin.course_offerings.index'));
        $response->assertOk()->assertSee('>Course Offerings</span>', false);
        $response->assertSee(route('admin.course_offerings.index'), false);
    }

    public function test_navigation_and_contextual_entry_visibility_follow_tenant_type_and_authorization(): void
    {
        $hei = $this->tenants[1];
        $approved = $hei['curriculum'];
        DB::table('curricula')->where('id', $approved)->update(['status' => 'approved']);
        $draftCurriculum = $this->curriculumFor($hei, 'draft', 'draft-v2');
        $retiredCurriculum = $this->curriculumFor($hei, 'retired', 'retired-v3');
        $manager = $this->staff(1, ['academic.course_offering.view','academic.course_offering.manage','academic.structure.manage','academic.curriculum.view']);
        $this->actingAs($manager);
        $this->get(route('admin.course_offerings.index'))->assertOk()->assertSee('Course Offerings');
        $this->get(route('admin.course_offerings.create'))->assertOk()
            ->assertSee('<h4>Create Course Offering</h4>', false)
            ->assertSee('Choose the Course Unit, Academic Year and Semester for this teaching period. Programme Study Plans can be linked after the Offering is created.')
            ->assertSee('<label class="form-label" for="period">Semester</label>', false)
            ->assertSee('>Create Course Offering</button>', false)
            ->assertDontSee('Create Course Offering draft')
            ->assertDontSee('>Create draft</button>', false);
        $this->get(route('admin.curricula.show', $approved))->assertOk()->assertSee('Create Course Offerings');
        $this->get(route('admin.curricula.show', $draftCurriculum))->assertOk()->assertDontSee('Create Course Offerings');
        $this->get(route('admin.curricula.show', $retiredCurriculum))->assertOk()->assertDontSee('Create Course Offerings');

        $k12school = $this->makeSchool(['title'=>'K12','status'=>1,'school_type'=>'k12','academic_calendar_pattern'=>'term']);
        // Render the shared admin shell on a simple authorized page and assert the K12 sidebar does not expose Offerings.
        $k12manager = $this->staffForSchool($k12school, ['academic.course_offering.view','academic.course_offering.manage']);
        $this->actingAs($k12manager)
            ->get(route('admin.course_offerings.index'))->assertOk()->assertDontSee('>Course Offerings</span>', false);
        $this->get(route('admin.course_offerings.create'))->assertOk()
            ->assertSee('Academic Year and Term for this teaching period.')
            ->assertSee('<label class="form-label" for="period">Term</label>', false);
    }

    public function test_academic_structure_navigation_uses_existing_permission_and_menu_boundaries(): void
    {
        $authorized = $this->staff(1, ['academic.course_offering.view', 'academic.structure.manage']);
        $this->actingAs($authorized)->get(route('admin.course_offerings.index'))
            ->assertOk()
            ->assertSee(route('admin.academic_structure.index'), false)
            ->assertSee('Academic Years &amp; Periods', false);

        $restricted = $this->staff(1, ['academic.course_offering.view'], [
            'menu_permission' => json_encode(['admin.course_offerings.index']),
        ]);
        $this->actingAs($restricted)->get(route('admin.course_offerings.index'))
            ->assertOk()
            ->assertDontSee(route('admin.academic_structure.index'), false)
            ->assertDontSee('Academic Years &amp; Periods', false);
        $this->get(route('admin.academic_structure.index'))->assertForbidden();
    }

    public function test_academic_context_http_workflow_feedback_tenant_validation_and_clear_period(): void
    {
        $tenantA = $this->tenants[1];
        $tenantB = $this->tenants[2];
        $admin = $this->staff(1, ['academic.structure.manage']);
        DB::table('academic_periods')->where('id', $tenantA['period'])->update(['label' => 'Academic year 2026-2027']);

        $this->actingAs($admin)
            ->post(route('admin.academic_structure.current'), [
                'current_academic_year_id' => $tenantA['year'],
                'current_academic_period_id' => $tenantA['period'],
            ])
            ->assertRedirect(route('admin.academic_structure.index'))
            ->assertSessionHas('success', 'Current academic context updated.');

        $this->assertDatabaseHas('schools', [
            'id' => $tenantA['school'],
            'current_academic_year_id' => $tenantA['year'],
            'current_academic_period_id' => $tenantA['period'],
        ]);

        $this->get(route('admin.academic_structure.index'))
            ->assertOk()
            ->assertSee('Current academic context updated.')
            ->assertSee('2026 — Semester 1')
            ->assertSee('Semester 1')
            ->assertDontSee('Academic year 2026-2027')
            ->assertSee('class="eBtn eBtn-primary" type="submit">Save current context</button>', false);

        $this->post(route('admin.academic_structure.current'), [
            'current_academic_year_id' => $tenantA['year'],
            'current_academic_period_id' => '',
        ])->assertRedirect(route('admin.academic_structure.index'))
            ->assertSessionHas('success', 'Current academic context updated.');
        $this->assertDatabaseHas('schools', [
            'id' => $tenantA['school'],
            'current_academic_year_id' => $tenantA['year'],
            'current_academic_period_id' => null,
        ]);

        $inactiveYear = (int) DB::table('academic_years')->insertGetId([
            'school_id' => $tenantA['school'], 'label' => 'Inactive year', 'start_date' => '2027-01-01',
            'end_date' => '2027-12-31', 'status' => 'planned', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $inactivePeriod = (int) DB::table('academic_periods')->insertGetId([
            'school_id' => $tenantA['school'], 'academic_year_id' => $tenantA['year'], 'type' => 'semester',
            'label' => 'Inactive period', 'sequence' => 2, 'start_date' => '2026-07-01', 'end_date' => '2026-12-31',
            'status' => 'planned', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherYear = (int) DB::table('academic_years')->insertGetId([
            'school_id' => $tenantA['school'], 'label' => 'Other year', 'start_date' => '2027-01-01',
            'end_date' => '2027-12-31', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherYearPeriod = (int) DB::table('academic_periods')->insertGetId([
            'school_id' => $tenantA['school'], 'academic_year_id' => $otherYear, 'type' => 'semester',
            'label' => 'Other year period', 'sequence' => 1, 'start_date' => '2027-01-01', 'end_date' => '2027-06-30',
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->post(route('admin.academic_structure.current'), [
            'current_academic_year_id' => $inactiveYear, 'current_academic_period_id' => '',
        ])->assertSessionHasErrors('current_academic_year_id');
        $this->post(route('admin.academic_structure.current'), [
            'current_academic_year_id' => $tenantA['year'], 'current_academic_period_id' => $inactivePeriod,
        ])->assertSessionHasErrors('current_academic_period_id');
        $this->post(route('admin.academic_structure.current'), [
            'current_academic_year_id' => $tenantA['year'], 'current_academic_period_id' => $otherYearPeriod,
        ])->assertSessionHasErrors('current_academic_period_id');
        $this->post(route('admin.academic_structure.current'), [
            'current_academic_year_id' => $tenantB['year'], 'current_academic_period_id' => '',
        ])->assertSessionHasErrors('current_academic_year_id');
        $this->post(route('admin.academic_structure.current'), [
            'current_academic_year_id' => $tenantA['year'], 'current_academic_period_id' => $tenantB['period'],
        ])->assertSessionHasErrors('current_academic_period_id');
        $this->get(route('admin.academic_structure.index'))
            ->assertOk()
            ->assertSee('The selected period must belong to the selected year and school.');

        $this->actingAs($this->staff(1, []))
            ->post(route('admin.academic_structure.current'), [
                'current_academic_year_id' => $tenantA['year'], 'current_academic_period_id' => $tenantA['period'],
            ])->assertForbidden();
    }

    public function test_stage_forms_are_distinct_edit_preserves_record_and_add_creates_another_without_stale_subject_errors(): void
    {
        $tenant = $this->tenants[1];
        $curriculumId = $this->curriculumFor($tenant, 'draft', 'STAGE-ERROR-ISOLATION');
        $manager = $this->staff(1, ['academic.curriculum.manage', 'academic.curriculum.view']);
        $staleErrors = (new \Illuminate\Support\ViewErrorBag())->put(
            'default', new \Illuminate\Support\MessageBag(['subject_ids' => ['The subject ids field is required.']])
        );

        $stageResponse = $this->actingAs($manager)
            ->withSession(['errors' => $staleErrors])
            ->from(route('admin.curricula.show', $curriculumId))
            ->post(route('admin.curricula.stages.store', $curriculumId), ['label' => 'Year 1', 'sequence' => 1])
            ->assertRedirect(route('admin.curricula.show', $curriculumId));
        $stageResponse->assertSessionMissing('errors');

        $this->assertDatabaseHas('curriculum_stages', [
            'school_id' => $tenant['school'], 'curriculum_id' => $curriculumId, 'label' => 'Year 1', 'sequence' => 1,
        ]);
        $showResponse = $this->get(route('admin.curricula.show', $curriculumId));
        $showResponse
            ->assertOk()
            ->assertSee('Stage added.')
            ->assertSee('Add New Stage')
            ->assertSee('Create a separate stage in this Study Plan. Existing stages will not be changed.')
            ->assertSee('Stage 1 — Year 1')
            ->assertSee('Edit Stage: Year 1')
            ->assertSee('Stage Name')
            ->assertSee('Position')
            ->assertSee('Save Stage Changes')
            ->assertDontSee('The subject ids field is required.');

        $dom = new \DOMDocument();
        $previousLibxmlState = libxml_use_internal_errors(true);
        $dom->loadHTML($showResponse->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);
        $xpath = new \DOMXPath($dom);
        $stageAction = route('admin.curricula.stages.store', $curriculumId);
        $membershipAction = route('admin.curricula.memberships.store', $curriculumId);
        $stageId = (int) DB::table('curriculum_stages')->where('curriculum_id', $curriculumId)->value('id');
        $editAction = route('admin.curricula.stages.update', [$curriculumId, $stageId]);
        $this->assertSame(1, $xpath->query('//form[@action="'.$stageAction.'"]')->length);
        $this->assertSame(1, $xpath->query('//form[@action="'.$editAction.'"][.//input[@name="_method" and @value="PUT"]]')->length);
        $this->assertSame(1, $xpath->query('//form[@action="'.$membershipAction.'"]')->length);
        $this->assertSame(0, $xpath->query('//form[ancestor::form]')->length, 'The rendered page must not contain nested forms.');
        $workspaceFormIds = [];
        foreach ($xpath->query('//*[@id="page-print-area"]//form[@id]') as $form) {
            $workspaceFormIds[] = $form->getAttribute('id');
        }
        $this->assertSame(count($workspaceFormIds), count(array_unique($workspaceFormIds)), 'Workspace form IDs must be unique.');

        $this->from(route('admin.curricula.show', $curriculumId))
            ->put(route('admin.curricula.stages.update', [$curriculumId, $stageId]), ['label' => 'Year 1 Revised', 'sequence' => 1])
            ->assertRedirect(route('admin.curricula.show', $curriculumId));
        $this->assertDatabaseHas('curriculum_stages', [
            'id' => $stageId, 'school_id' => $tenant['school'], 'curriculum_id' => $curriculumId,
            'label' => 'Year 1 Revised', 'sequence' => 1,
        ]);

        $this->from(route('admin.curricula.show', $curriculumId))
            ->post(route('admin.curricula.stages.store', $curriculumId), ['label' => 'Year 2', 'sequence' => 2])
            ->assertRedirect(route('admin.curricula.show', $curriculumId));
        $this->assertDatabaseHas('curriculum_stages', [
            'id' => $stageId, 'school_id' => $tenant['school'], 'curriculum_id' => $curriculumId,
            'label' => 'Year 1 Revised', 'sequence' => 1,
        ]);
        $newStage = DB::table('curriculum_stages')->where('school_id', $tenant['school'])
            ->where('curriculum_id', $curriculumId)->where('label', 'Year 2')->first();
        $this->assertNotNull($newStage);
        $this->assertNotSame($stageId, (int) $newStage->id);
        $this->assertSame(2, (int) $newStage->sequence);

        $this->post(route('admin.curricula.memberships.store', $curriculumId), [
            'curriculum_stage_id' => $stageId,
        ])->assertSessionHasErrors('subject_ids');

        $unauthorized = $this->staff(1, []);
        $this->actingAs($unauthorized)
            ->post(route('admin.curricula.stages.store', $curriculumId), ['label' => 'Blocked', 'sequence' => 2])
            ->assertForbidden();
        $this->assertDatabaseMissing('curriculum_stages', [
            'school_id' => $tenant['school'], 'curriculum_id' => $curriculumId, 'label' => 'Blocked',
        ]);

        $foreignDraftId = $this->curriculumFor($this->tenants[2], 'draft', 'FOREIGN-STAGE-ISOLATION');
        $this->actingAs($manager)
            ->post(route('admin.curricula.stages.store', $foreignDraftId), ['label' => 'Blocked', 'sequence' => 1])
            ->assertNotFound();
        $this->assertDatabaseMissing('curriculum_stages', [
            'school_id' => $this->tenants[2]['school'], 'curriculum_id' => $foreignDraftId, 'label' => 'Blocked',
        ]);

        $approvedCurriculumId = $tenant['curriculum'];
        $this->actingAs($manager)
            ->from(route('admin.curricula.show', $approvedCurriculumId))
            ->post(route('admin.curricula.stages.store', $approvedCurriculumId), ['label' => 'Blocked', 'sequence' => 2])
            ->assertSessionHasErrors('curriculum');
        $this->assertDatabaseMissing('curriculum_stages', [
            'school_id' => $tenant['school'], 'curriculum_id' => $approvedCurriculumId, 'label' => 'Blocked',
        ]);
    }

    public function test_course_registration_shape_and_behavior_remain_unchanged(): void
    {
        $this->assertContains('session_id', Schema::getColumnListing('course_registrations'));
        $this->assertNotContains('course_offering_id', Schema::getColumnListing('course_registrations'));
        $this->assertSame(0, DB::table('course_registrations')->count());
    }

    public function test_offering_live_class_workspace_is_exact_tenant_scoped_and_provider_secret_free(): void
    {
        $a = $this->tenants[1];
        $b = $this->tenants[2];
        $offeringA = $this->createOffering($a, 'WORKSPACE-A');
        $parallel = $this->createOffering($a, 'WORKSPACE-B', $a['subject']);
        $foreign = $this->createOffering($b, 'WORKSPACE-FOREIGN');
        DB::table('course_offerings')->whereIn('id', [$offeringA->id, $parallel->id, $foreign->id])->update(['status' => 'open']);
        $ownSessionId = $this->insertWorkspaceClass($a['school'], $a['subject'], $offeringA->id, 'OWN SESSION', 'scheduled', true, '2026-10-01 10:00:00');
        $this->insertWorkspaceClass($a['school'], $a['subject'], $parallel->id, 'PARALLEL SECRET SESSION', 'scheduled', true);
        $this->insertWorkspaceClass($b['school'], $b['subject'], $foreign->id, 'CROSS TENANT SECRET SESSION', 'scheduled', true);
        $this->insertWorkspaceClass($a['school'], $a['subject'], null, 'LEGACY SECRET SESSION', 'scheduled', true);
        DB::table('live_classes')->where('title', 'OWN SESSION')->update([
            'meeting_url' => 'https://provider.example/DO-NOT-LEAK', 'meeting_id' => 'DO-NOT-LEAK-ID',
            'meeting_password' => 'DO-NOT-LEAK-PASSWORD', 'recording_url' => 'https://recording.example/DO-NOT-LEAK',
        ]);
        DB::table('live_class_materials')->insert([
            'school_id' => $a['school'], 'live_class_id' => $ownSessionId, 'type' => 'file', 'category' => 'resource',
            'title' => 'Confidential resource', 'original_name' => 'slides.pdf',
            'stored_name' => 'private/live-classes/SECRET-STORAGE-KEY.pdf', 'link_url' => 'https://provider.example/SECRET-LINK',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->insertWorkspaceClass($a['school'], $a['subject'], $offeringA->id, 'PAST SESSION', 'ended', true, '2026-08-01 10:00:00');
        $this->insertWorkspaceClass($a['school'], $a['subject'], $offeringA->id, 'CANCELLED SESSION', 'cancelled', true, '2026-10-02 10:00:00');

        $admin = $this->staff(1, ['academic.course_offering.view', 'live_classes.view', 'live_classes.create'], ['role_id' => 2]);
        $response = $this->actingAs($admin)->get(route('admin.course_offerings.show', $offeringA->id));
        $response->assertOk()->assertSee('OWN SESSION')->assertSee('PAST SESSION')->assertSee('CANCELLED SESSION')
            ->assertDontSee('PARALLEL SECRET SESSION')
            ->assertDontSee('CROSS TENANT SECRET SESSION')->assertDontSee('LEGACY SECRET SESSION')
            ->assertSee('Schedule Live Class')->assertSee(route('admin.course_offerings.live_classes.create', $offeringA->id), false)
            ->assertSee('Scheduled')->assertSee('Ended')->assertSee('Cancelled')
            ->assertSee('View')->assertSee('Edit')->assertSee('Cancel')->assertSee('Join Evidence')
            ->assertDontSee('DO-NOT-LEAK')->assertDontSee('DO-NOT-LEAK-ID')->assertDontSee('DO-NOT-LEAK-PASSWORD')
            ->assertDontSee('SECRET-STORAGE-KEY')->assertDontSee('SECRET-LINK')->assertDontSee('private/live-classes')
            ->assertDontSee('course_offering_id');
    }

    public function test_lecturer_creation_authority_does_not_render_admin_schedule_route(): void
    {
        $tenant = $this->tenants[1];
        $offering = $this->createOffering($tenant, 'LECTURER-CTA');
        DB::table('course_offerings')->where('id', $offering->id)->update(['status' => 'open']);
        $offering->refresh();
        $lecturer = $this->staff(1, ['academic.course_offering.view', 'live_classes.view', 'live_classes.create']);
        DB::table('course_offering_lecturer_allocations')->insert([
            'school_id' => $tenant['school'], 'course_offering_id' => $offering->id, 'user_id' => $lecturer->id,
            'role' => 'primary_lecturer', 'status' => 'active', 'starts_on' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertTrue(app(\App\Support\LiveClasses\LiveClassAccessService::class)
            ->canLecturerCreateForOffering($lecturer, $offering));
        $response = $this->actingAs($lecturer)->get(route('admin.course_offerings.show', $offering->id));
        $response->assertOk()->assertDontSee('Schedule Live Class');
    }

    public function test_offering_session_actions_follow_lecturer_allocation_role(): void
    {
        $tenant = $this->tenants[1];
        $offering = $this->createOffering($tenant, 'ROLE-ACTIONS');
        DB::table('course_offerings')->where('id', $offering->id)->update(['status' => 'open']);
        $offering->refresh();
        $this->insertWorkspaceClass($tenant['school'], $tenant['subject'], $offering->id, 'ROLE MATRIX SESSION', 'scheduled', true, '2026-10-01 10:00:00');

        foreach ([
            'primary_lecturer' => true,
            'co_lecturer' => true,
            'teaching_assistant' => false,
            'lab_instructor' => false,
            'guest_lecturer' => false,
        ] as $role => $canManage) {
            $lecturer = $this->staff(1, ['academic.course_offering.view', 'live_classes.view', 'live_classes.create']);
            DB::table('course_offering_lecturer_allocations')->insert([
                'school_id' => $tenant['school'], 'course_offering_id' => $offering->id, 'user_id' => $lecturer->id,
                'role' => $role, 'status' => 'active', 'starts_on' => now()->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $response = $this->actingAs($lecturer)->get(route('admin.course_offerings.show', $offering->id));
            $response->assertOk()->assertSee('ROLE MATRIX SESSION')->assertSee('View');
            if ($canManage) {
                $response->assertSee('Edit')->assertSee('Cancel');
            } else {
                $response->assertDontSee('Edit')->assertDontSee('Cancel this class?');
            }
        }
    }

    public function test_offering_workspace_empty_state_and_schedule_cta_visibility(): void
    {
        $offering = $this->createOffering($this->tenants[1], 'EMPTY-WORKSPACE');
        $admin = $this->staff(1, ['academic.course_offering.view'], ['role_id' => 2]);
        $this->actingAs($admin)->get(route('admin.course_offerings.show', $offering->id))
            ->assertOk()->assertSee('No live classes have been scheduled for this course offering yet.')
            ->assertDontSee('Schedule Live Class');

        $this->actingAs($this->staff(1, ['academic.course_offering.view']))
            ->get(route('admin.course_offerings.show', $offering->id))
            ->assertOk()->assertDontSee('Schedule Live Class');
    }

    public function test_offering_workspace_renders_all_existing_user_facing_statuses(): void
    {
        $clock = \Illuminate\Support\Carbon::parse('2026-10-01 10:00:00', 'UTC');
        \Illuminate\Support\Carbon::setTestNow($clock);
        try {
            $tenant = $this->tenants[1];
            $offering = $this->createOffering($tenant, 'STATUS-WORKSPACE');
            DB::table('course_offerings')->where('id', $offering->id)->update(['status' => 'open']);
            $this->insertWorkspaceClass($tenant['school'], $tenant['subject'], $offering->id, 'DRAFT STATUS', 'draft', false, '2026-10-01 12:00:00');
            $this->insertWorkspaceClass($tenant['school'], $tenant['subject'], $offering->id, 'SCHEDULED STATUS', 'scheduled', true, '2026-10-01 13:00:00');
            $this->insertWorkspaceClass($tenant['school'], $tenant['subject'], $offering->id, 'STARTING SOON STATUS', 'scheduled', true, '2026-10-01 10:10:00');
            $this->insertWorkspaceClass($tenant['school'], $tenant['subject'], $offering->id, 'LIVE STATUS', 'live', true, '2026-10-01 09:55:00');
            $this->insertWorkspaceClass($tenant['school'], $tenant['subject'], $offering->id, 'ENDED STATUS', 'ended', true, '2026-09-30 10:00:00');
            $this->insertWorkspaceClass($tenant['school'], $tenant['subject'], $offering->id, 'CANCELLED STATUS', 'cancelled', true, '2026-10-01 13:00:00');

            $this->actingAs($this->staff(1, ['academic.course_offering.view'], ['role_id' => 2]))
                ->get(route('admin.course_offerings.show', $offering->id))
                ->assertOk()
                ->assertSee('Draft')->assertSee('Scheduled')->assertSee('Starting Soon')
                ->assertSee('Live Now')->assertSee('Ended')->assertSee('Cancelled');
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
        }
    }

    private function insertWorkspaceClass(int $schoolId, int $subjectId, ?int $offeringId, string $title, string $status, bool $published, ?string $scheduledAt = null): int
    {
        $endsAt = $scheduledAt ? \Illuminate\Support\Carbon::parse($scheduledAt)->addHour()->toDateTimeString() : null;
        return (int) DB::table('live_classes')->insertGetId([
            'school_id' => $schoolId, 'subject_id' => $subjectId, 'course_offering_id' => $offeringId,
            'title' => $title, 'status' => $status, 'is_published' => $published,
            'scheduled_at' => $scheduledAt, 'ends_at' => $endsAt,
            'timezone' => 'UTC', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createAcademicTables(): void
    {
        Schema::create('academic_years', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('school_id'); $t->string('label'); $t->date('start_date'); $t->date('end_date'); $t->string('status'); $t->timestamps(); });
        Schema::create('academic_periods', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('academic_year_id'); $t->string('type'); $t->string('label'); $t->unsignedSmallInteger('sequence'); $t->date('start_date'); $t->date('end_date'); $t->string('status'); $t->timestamps(); });
        Schema::create('curricula', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('programme_id'); $t->string('version'); $t->unsignedBigInteger('effective_academic_year_id')->nullable(); $t->string('status'); $t->timestamps(); });
        Schema::create('curriculum_stages', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('curriculum_id'); $t->string('label'); $t->unsignedSmallInteger('sequence'); $t->timestamps(); });
        Schema::create('curriculum_memberships', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('curriculum_id'); $t->unsignedBigInteger('subject_id'); $t->unsignedBigInteger('curriculum_stage_id'); $t->string('period_type')->nullable(); $t->unsignedSmallInteger('period_sequence')->nullable(); $t->string('classification'); $t->decimal('credits',6,2); $t->unsignedSmallInteger('sequence')->default(0); $t->timestamps(); });
        Schema::create('curriculum_prerequisites', function (Blueprint $t): void {
            $t->unsignedBigInteger('school_id');
            $t->unsignedBigInteger('curriculum_id');
            $t->unsignedBigInteger('membership_id');
            $t->unsignedBigInteger('prerequisite_membership_id');
            $t->primary(['membership_id', 'prerequisite_membership_id'], 'curriculum_prerequisites_edge_pk');
            $t->index(['school_id', 'curriculum_id'], 'curriculum_prerequisites_tenant_curriculum_idx');
        });
        Schema::create('course_offerings', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('subject_id'); $t->unsignedBigInteger('academic_year_id'); $t->unsignedBigInteger('academic_period_id'); $t->string('reference',50)->nullable(); $t->string('status',20)->default('draft'); $t->timestamps(); });
        Schema::create('course_offering_curriculum_memberships', function (Blueprint $t): void { $t->unsignedBigInteger('school_id'); $t->unsignedBigInteger('course_offering_id'); $t->unsignedBigInteger('curriculum_id'); $t->unsignedBigInteger('curriculum_membership_id'); $t->unsignedBigInteger('subject_id'); $t->timestamps(); $t->primary(['school_id','course_offering_id','curriculum_membership_id']); });
        Schema::create('course_registrations', function (Blueprint $t): void { $t->id(); $t->unsignedBigInteger('student_id'); $t->unsignedBigInteger('subject_id'); $t->unsignedBigInteger('session_id')->nullable(); $t->unsignedBigInteger('school_id'); $t->string('status'); });
    }

    private function tenant(int $schoolId, string $type): array
    {
        $school = $this->makeSchool(['title'=>'Tenant '.$schoolId,'status'=>1,'school_type'=>$type,'academic_calendar_pattern'=>'semester']);
        $department = $this->makeDepartment($school, 'Department '.$schoolId);
        $programme = $this->makeProgramme($school, ['code'=>'P'.$schoolId,'name'=>'Programme '.$schoolId,'department_id'=>$department]);
        $year = (int)DB::table('academic_years')->insertGetId(['school_id'=>$school,'label'=>'2026','start_date'=>'2026-01-01','end_date'=>'2026-12-31','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $period = (int)DB::table('academic_periods')->insertGetId(['school_id'=>$school,'academic_year_id'=>$year,'type'=>'semester','label'=>'Semester 1','sequence'=>1,'start_date'=>'2026-01-01','end_date'=>'2026-06-30','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $subject = $this->subject($school, 'Core '.$schoolId, 'CORE-'.$schoolId);
        $curriculum = (int)DB::table('curricula')->insertGetId(['school_id'=>$school,'programme_id'=>$programme,'version'=>'v1','effective_academic_year_id'=>$year,'status'=>'approved','created_at'=>now(),'updated_at'=>now()]);
        $stage = (int)DB::table('curriculum_stages')->insertGetId(['school_id'=>$school,'curriculum_id'=>$curriculum,'label'=>'Year 1','sequence'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $member = (int)DB::table('curriculum_memberships')->insertGetId(['school_id'=>$school,'curriculum_id'=>$curriculum,'subject_id'=>$subject,'curriculum_stage_id'=>$stage,'period_type'=>'semester','period_sequence'=>1,'classification'=>'compulsory','credits'=>3,'sequence'=>1,'created_at'=>now(),'updated_at'=>now()]);
        return compact('school','department','programme','year','period','subject','curriculum','stage','member');
    }

    private function curriculumFor(array $tenant, string $status, string $version): int
    {
        return (int)DB::table('curricula')->insertGetId(['school_id'=>$tenant['school'],'programme_id'=>$tenant['programme'],'version'=>$version,'effective_academic_year_id'=>$tenant['year'],'status'=>$status,'created_at'=>now(),'updated_at'=>now()]);
    }

    private function subject(int $school, string $name, string $code): int
    {
        return (int)DB::table('subjects')->insertGetId(['school_id'=>$school,'name'=>$name,'code'=>$code,'created_at'=>now(),'updated_at'=>now()]);
    }

    private function createMembership(array $tenant, int $curriculumId, int $subjectId, ?string $periodType, ?int $periodSequence, ?int $stageId = null): int
    {
        $stageId ??= (int) DB::table('curriculum_stages')->where('school_id', $tenant['school'])->where('curriculum_id', $curriculumId)->value('id');
        return (int) DB::table('curriculum_memberships')->insertGetId([
            'school_id' => $tenant['school'], 'curriculum_id' => $curriculumId, 'subject_id' => $subjectId,
            'curriculum_stage_id' => $stageId, 'period_type' => $periodType, 'period_sequence' => $periodSequence,
            'classification' => 'compulsory', 'credits' => 3, 'sequence' => 2, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function createOffering(array $tenant, string $reference, ?int $subject = null): CourseOffering
    {
        return app(CourseOfferingService::class)->createDraft($tenant['school'],$subject ?? $tenant['subject'],$tenant['year'],$tenant['period'],$reference);
    }

    private function attach(array $tenant, CourseOffering $offering, int $membership): void
    {
        app(CourseOfferingService::class)->addApplicability($tenant['school'],$offering->id,$membership);
    }

    private function draftPayload(array $tenant, string $reference = ''): array
    {
        return ['subject_id'=>$tenant['subject'],'academic_year_id'=>$tenant['year'],'academic_period_id'=>$tenant['period'],'reference'=>$reference];
    }

    private function staff(int $schoolNumber, array $permissions, array $extra = []): User
    {
        $user = $this->staffForSchool($this->tenants[$schoolNumber]['school'], $permissions, $extra);
        return $user;
    }

    private function staffForSchool(int $schoolId, array $permissions, array $extra = []): User
    {
        $user = User::factory()->create($extra + ['role_id'=>3,'school_id'=>$schoolId,'account_status'=>'active']);
        $this->grant($user, ...$permissions);
        return $user;
    }

    private function grant(User $user, string ...$permissions): void
    {
        foreach ($permissions as $permission) DB::table('user_permissions')->insert(['school_id'=>$user->school_id,'user_id'=>$user->id,'permission'=>$permission,'created_at'=>now(),'updated_at'=>now()]);
    }
}
