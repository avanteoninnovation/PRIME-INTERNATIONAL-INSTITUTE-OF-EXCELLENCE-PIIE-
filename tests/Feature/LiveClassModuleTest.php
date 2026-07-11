<?php

namespace Tests\Feature;

use App\Models\GlobalSettings;
use App\Models\User;
use App\Policies\LiveClassPolicy;
use Database\Seeders\LiveClassPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LiveClassModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_administrator_can_create_live_class(): void
    {
        $this->guardRequiredSchema();
        $admin = $this->createUser(2, 1);
        $context = $this->createAcademicContext(1);

        $response = $this->actingAs($admin)->post(route('admin.live_classes.store'), [
            'title' => 'Admin Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'jitsi',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
            'is_published' => 1,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('live_classes', ['title' => 'Admin Class', 'school_id' => 1]);
    }

    public function test_lecturer_with_permission_can_create_for_assigned_course(): void
    {
        $this->guardRequiredSchema();
        $lecturer = $this->createUser(3, 1);
        $context = $this->createAcademicContext(1);

        DB::table('teacher_permissions')->insert([
            'class_id' => $context['class_id'],
            'section_id' => 0,
            'school_id' => 1,
            'teacher_id' => $lecturer->id,
            'marks' => 1,
            'attendance' => 1,
            'updated_at' => time(),
        ]);

        $response = $this->actingAs($lecturer)->post(route('admin.live_classes.store'), [
            'title' => 'Lecturer Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'jitsi',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'timezone' => 'UTC',
            'is_published' => 1,
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('live_classes', ['title' => 'Lecturer Class', 'teacher_id' => $lecturer->id]);
    }

    public function test_meeting_link_is_auto_generated_when_missing(): void
    {
        $this->guardRequiredSchema();
        $admin = $this->createUser(2, 1);
        $context = $this->createAcademicContext(1);

        $response = $this->actingAs($admin)->post(route('admin.live_classes.store'), [
            'title' => 'Auto Link Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'jitsi',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
            'is_published' => 1,
        ]);

        $response->assertStatus(302);
        $meetingUrl = DB::table('live_classes')->where('title', 'Auto Link Class')->value('meeting_url');

        $this->assertNotEmpty($meetingUrl);
        $this->assertStringStartsWith('https://', $meetingUrl);
    }

    public function test_google_meet_without_url_is_rejected(): void
    {
        $this->guardRequiredSchema();
        $admin = $this->createUser(2, 1);
        $context = $this->createAcademicContext(1);

        $response = $this->actingAs($admin)
            ->from(route('admin.live_classes.create'))
            ->post(route('admin.live_classes.store'), [
                'title' => 'Google Meet No URL',
                'subject_id' => $context['subject_id'],
                'class_id' => $context['class_id'],
                'platform' => 'google_meet',
                'start_date' => now()->addDay()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'timezone' => 'UTC',
                'is_published' => 1,
            ]);

        $response->assertSessionHasErrors(['meeting_url']);
    }

    public function test_meet_now_creates_live_class_with_link(): void
    {
        $this->guardRequiredSchema();
        $admin = $this->createUser(2, 1);
        $context = $this->createAcademicContext(1);

        $response = $this->actingAs($admin)->post(route('admin.live_classes.meet_now'), [
            'title' => 'Instant Session',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'jitsi',
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('live_classes', [
            'title' => 'Instant Session',
            'is_published' => 1,
            'platform' => 'jitsi',
        ]);

        $meetingUrl = DB::table('live_classes')->where('title', 'Instant Session')->value('meeting_url');
        $this->assertNotEmpty($meetingUrl);
        $this->assertStringStartsWith('https://', $meetingUrl);
    }

    public function test_lecturer_cannot_modify_another_lecturer_class_without_permission(): void
    {
        $this->guardRequiredSchema();
        $owner = $this->createUser(3, 1);
        $other = $this->createUser(3, 1);
        $context = $this->createAcademicContext(1);

        $classId = DB::table('live_classes')->insertGetId([
            'school_id' => 1,
            'title' => 'Owned Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'teacher_id' => $owner->id,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/owned-room',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'is_published' => 1,
            'created_by' => $owner->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($other)->put(route('admin.live_classes.update', $classId), [
            'title' => 'Hijacked',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'jitsi',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(403);
    }

    public function test_lecturer_can_publish_own_live_class(): void
    {
        $this->guardRequiredSchema();
        $lecturer = $this->createUser(3, 1);
        $context = $this->createAcademicContext(1);

        $liveClassId = DB::table('live_classes')->insertGetId([
            'school_id' => 1,
            'title' => 'Teacher Publish Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'teacher_id' => $lecturer->id,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/teacher-publish-room',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'timezone' => 'UTC',
            'status' => 'draft',
            'is_published' => 0,
            'created_by' => $lecturer->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($lecturer)->post(route('teacher.live_classes.publish', $liveClassId));

        $response->assertStatus(302);
        $this->assertDatabaseHas('live_classes', [
            'id' => $liveClassId,
            'is_published' => 1,
        ]);
    }

    public function test_student_cannot_create_live_class(): void
    {
        $this->guardRequiredSchema();
        $student = $this->createUser(7, 1);
        $context = $this->createAcademicContext(1);

        $response = $this->actingAs($student)->post(route('admin.live_classes.store'), [
            'title' => 'Student Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'jitsi',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
        ]);

        $response->assertStatus(302);
    }

    public function test_student_can_view_published_class_for_enrolled_course(): void
    {
        $this->guardRequiredSchema();
        $student = $this->createUser(7, 1);
        $context = $this->createAcademicContext(1);

        DB::table('enrollment')->insert([
            'user_id' => $student->id,
            'class_id' => $context['class_id'],
            'section_id' => 0,
            'school_id' => 1,
            'department_id' => 0,
            'session_id' => $context['session_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('live_classes')->insert([
            'school_id' => 1,
            'title' => 'Published Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'teacher_id' => $this->createUser(3, 1)->id,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/published-room',
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'start_date' => now()->format('Y-m-d'),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'is_published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.live_classes.index'));
        $response->assertOk()->assertSee('Published Class');
    }

    public function test_student_cannot_view_unpublished_class(): void
    {
        $this->guardRequiredSchema();
        $student = $this->createUser(7, 1);
        $context = $this->createAcademicContext(1);

        DB::table('enrollment')->insert([
            'user_id' => $student->id,
            'class_id' => $context['class_id'],
            'section_id' => 0,
            'school_id' => 1,
            'department_id' => 0,
            'session_id' => $context['session_id'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('live_classes')->insert([
            'school_id' => 1,
            'title' => 'Draft Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'teacher_id' => $this->createUser(3, 1)->id,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/draft-room',
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'start_date' => now()->format('Y-m-d'),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'timezone' => 'UTC',
            'status' => 'draft',
            'is_published' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.live_classes.index'));
        $response->assertOk()->assertDontSee('Draft Class');
    }

    public function test_student_cannot_view_class_for_different_session(): void
    {
        $this->guardRequiredSchema();

        if (!Schema::hasColumn('live_classes', 'academic_session_id')) {
            $this->markTestSkipped('live_classes.academic_session_id is required for session-targeted filtering.');
        }

        $student = $this->createUser(7, 1);
        $contextA = $this->createAcademicContext(1);
        $contextB = $this->createAcademicContext(1);

        $this->enrollStudent($student->id, $contextA['class_id'], $contextA['session_id']);

        DB::table('live_classes')->insert([
            'school_id' => 1,
            'title' => 'Other Session Class',
            'subject_id' => $contextA['subject_id'],
            'class_id' => $contextA['class_id'],
            'academic_session_id' => $contextB['session_id'],
            'teacher_id' => $this->createUser(3, 1)->id,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/other-session-room',
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'start_date' => now()->format('Y-m-d'),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'is_published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.live_classes.index'));
        $response->assertOk()->assertDontSee('Other Session Class');
    }

    public function test_student_cannot_view_subject_targeted_for_other_class(): void
    {
        $this->guardRequiredSchema();
        $student = $this->createUser(7, 1);
        $contextA = $this->createAcademicContext(1);
        $contextB = $this->createAcademicContext(1);

        $this->enrollStudent($student->id, $contextA['class_id'], $contextA['session_id']);

        DB::table('live_classes')->insert([
            'school_id' => 1,
            'title' => 'Other Class Subject Class',
            'subject_id' => $contextB['subject_id'],
            'class_id' => null,
            'teacher_id' => $this->createUser(3, 1)->id,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/other-class-subject-room',
            'scheduled_at' => now()->addHour()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
            'start_date' => now()->format('Y-m-d'),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'is_published' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('student.live_classes.index'));
        $response->assertOk()->assertDontSee('Other Class Subject Class');
    }

    public function test_unauthorized_user_cannot_access_management_routes(): void
    {
        $this->guardRequiredSchema();
        $parent = $this->createUser(6, 1);
        $response = $this->actingAs($parent)->get(route('admin.live_classes.index'));
        $response->assertStatus(302);
    }

    public function test_invalid_date_time_is_rejected(): void
    {
        $this->guardRequiredSchema();
        $admin = $this->createUser(2, 1);
        $context = $this->createAcademicContext(1);

        $response = $this->actingAs($admin)->from(route('admin.live_classes.create'))->post(route('admin.live_classes.store'), [
            'title' => 'Bad Time Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'jitsi',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '11:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
        ]);

        $response->assertSessionHasErrors(['end_time']);
    }

    public function test_invalid_meeting_url_is_rejected(): void
    {
        $this->guardRequiredSchema();
        $admin = $this->createUser(2, 1);
        $context = $this->createAcademicContext(1);

        $response = $this->actingAs($admin)->from(route('admin.live_classes.create'))->post(route('admin.live_classes.store'), [
            'title' => 'Bad URL Class',
            'subject_id' => $context['subject_id'],
            'class_id' => $context['class_id'],
            'platform' => 'zoom',
            'meeting_url' => 'http://zoom.us/bad-url',
            'start_date' => now()->addDay()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'timezone' => 'UTC',
        ]);

        $response->assertSessionHasErrors(['meeting_url']);
    }

    public function test_ended_class_cannot_be_joined(): void
    {
        $this->guardRequiredSchema();
        $student = $this->createUser(7, 1);
        $context = $this->createAcademicContext(1);
        $this->enrollStudent($student->id, $context['class_id'], $context['session_id']);

        $liveClassId = $this->createLiveClass(1, $context['subject_id'], $context['class_id'], 'ended', 1);

        $response = $this->actingAs($student)->get(route('student.live_classes.join', $liveClassId));
        $response->assertStatus(302);
    }

    public function test_cancelled_class_cannot_be_joined(): void
    {
        $this->guardRequiredSchema();
        $student = $this->createUser(7, 1);
        $context = $this->createAcademicContext(1);
        $this->enrollStudent($student->id, $context['class_id'], $context['session_id']);

        $liveClassId = $this->createLiveClass(1, $context['subject_id'], $context['class_id'], 'cancelled', 1);

        $response = $this->actingAs($student)->get(route('student.live_classes.join', $liveClassId));
        $response->assertStatus(302);
    }

    public function test_student_can_open_live_jitsi_class_inside_app(): void
    {
        $this->guardRequiredSchema();
        $student = $this->createUser(7, 1);
        $context = $this->createAcademicContext(1);
        $this->enrollStudent($student->id, $context['class_id'], $context['session_id']);

        $liveClassId = $this->createLiveClass(1, $context['subject_id'], $context['class_id'], 'live', 1);

        $response = $this->actingAs($student)->get(route('student.live_classes.join', $liveClassId));
        $response->assertOk()->assertSee('Live Meeting Room');
    }

    public function test_permission_seeder_adds_permissions_without_destroying_existing(): void
    {
        $this->guardSettingsSchema();

        if (Schema::hasColumn('roles', 'role_id')) {
            DB::table('roles')->updateOrInsert(
                ['role_id' => 2],
                ['name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]
            );
        } else {
            DB::table('roles')->updateOrInsert(
                ['id' => 2],
                ['name' => 'Admin', 'created_at' => now(), 'updated_at' => now()]
            );
        }

        GlobalSettings::updateOrCreate(
            ['key' => 'role_perm_2'],
            ['value' => json_encode(['View Dashboard'])]
        );

        Artisan::call('db:seed', ['--class' => LiveClassPermissionSeeder::class]);

        $stored = json_decode(GlobalSettings::where('key', 'role_perm_2')->value('value'), true);
        $this->assertContains('View Dashboard', $stored);
        $this->assertContains('View Live Classes', $stored);
    }

    public function test_platform_configuration_is_restricted_to_authorized_admins(): void
    {
        $this->guardRequiredSchema();

        $admin = $this->createUser(2, 1);
        $lecturer = $this->createUser(3, 1);

        $policy = new LiveClassPolicy();

        $this->assertTrue($policy->managePlatforms($admin));
        $this->assertFalse($policy->managePlatforms($lecturer));
    }

    private function guardRequiredSchema(): void
    {
        $required = ['users', 'classes', 'subjects', 'sessions', 'enrollment', 'live_classes', 'teacher_permissions'];
        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("Required table missing: {$table}");
            }
        }

        $requiredLiveClassColumns = ['start_date', 'start_time', 'end_time', 'is_published', 'created_by', 'updated_by'];
        foreach ($requiredLiveClassColumns as $column) {
            if (!Schema::hasColumn('live_classes', $column)) {
                $this->markTestSkipped("Run live class migration first. Missing column: live_classes.{$column}");
            }
        }
    }

    private function guardSettingsSchema(): void
    {
        if (!Schema::hasTable('global_settings') || !Schema::hasTable('roles')) {
            $this->markTestSkipped('Required settings schema is missing.');
        }
    }

    private function createUser(int $roleId, int $schoolId): User
    {
        $data = [
            'name' => 'User ' . $roleId . ' ' . uniqid(),
            'email' => 'u' . uniqid() . '@example.test',
            'role_id' => (string) $roleId,
            'school_id' => $schoolId,
            'password' => bcrypt('password'),
            'code' => 'C' . mt_rand(1000, 9999),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('users', 'account_status')) {
            $data['account_status'] = 'active';
        }

        if (Schema::hasColumn('users', 'status')) {
            $data['status'] = 1;
        }

        DB::table('users')->insert($data);

        return User::where('email', $data['email'])->firstOrFail();
    }

    private function createAcademicContext(int $schoolId): array
    {
        $sessionId = DB::table('sessions')->insertGetId([
            'session_title' => 'Session ' . uniqid(),
            'status' => 1,
            'school_id' => $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $classId = DB::table('classes')->insertGetId([
            'name' => 'Class ' . uniqid(),
            'school_id' => $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $subjectData = [
            'name' => 'Subject ' . uniqid(),
            'class_id' => $classId,
            'school_id' => $schoolId,
            'session_id' => $sessionId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('subjects', 'credits')) {
            $subjectData['credits'] = 3;
        }
        if (Schema::hasColumn('subjects', 'course_type')) {
            $subjectData['course_type'] = 'compulsory';
        }
        if (Schema::hasColumn('subjects', 'pass_mark')) {
            $subjectData['pass_mark'] = 50;
        }

        $subjectId = DB::table('subjects')->insertGetId($subjectData);

        return [
            'session_id' => $sessionId,
            'class_id' => $classId,
            'subject_id' => $subjectId,
        ];
    }

    private function enrollStudent(int $studentId, int $classId, int $sessionId): void
    {
        DB::table('enrollment')->insert([
            'user_id' => $studentId,
            'class_id' => $classId,
            'section_id' => 0,
            'school_id' => 1,
            'department_id' => 0,
            'session_id' => $sessionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createLiveClass(int $schoolId, int $subjectId, int $classId, string $status, int $published): int
    {
        $startsAt = now()->subDays(2);
        $endsAt = now()->subDays(2)->addHour();

        if ($status === 'scheduled') {
            $startsAt = now()->addHour();
            $endsAt = now()->addHours(2);
        }

        if ($status === 'live') {
            $startsAt = now()->subMinutes(10);
            $endsAt = now()->addMinutes(50);
        }

        return DB::table('live_classes')->insertGetId([
            'school_id' => $schoolId,
            'title' => 'Class ' . uniqid(),
            'subject_id' => $subjectId,
            'class_id' => $classId,
            'teacher_id' => $this->createUser(3, $schoolId)->id,
            'platform' => 'jitsi',
            'meeting_url' => 'https://meet.jit.si/' . uniqid('room-'),
            'scheduled_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'start_date' => $startsAt->format('Y-m-d'),
            'start_time' => $startsAt->format('H:i:s'),
            'end_time' => $endsAt->format('H:i:s'),
            'timezone' => 'UTC',
            'status' => $status,
            'is_published' => $published,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
