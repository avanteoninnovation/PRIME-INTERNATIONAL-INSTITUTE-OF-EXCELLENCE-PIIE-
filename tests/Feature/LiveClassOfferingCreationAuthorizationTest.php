<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CourseOfferingLecturerAllocation;
use App\Models\LiveClass;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\LiveClassTestHelper;
use Tests\TestCase;

class LiveClassOfferingCreationAuthorizationTest extends TestCase
{
    use LiveClassTestHelper;

    private int $subjectA;
    private int $subjectB;
    private User $primary;
    private User $coLecturer;
    private int $offeringA;
    private int $offeringB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootLiveClassTestSchema();
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'UTC'));

        Schema::table('live_classes', function (Blueprint $table): void {
            $table->unsignedBigInteger('course_offering_id')->nullable();
        });
        Schema::create('course_offerings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('academic_year_id')->nullable();
            $table->unsignedBigInteger('academic_period_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('course_offering_lecturer_allocations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('course_offering_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role');
            $table->string('status');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
        });
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->string('label');
            $table->date('start_date'); $table->date('end_date'); $table->string('status'); $table->timestamps();
        });
        Schema::create('academic_periods', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('academic_year_id');
            $table->string('type'); $table->string('label'); $table->unsignedInteger('sequence');
            $table->date('start_date'); $table->date('end_date'); $table->string('status'); $table->timestamps();
        });

        DB::table('schools')->insert([
            ['id' => 1, 'title' => 'Tenant A'], ['id' => 2, 'title' => 'Tenant B'],
        ]);
        foreach ([1, 2] as $schoolId) {
            DB::table('academic_years')->insert([
                'id' => $schoolId, 'school_id' => $schoolId, 'label' => '2026/27',
                'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'status' => 'active',
            ]);
            DB::table('academic_periods')->insert([
                'id' => $schoolId, 'school_id' => $schoolId, 'academic_year_id' => $schoolId,
                'type' => 'semester', 'label' => 'Semester 1', 'sequence' => 1,
                'start_date' => '2026-09-01', 'end_date' => '2027-01-31', 'status' => 'active',
            ]);
        }
        $this->subjectA = $this->subject(1, 'Course A');
        $this->subjectB = $this->subject(1, 'Course B');
        $this->offeringA = $this->offering(1, $this->subjectA, CourseOffering::STATUS_OPEN);
        $this->offeringB = $this->offering(1, $this->subjectA, CourseOffering::STATUS_OPEN);
        $this->primary = $this->user('primary@example.test', 3, 1);
        $this->coLecturer = $this->user('co@example.test', 3, 1);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_primary_and_co_lecturers_create_through_the_contextual_http_routes(): void
    {
        foreach ([
            [$this->primary, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER],
            [$this->coLecturer, CourseOfferingLecturerAllocation::ROLE_CO_LECTURER],
        ] as [$lecturer, $role]) {
            $this->allocation($lecturer, $this->offeringA, $role);
            $this->actingAs($lecturer)->get($this->createUrl($this->offeringA))->assertOk();
            $response = $this->post($this->storeUrl($this->offeringA), $this->payload('HTTP '.$lecturer->email));
            $response->assertRedirect()->assertSessionHasNoErrors();
            $this->assertStringContainsString('/admin/live-classes/', (string) $response->headers->get('Location'));

            $created = LiveClass::query()->where('title', 'HTTP '.$lecturer->email)->firstOrFail();
            $this->assertSame(1, (int) $created->school_id);
            $this->assertSame($this->offeringA, (int) $created->course_offering_id);
            $this->assertSame($this->subjectA, (int) $created->subject_id);
            $this->assertSame((int) $lecturer->id, (int) $created->created_by);
            $this->assertSame((int) $lecturer->id, (int) $created->teacher_id);
            $this->assertNull($created->programme_id);
            $this->assertNull($created->academic_session_id);
        }

        $this->assertSame(2, LiveClass::query()->count());
        $responseBody = $response->getContent() ?: '';
        foreach (['http-secret-password', 'http-secret-meeting-id', 'https://meet.example.test/private-provider'] as $secret) {
            $this->assertStringNotContainsString($secret, $responseBody);
        }
        $this->assertStringContainsString('/admin/live-classes/', $response->headers->get('Location'));
    }

    public function test_non_managing_allocation_roles_cannot_create_even_with_create_capability(): void
    {
        foreach ([
            CourseOfferingLecturerAllocation::ROLE_TEACHING_ASSISTANT,
            CourseOfferingLecturerAllocation::ROLE_LAB_INSTRUCTOR,
            CourseOfferingLecturerAllocation::ROLE_GUEST_LECTURER,
        ] as $index => $role) {
            $lecturer = $this->user("nonmanager{$index}@example.test", 3, 1);
            $this->allocation($lecturer, $this->offeringA, $role);
            $this->actingAs($lecturer)->get($this->createUrl($this->offeringA))->assertForbidden();
            $this->post($this->storeUrl($this->offeringA), $this->payload())->assertForbidden();
        }
        $this->assertSame(0, LiveClass::query()->count());
    }

    public function test_create_capability_without_allocation_and_allocation_without_capability_are_denied(): void
    {
        $this->actingAs($this->primary)->get($this->createUrl($this->offeringA))->assertForbidden();
        $this->post($this->storeUrl($this->offeringA), $this->payload())->assertForbidden();

        $noCapability = $this->user('no-capability@example.test', 5, 1);
        $this->allocation($noCapability, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        $this->actingAs($noCapability)->get($this->createUrl($this->offeringA))->assertForbidden();
        $this->post($this->storeUrl($this->offeringA), $this->payload())->assertForbidden();
        $this->assertSame(0, LiveClass::query()->count());
    }

    public function test_same_subject_parallel_offering_is_not_authority(): void
    {
        $this->allocation($this->primary, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        $this->actingAs($this->primary)->get($this->createUrl($this->offeringB))->assertForbidden();
        $this->post($this->storeUrl($this->offeringB), $this->payload())->assertForbidden();
        $this->assertSame(0, LiveClass::query()->count());
    }

    public function test_cross_tenant_lecturer_and_admin_requests_are_hidden(): void
    {
        $foreignOffering = $this->offering(2, $this->subject(2, 'Foreign Course'), CourseOffering::STATUS_OPEN);
        $this->allocation($this->primary, $foreignOffering, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER, 2);
        $this->actingAs($this->primary)->get($this->createUrl($foreignOffering))->assertNotFound();
        $this->post($this->storeUrl($foreignOffering), $this->payload())->assertNotFound();

        $admin = $this->user('tenant-admin@example.test', 2, 1);
        $this->actingAs($admin)->get($this->createUrl($foreignOffering))->assertNotFound();
        $this->post($this->storeUrl($foreignOffering), $this->payload())->assertNotFound();
        $this->assertSame(0, LiveClass::query()->count());
    }

    public function test_offering_lifecycle_allows_only_open_and_in_progress_http_creation(): void
    {
        $this->allocation($this->primary, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        foreach ([
            CourseOffering::STATUS_DRAFT => false,
            CourseOffering::STATUS_OPEN => true,
            CourseOffering::STATUS_IN_PROGRESS => true,
            CourseOffering::STATUS_COMPLETED => false,
            CourseOffering::STATUS_CANCELLED => false,
        ] as $status => $allowed) {
            $offering = $this->offering(1, $this->subjectA, $status);
            $this->allocation($this->primary, $offering, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
            $this->actingAs($this->primary);
            if ($allowed) {
                $this->get($this->createUrl($offering))->assertOk();
                $this->post($this->storeUrl($offering), $this->payload("State {$status}"))->assertRedirect();
            } else {
                $this->get($this->createUrl($offering))->assertForbidden();
                $this->post($this->storeUrl($offering), $this->payload("State {$status}"))->assertForbidden();
            }
        }
        $this->assertSame(2, LiveClass::query()->count());
    }

    public function test_reserved_context_tampering_is_rejected_and_valid_request_derives_context(): void
    {
        $this->allocation($this->primary, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        $otherUser = $this->user('other-actor@example.test', 3, 1);
        $tampered = array_merge($this->payload(), [
            'school_id' => 2, 'course_offering_id' => $this->offeringB, 'subject_id' => $this->subjectB,
            'programme_id' => 999, 'academic_session_id' => 999, 'academic_year_id' => 999,
            'academic_period_id' => 999, 'created_by' => $otherUser->id, 'updated_by' => $otherUser->id,
        ]);
        $this->actingAs($this->primary)->post($this->storeUrl($this->offeringA), $tampered)
            ->assertSessionHasErrors('school_id');
        $this->assertSame(0, LiveClass::query()->count());

        $this->post($this->storeUrl($this->offeringA), $this->payload())->assertRedirect();
        $class = LiveClass::query()->firstOrFail();
        $this->assertSame(1, (int) $class->school_id);
        $this->assertSame($this->offeringA, (int) $class->course_offering_id);
        $this->assertSame($this->subjectA, (int) $class->subject_id);
        $this->assertSame((int) $this->primary->id, (int) $class->created_by);
    }

    public function test_lecturer_cannot_nominate_unrelated_facilitator_and_admin_must_choose_exact_manager(): void
    {
        $this->allocation($this->primary, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        $unrelated = $this->user('unrelated@example.test', 3, 1);
        $this->actingAs($this->primary)->post($this->storeUrl($this->offeringA), $this->payload('Bad lecturer', $unrelated->id))
            ->assertSessionHasErrors('teacher_id');
        $this->assertSame(0, LiveClass::query()->count());

        $admin = $this->user('admin-facilitator@example.test', 2, 1);
        $this->actingAs($admin)->post($this->storeUrl($this->offeringA), $this->payload('Bad admin facilitator', $unrelated->id))
            ->assertSessionHasErrors('teacher_id');
        $this->post($this->storeUrl($this->offeringA), $this->payload('Primary selected', $this->primary->id))->assertRedirect();

        $co = $this->user('valid-co@example.test', 3, 1);
        $this->allocation($co, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_CO_LECTURER);
        $this->post($this->storeUrl($this->offeringA), $this->payload('Co selected', $co->id))->assertRedirect();
        $this->assertSame(2, LiveClass::query()->count());
    }

    public function test_allocation_must_be_active_now_and_cover_the_meeting_date(): void
    {
        $future = $this->user('future@example.test', 3, 1);
        $this->allocation($future, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER, 1, 'active', '2026-09-26');
        $this->actingAs($future)->get($this->createUrl($this->offeringA))->assertForbidden();
        $this->post($this->storeUrl($this->offeringA), $this->payload())->assertForbidden();

        foreach ([
            ['ended@example.test', 'ended', '2026-09-01', null],
            ['cancelled@example.test', 'cancelled', '2026-09-01', null],
        ] as [$email, $status, $start, $end]) {
            $user = $this->user($email, 3, 1);
            $this->allocation($user, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER, 1, $status, $start, $end);
            $this->actingAs($user)->get($this->createUrl($this->offeringA))->assertForbidden();
            $this->post($this->storeUrl($this->offeringA), $this->payload())->assertForbidden();
        }

        $current = $this->user('current@example.test', 3, 1);
        $this->allocation($current, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER, 1, 'active', '2026-09-01', '2026-09-30');
        $this->actingAs($current)->get($this->createUrl($this->offeringA))->assertOk();
        $futureMeeting = $this->payload('Outside allocation');
        $futureMeeting['start_date'] = '2026-10-01';
        $this->post($this->storeUrl($this->offeringA), $futureMeeting)->assertSessionHasErrors('teacher_id');
        $this->assertSame(0, LiveClass::query()->count());
    }

    public function test_legacy_create_and_meet_now_keep_null_offering_and_reject_raw_offering_id(): void
    {
        $teacher = $this->user('legacy-teacher@example.test', 3, 1);
        $this->actingAs($teacher)->post(route('admin.live_classes.store'), $this->legacyPayload())->assertRedirect();
        $legacy = LiveClass::query()->firstOrFail();
        $this->assertNull($legacy->course_offering_id);

        $this->post(route('admin.live_classes.store'), $this->legacyPayload() + ['course_offering_id' => $this->offeringA])
            ->assertSessionHasErrors('course_offering_id');
        $this->post(route('admin.live_classes.meet_now'), ['course_offering_id' => $this->offeringA])
            ->assertSessionHasErrors('course_offering_id');
        $this->assertSame(1, LiveClass::query()->count());
    }

    private function payload(?string $title = null, ?int $teacherId = null): array
    {
        return array_filter([
            'title' => $title ?? 'HTTP '.$this->primary->email,
            'teacher_id' => $teacherId,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.example.test/private-provider',
            'meeting_id' => 'http-secret-meeting-id',
            'meeting_password' => 'http-secret-password',
            'start_date' => '2026-09-25', 'start_time' => '11:00', 'end_time' => '12:00',
            'timezone' => 'UTC', 'status' => 'draft', 'attendance_enabled' => 1,
        ], fn ($value) => $value !== null);
    }

    private function legacyPayload(): array
    {
        return [
            'title' => 'Legacy HTTP', 'platform' => 'jitsi', 'meeting_url' => 'https://meet.example.test/legacy',
            'start_date' => '2026-09-25', 'start_time' => '11:00', 'end_time' => '12:00', 'timezone' => 'UTC',
        ];
    }

    private function createUrl(int $offeringId): string
    {
        return route('admin.course_offerings.live_classes.create', $offeringId);
    }

    private function storeUrl(int $offeringId): string
    {
        return route('admin.course_offerings.live_classes.store', $offeringId);
    }

    private function subject(int $schoolId, string $name): int
    {
        return (int) DB::table('subjects')->insertGetId([
            'school_id' => $schoolId, 'name' => $name, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function offering(int $schoolId, int $subjectId, string $status): int
    {
        return (int) DB::table('course_offerings')->insertGetId([
            'school_id' => $schoolId, 'subject_id' => $subjectId, 'academic_year_id' => $schoolId,
            'academic_period_id' => $schoolId, 'reference' => 'OF-'.$schoolId.'-'.$status,
            'status' => $status, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function allocation(User $user, int $offeringId, string $role, int $schoolId = 1, string $status = 'active', string $startsOn = '2026-09-01', ?string $endsOn = null): void
    {
        DB::table('course_offering_lecturer_allocations')->insert([
            'school_id' => $schoolId, 'course_offering_id' => $offeringId, 'user_id' => $user->id,
            'role' => $role, 'status' => $status, 'starts_on' => $startsOn, 'ends_on' => $endsOn,
        ]);
    }

    private function user(string $email, int $roleId, int $schoolId): User
    {
        return User::create([
            'name' => $email, 'email' => $email, 'role_id' => $roleId,
            'school_id' => $schoolId, 'status' => 1, 'account_status' => 'active',
        ]);
    }
}
