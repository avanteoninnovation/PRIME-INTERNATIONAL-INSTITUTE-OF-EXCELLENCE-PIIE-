<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CourseOfferingLecturerAllocation;
use App\Models\CourseRegistration;
use App\Models\LiveClass;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\LiveClassTestHelper;
use Tests\TestCase;

class LiveClassOfferingProviderSecurityTest extends TestCase
{
    use LiveClassTestHelper;

    private const JITSI_TEST_SECRET = 'isolated-provider-test-signing-key';

    private int $subjectA;
    private int $offeringA;
    private int $parallelOffering;
    private int $foreignOffering;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootLiveClassTestSchema();
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'UTC'));
        Schema::table('live_classes', fn (Blueprint $table) => $table->unsignedBigInteger('course_offering_id')->nullable());
        Schema::create('course_offerings', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('academic_year_id')->nullable(); $table->unsignedBigInteger('academic_period_id')->nullable();
            $table->string('reference')->nullable(); $table->string('status'); $table->timestamps();
        });
        Schema::create('course_offering_lecturer_allocations', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('course_offering_id');
            $table->unsignedBigInteger('user_id'); $table->string('role'); $table->string('status');
            $table->date('starts_on'); $table->date('ends_on')->nullable();
        });
        Schema::create('course_registrations', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('course_offering_id')->nullable(); $table->unsignedBigInteger('subject_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable(); $table->string('status'); $table->timestamps();
        });
        Schema::create('addons', function (Blueprint $table): void {
            $table->id(); $table->string('unique_identifier')->nullable(); $table->string('status')->nullable();
        });

        DB::table('schools')->insert([
            ['id' => 1, 'title' => 'Tenant A'], ['id' => 2, 'title' => 'Tenant B'],
        ]);
        $this->subjectA = $this->subject(1, 'Provider course');
        $foreignSubject = $this->subject(2, 'Provider course');
        $this->offeringA = $this->offering(1, $this->subjectA);
        $this->parallelOffering = $this->offering(1, $this->subjectA);
        $this->foreignOffering = $this->offering(2, $foreignSubject);
        DB::table('global_settings')->insert([
            ['key' => 'live_class_jitsi_base_url', 'value' => 'https://meet.example.test', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'live_class_platform_zoom', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'live_class_platform_google_meet', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
        ]);
        Config::set('services.jitsi.algorithm', 'HS256');
        Config::set('services.jitsi.app_id', 'provider-security-test-app');
        Config::set('services.jitsi.app_secret', self::JITSI_TEST_SECRET);
        Config::set('services.google_meet.client_id', 'nonproduction-google-client');
        Config::set('services.google_meet.client_secret', 'nonproduction-google-secret');
        Config::set('services.google_meet.refresh_token', 'nonproduction-google-refresh');
        Config::set('services.google_meet.calendar_id', 'primary');
        Config::set('services.zoom.account_id', 'nonproduction-zoom-account');
        Config::set('services.zoom.client_id', 'nonproduction-zoom-client');
        Config::set('services.zoom.client_secret', 'nonproduction-zoom-secret');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Config::set('services.jitsi.algorithm', 'RS256');
        Config::set('services.jitsi.app_id', '');
        Config::set('services.jitsi.kid', '');
        Config::set('services.jitsi.private_key', '');
        Config::set('services.jitsi.app_secret', '');
        parent::tearDown();
    }

    public function test_real_jitsi_join_route_signs_moderator_only_for_primary_and_co_allocations(): void
    {
        $class = $this->liveClass(1, $this->offeringA);
        foreach ([
            CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER => true,
            CourseOfferingLecturerAllocation::ROLE_CO_LECTURER => true,
            CourseOfferingLecturerAllocation::ROLE_TEACHING_ASSISTANT => false,
            CourseOfferingLecturerAllocation::ROLE_LAB_INSTRUCTOR => false,
            CourseOfferingLecturerAllocation::ROLE_GUEST_LECTURER => false,
        ] as $role => $expectedModerator) {
            $lecturer = $this->user($role.'-provider@example.test', 3, 1);
            $this->allocation($lecturer, $this->offeringA, $role);
            $joinUrl = route('teacher.live_classes.join', $class->id);
            if ($role === CourseOfferingLecturerAllocation::ROLE_TEACHING_ASSISTANT) {
                $joinUrl .= '?moderator=1&is_moderator=1&room=attacker-room&token=attacker-token';
            }
            $response = $this->actingAs($lecturer)->get($joinUrl);
            $response->assertOk()->assertViewHas('isModerator', $expectedModerator);
            $token = $response->viewData('jitsiJwt');
            $this->assertNotEmpty($token);
            $claims = (array) JWT::decode($token, new Key(self::JITSI_TEST_SECRET, 'HS256'));
            $this->assertSame($expectedModerator, (bool) $claims['context']->user->moderator);
            $this->assertSame($lecturer->id, (int) $claims['context']->user->id);
            $this->assertSame('room-1', $claims['room']);
            $this->assertStringNotContainsString(self::JITSI_TEST_SECRET, $response->getContent() ?: '');
            $this->assertStringNotContainsString('meeting-password-secret', $response->getContent() ?: '');
        }

        $admin = $this->user('admin-jitsi-host@example.test', 2, 1);
        $adminResponse = $this->actingAs($admin)->get(route('admin.live_classes.join', $class->id));
        $adminResponse->assertOk()->assertViewHas('isModerator', true);
        $adminClaims = (array) JWT::decode($adminResponse->viewData('jitsiJwt'), new Key(self::JITSI_TEST_SECRET, 'HS256'));
        $this->assertTrue($adminClaims['context']->user->moderator);
    }

    public function test_student_jitsi_token_is_participant_only_and_other_registrations_are_denied(): void
    {
        $class = $this->liveClass(1, $this->offeringA);
        $confirmed = $this->student('confirmed-jitsi@example.test', 1);
        $this->registration($confirmed, $this->offeringA, CourseRegistration::STATUS_CONFIRMED);
        $response = $this->actingAs($confirmed)->get(route('student.live_classes.join', $class->id));
        $response->assertOk()->assertViewHas('isModerator', false);
        $claims = (array) JWT::decode($response->viewData('jitsiJwt'), new Key(self::JITSI_TEST_SECRET, 'HS256'));
        $this->assertFalse($claims['context']->user->moderator);
        $this->assertFalse($claims['context']->features->recording);
        $this->assertFalse($claims['context']->features->livestreaming);

        $denials = [
            [CourseRegistration::STATUS_REGISTERED, $this->offeringA, 1, 'registered-jitsi@example.test'],
            [CourseRegistration::STATUS_DROPPED, $this->offeringA, 1, 'dropped-jitsi@example.test'],
            [CourseRegistration::STATUS_CONFIRMED, $this->parallelOffering, 1, 'parallel-jitsi@example.test'],
            [CourseRegistration::STATUS_CONFIRMED, $this->foreignOffering, 2, 'foreign-jitsi@example.test'],
        ];
        foreach ($denials as [$status, $offeringId, $schoolId, $email]) {
            $student = $this->student($email, $schoolId);
            $this->registration($student, $offeringId, $status);
            $denied = $this->actingAs($student)->get(route('student.live_classes.join', $class->id));
            $this->assertNotSame('https://meet.example.test/room-1', $denied->headers->get('Location'));
            $this->assertStringNotContainsString('room-1', $denied->getContent() ?: '');
            $this->assertStringNotContainsString(self::JITSI_TEST_SECRET, $denied->getContent() ?: '');
        }

        $unregistered = $this->student('unregistered-jitsi@example.test', 1);
        $this->actingAs($unregistered)->get(route('student.live_classes.join', $class->id))
            ->assertRedirect()->assertDontSee('room-1');

        $disabled = $this->student('disabled-jitsi@example.test', 1);
        $this->registration($disabled, $this->offeringA, CourseRegistration::STATUS_CONFIRMED);
        DB::table('users')->where('id', $disabled->id)->update(['account_status' => 'disable']);
        $disabled->refresh();
        $disabledResponse = $this->actingAs($disabled)->get(route('student.live_classes.join', $class->id));
        $this->assertContains($disabledResponse->getStatusCode(), [302, 403]);
        $this->assertStringNotContainsString('room-1', $disabledResponse->getContent() ?: '');

        $suspended = $this->user('suspended-allocation-provider@example.test', 3, 1);
        $this->allocation($suspended, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        DB::table('users')->where('id', $suspended->id)->update(['staff_status' => 'suspended']);
        $suspended->refresh();
        $suspendedResponse = $this->actingAs($suspended)->get(route('teacher.live_classes.join', $class->id));
        $this->assertContains($suspendedResponse->getStatusCode(), [302, 403]);
        $this->assertStringNotContainsString('room-1', $suspendedResponse->getContent() ?: '');
    }

    public function test_join_window_http_boundaries_are_enforced_before_jitsi_token_delivery(): void
    {
        $class = $this->liveClass(1, $this->offeringA);
        $student = $this->student('jitsi-window@example.test', 1);
        $this->registration($student, $this->offeringA, CourseRegistration::STATUS_CONFIRMED);

        foreach ([
            ['2026-09-25 09:44:59', false],
            ['2026-09-25 09:45:00', true],
            ['2026-09-25 11:15:00', true],
            ['2026-09-25 11:15:01', false],
        ] as [$time, $allowed]) {
            Carbon::setTestNow(Carbon::parse($time, 'UTC'));
            $response = $this->actingAs($student)->get(route('student.live_classes.join', $class->id));
            if ($allowed) {
                $response->assertOk()->assertViewHas('isModerator', false);
                $claims = (array) JWT::decode($response->viewData('jitsiJwt'), new Key(self::JITSI_TEST_SECRET, 'HS256'));
                $this->assertFalse($claims['context']->user->moderator);
            } else {
                $this->assertNotSame(200, $response->getStatusCode());
                $this->assertStringNotContainsString('room-1', $response->getContent() ?: '');
                $this->assertStringNotContainsString(self::JITSI_TEST_SECRET, $response->getContent() ?: '');
            }
        }
        Carbon::setTestNow(Carbon::parse('2026-09-25 10:00:00', 'UTC'));
    }

    public function test_nonqualifying_and_cross_tenant_lecturers_never_receive_provider_tokens(): void
    {
        $class = $this->liveClass(1, $this->offeringA);
        $ended = $this->user('ended-allocation-provider@example.test', 3, 1);
        $this->allocation($ended, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER,
            CourseOfferingLecturerAllocation::STATUS_ENDED, '2026-09-01', '2026-09-24');
        $this->actingAs($ended)->get(route('teacher.live_classes.join', $class->id))
            ->assertRedirect()->assertDontSee('room-1');

        $future = $this->user('future-allocation-provider@example.test', 3, 1);
        $this->allocation($future, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER,
            CourseOfferingLecturerAllocation::STATUS_ACTIVE, '2026-09-26', null);
        $this->actingAs($future)->get(route('teacher.live_classes.join', $class->id))
            ->assertRedirect()->assertDontSee('room-1');

        $parallel = $this->user('parallel-allocation-provider@example.test', 3, 1);
        $this->allocation($parallel, $this->parallelOffering, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        $this->actingAs($parallel)->get(route('teacher.live_classes.join', $class->id))
            ->assertRedirect()->assertDontSee('room-1');

        $foreign = $this->user('foreign-allocation-provider@example.test', 3, 2);
        $this->allocation($foreign, $this->foreignOffering, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        $this->actingAs($foreign)->get(route('teacher.live_classes.join', $class->id))
            ->assertNotFound()->assertDontSee('room-1');

        $disabled = $this->user('disabled-allocation-provider@example.test', 3, 1);
        $this->allocation($disabled, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        DB::table('users')->where('id', $disabled->id)->update(['account_status' => 'disable']);
        $disabled->refresh();
        $disabledResponse = $this->actingAs($disabled)->get(route('teacher.live_classes.join', $class->id));
        $this->assertContains($disabledResponse->getStatusCode(), [302, 403]);
        $this->assertStringNotContainsString('room-1', $disabledResponse->getContent() ?: '');
    }

    public function test_recording_route_authorizes_before_redirect_and_blocks_cross_object_access(): void
    {
        $class = $this->liveClass(1, $this->offeringA, [
            'status' => LiveClass::STATUS_ENDED, 'scheduled_at' => '2026-09-25 08:00:00',
            'ends_at' => '2026-09-25 09:00:00', 'recording_url' => 'https://video.example.test/private-recording-secret',
        ]);
        $confirmed = $this->student('recording-confirmed-provider@example.test', 1);
        $this->registration($confirmed, $this->offeringA, CourseRegistration::STATUS_CONFIRMED);
        $this->actingAs($confirmed)->get(route('live_classes.recording.access', $class->id))
            ->assertRedirect('https://video.example.test/private-recording-secret');

        foreach ([
            [CourseRegistration::STATUS_REGISTERED, $this->offeringA, 1, 'recording-pending@example.test'],
            [CourseRegistration::STATUS_DROPPED, $this->offeringA, 1, 'recording-dropped@example.test'],
            [CourseRegistration::STATUS_CONFIRMED, $this->parallelOffering, 1, 'recording-parallel@example.test'],
            [CourseRegistration::STATUS_CONFIRMED, $this->foreignOffering, 2, 'recording-foreign@example.test'],
        ] as [$status, $offeringId, $schoolId, $email]) {
            $student = $this->student($email, $schoolId);
            $this->registration($student, $offeringId, $status);
            $response = $this->actingAs($student)->get(route('live_classes.recording.access', $class->id));
            $this->assertNotSame('https://video.example.test/private-recording-secret', $response->headers->get('Location'));
            $this->assertStringNotContainsString('private-recording-secret', $response->getContent() ?: '');
        }

        $parallelClass = $this->liveClass(2, $this->parallelOffering, [
            'status' => LiveClass::STATUS_ENDED, 'scheduled_at' => '2026-09-25 08:00:00',
            'ends_at' => '2026-09-25 09:00:00', 'recording_url' => 'https://video.example.test/other-recording-secret',
        ]);
        $this->actingAs($confirmed)->get(route('live_classes.recording.access', $parallelClass->id))
            ->assertForbidden()->assertDontSee('other-recording-secret');
    }

    public function test_authorized_edit_form_is_the_only_class_page_with_provider_fields_and_tampering_does_not_elevate_ta(): void
    {
        $class = $this->liveClass(1, $this->offeringA);
        $primary = $this->user('primary-provider-edit@example.test', 3, 1);
        $this->allocation($primary, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        $this->actingAs($primary)->get(route('teacher.live_classes.edit', $class->id))
            ->assertOk()->assertDontSee('https://meet.example.test/room-1')->assertDontSee('meeting-password-secret');

        $ta = $this->user('ta-provider-tamper@example.test', 3, 1);
        $this->allocation($ta, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_TEACHING_ASSISTANT);
        $payload = [
            'title' => 'Injected provider class', 'platform' => 'jitsi', 'meeting_url' => 'https://attacker.example.test/secret-room',
            'meeting_id' => 'injected-id', 'meeting_password' => 'injected-password',
            'start_date' => '2026-09-25', 'start_time' => '10:00', 'end_time' => '11:00', 'timezone' => 'UTC',
            'status' => 'scheduled', 'is_published' => 1, 'recording_url' => 'https://attacker.example.test/recording-secret',
            'course_offering_id' => $this->parallelOffering, 'moderator' => true, 'is_moderator' => true,
            'room_name' => 'attacker-room', 'token' => 'attacker-token', 'host_identity' => $ta->id,
        ];
        $this->actingAs($ta)->put(route('teacher.live_classes.update', $class->id), $payload)->assertForbidden();
        $this->assertDatabaseHas('live_classes', [
            'id' => $class->id, 'course_offering_id' => $this->offeringA,
            'meeting_url' => 'https://meet.example.test/room-1', 'meeting_password' => 'meeting-password-secret',
            'recording_url' => 'https://video.example.test/recording-provider-secret',
        ]);
    }

    public function test_offering_provider_creation_failure_is_safe_and_creation_is_host_only(): void
    {
        $primary = $this->user('primary-provider-create@example.test', 3, 1);
        $this->allocation($primary, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER);
        Http::fake(['zoom.us/*' => Http::response(['error' => 'provider-secret-response'], 503)]);
        Log::spy();
        $zoomPayload = $this->offeringPayload('zoom');
        $zoomPayload['teacher_id'] = $primary->id;
        $failed = $this->actingAs($primary)->from(route('admin.course_offerings.live_classes.create', $this->offeringA))
            ->post(route('admin.course_offerings.live_classes.store', $this->offeringA), $zoomPayload);
        $failed->assertRedirect()->assertSessionHasErrors('meeting_url');
        $failureText = session('errors')->first('meeting_url');
        foreach (['provider-secret-response', 'nonproduction-zoom-secret', 'access_token'] as $secret) {
            $this->assertStringNotContainsString($secret, $failureText);
        }
        $this->assertSame(0, DB::table('live_classes')->count());
        Log::shouldHaveReceived('warning')->once();

        $ta = $this->user('ta-provider-create@example.test', 3, 1);
        $this->allocation($ta, $this->offeringA, CourseOfferingLecturerAllocation::ROLE_TEACHING_ASSISTANT);
        Http::fake();
        $unauthorizedPayload = $this->offeringPayload('google_meet');
        $unauthorizedPayload['teacher_id'] = $ta->id;
        $this->actingAs($ta)->post(route('admin.course_offerings.live_classes.store', $this->offeringA), $unauthorizedPayload)
            ->assertForbidden();
        Http::assertNothingSent();

        $googlePayload = $this->offeringPayload('google_meet');
        $googlePayload['teacher_id'] = $primary->id;
        $googlePayload['meeting_url'] = 'https://meet.google.com/provider-created-room';
        $created = $this->actingAs($primary)->post(route('admin.course_offerings.live_classes.store', $this->offeringA), $googlePayload);
        $created->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('live_classes')->where('course_offering_id', $this->offeringA)->count());
        $this->assertStringNotContainsString('provider-created-room', $created->getContent() ?: '');
    }

    public function test_provider_failure_logging_uses_only_safe_context_and_new_access_after_loss_is_denied(): void
    {
        $class = $this->liveClass(1, $this->offeringA);
        $student = $this->student('provider-loss-student@example.test', 1);
        $registrationId = $this->registration($student, $this->offeringA, CourseRegistration::STATUS_CONFIRMED);
        $valid = $this->actingAs($student)->get(route('student.live_classes.join', $class->id));
        $valid->assertOk();
        DB::table('course_registrations')->where('id', $registrationId)->update(['status' => CourseRegistration::STATUS_DROPPED]);
        $denied = $this->actingAs($student)->get(route('student.live_classes.join', $class->id));
        $this->assertNotSame(200, $denied->getStatusCode());
        $this->assertStringNotContainsString('room-1', $denied->getContent() ?: '');

        $row = DB::table('audit_logs')->orderByDesc('id')->first();
        if ($row) {
            $audit = json_encode($row);
            foreach (['meeting-password-secret', self::JITSI_TEST_SECRET, 'access_token'] as $secret) {
                $this->assertStringNotContainsString($secret, $audit);
            }
        }

        $lecturer = $this->user('host-access-loss@example.test', 3, 1);
        $allocationId = DB::table('course_offering_lecturer_allocations')->insertGetId([
            'school_id' => 1, 'course_offering_id' => $this->offeringA, 'user_id' => $lecturer->id,
            'role' => CourseOfferingLecturerAllocation::ROLE_PRIMARY_LECTURER,
            'status' => CourseOfferingLecturerAllocation::STATUS_ACTIVE,
            'starts_on' => '2026-09-01', 'ends_on' => null,
        ]);
        $hostResponse = $this->actingAs($lecturer)->get(route('teacher.live_classes.join', $class->id));
        $hostResponse->assertOk()->assertViewHas('isModerator', true);
        DB::table('course_offering_lecturer_allocations')->where('id', $allocationId)->update([
            'status' => CourseOfferingLecturerAllocation::STATUS_ENDED, 'ends_on' => '2026-09-25',
        ]);
        $afterLoss = $this->actingAs($lecturer)->get(route('teacher.live_classes.join', $class->id));
        $this->assertNotSame(200, $afterLoss->getStatusCode());
        $this->assertStringNotContainsString('room-1', $afterLoss->getContent() ?: '');
    }

    public function test_model_serialization_and_denial_pages_do_not_expose_offering_provider_secrets(): void
    {
        $class = $this->liveClass(1, $this->offeringA);
        $serialized = json_encode($class->fresh());
        foreach (['https://meet.example.test/room-1', 'meeting-id-secret', 'meeting-password-secret', 'recording-provider-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $serialized);
        }
        $unauthorized = $this->user('unauthorized-provider-view@example.test', 3, 1);
        $show = $this->actingAs($unauthorized)->get(route('teacher.live_classes.show', $class->id));
        $show->assertForbidden();
        foreach (['room-1', 'meeting-id-secret', 'meeting-password-secret', 'recording-provider-secret', self::JITSI_TEST_SECRET] as $secret) {
            $this->assertStringNotContainsString($secret, $show->getContent() ?: '');
        }
    }

    private function subject(int $schoolId, string $name): int
    {
        return (int) DB::table('subjects')->insertGetId([
            'school_id' => $schoolId, 'name' => $name, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function offering(int $schoolId, int $subjectId): int
    {
        return (int) DB::table('course_offerings')->insertGetId([
            'school_id' => $schoolId, 'subject_id' => $subjectId, 'status' => CourseOffering::STATUS_OPEN,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function user(string $email, int $roleId, int $schoolId): User
    {
        return User::create([
            'name' => $email, 'email' => $email, 'role_id' => $roleId,
            'school_id' => $schoolId, 'status' => 1, 'account_status' => 'active',
        ]);
    }

    private function student(string $email, int $schoolId): User
    {
        return $this->user($email, 7, $schoolId);
    }

    private function allocation(User $user, int $offeringId, string $role, string $status = 'active', string $startsOn = '2026-09-01', ?string $endsOn = null): void
    {
        DB::table('course_offering_lecturer_allocations')->insert([
            'school_id' => $user->school_id, 'course_offering_id' => $offeringId, 'user_id' => $user->id,
            'role' => $role, 'status' => $status, 'starts_on' => $startsOn, 'ends_on' => $endsOn,
        ]);
    }

    private function registration(User $student, int $offeringId, string $status): int
    {
        return (int) DB::table('course_registrations')->insertGetId([
            'school_id' => $student->school_id, 'student_id' => $student->id, 'course_offering_id' => $offeringId,
            'subject_id' => $student->school_id === 1 ? $this->subjectA : null, 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function liveClass(int $id, int $offeringId, array $overrides = []): LiveClass
    {
        $row = array_merge([
            'id' => $id, 'school_id' => 1, 'course_offering_id' => $offeringId, 'subject_id' => $this->subjectA,
            'title' => 'Provider class '.$id, 'description' => 'Provider security fixture', 'platform' => 'jitsi',
            'meeting_url' => 'https://meet.example.test/room-'.$id, 'meeting_id' => 'meeting-id-secret',
            'meeting_password' => 'meeting-password-secret', 'recording_url' => 'https://video.example.test/recording-provider-secret',
            'scheduled_at' => '2026-09-25 10:00:00', 'ends_at' => '2026-09-25 11:00:00',
            'start_date' => '2026-09-25', 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'timezone' => 'UTC',
            'status' => LiveClass::STATUS_SCHEDULED, 'is_published' => 1, 'attendance_enabled' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ], $overrides);
        DB::table('live_classes')->insert($row);
        return LiveClass::query()->findOrFail($id);
    }

    private function offeringPayload(string $platform): array
    {
        return [
            'title' => 'Offering provider creation', 'teacher_id' => null, 'platform' => $platform,
            'start_date' => '2026-09-26', 'start_time' => '11:00', 'end_time' => '12:00', 'timezone' => 'UTC',
            'status' => 'scheduled', 'is_published' => 0,
        ];
    }
}
