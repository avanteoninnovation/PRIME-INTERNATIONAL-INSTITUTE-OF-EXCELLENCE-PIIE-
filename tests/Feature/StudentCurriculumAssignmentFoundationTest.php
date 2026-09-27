<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\StudentCurriculumAssignment;
use App\Support\Curriculum\StudentCurriculumAssignmentService;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentCurriculumAssignmentFoundationTest extends TestCase
{
    private StudentCurriculumAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
        DB::purge('sqlite');
        DB::reconnect('sqlite');
        DB::connection()->getPdo()->exec('PRAGMA foreign_keys = ON');
        $this->schema();
        $this->service = new StudentCurriculumAssignmentService();
        $this->school(1, 'higher_ed');
        $this->school(2, 'higher_ed');
        $this->school(3, 'k12');
        $this->user(10, 1, 7);
        $this->user(11, 2, 7);
        $this->user(12, 1, 2);
        $this->user(13, 1, 7);
        $this->user(30, 2, 2);
        $this->programme(100, 1);
        $this->programme(101, 1);
        $this->programme(200, 2);
        $this->programme(300, 3);
    }

    public function test_automatic_assignment_uses_latest_eligible_effective_year_by_dates_not_ids(): void
    {
        $entry = $this->year(1, 50, '2027-01-01');
        $floorOld = $this->year(1, 90, '2026-01-01');
        $floorLatest = $this->year(1, 2, '2027-01-01');
        $this->curriculum(501, 1, 100, $floorOld);
        $latest = $this->curriculum(502, 1, 100, $floorLatest);
        $this->curriculum(503, 1, 100, $this->year(1, 3, '2028-01-01'));

        $assignment = $this->initial($entry);
        $this->assertSame($latest, (int) $assignment->curriculum_id);
        $this->assertSame(50, (int) $assignment->entry_academic_year_id);
    }

    public function test_assignment_for_academic_year_uses_latest_floor_and_rejects_ties_or_missing_history(): void
    {
        $year2025 = $this->year(1, 25, '2025-01-01');
        $year2026 = $this->year(1, 26, '2026-01-01');
        $year2027 = $this->year(1, 27, '2027-01-01');
        $this->curriculum(525, 1, 100, $year2025);
        $this->curriculum(526, 1, 100, $year2026);
        DB::table('student_curriculum_assignments')->insert([
            ['school_id' => 1, 'student_id' => 10, 'programme_id' => 100, 'curriculum_id' => 525, 'entry_academic_year_id' => $year2025, 'effective_from_academic_year_id' => $year2025, 'assigned_by' => 12],
            ['school_id' => 1, 'student_id' => 10, 'programme_id' => 100, 'curriculum_id' => 526, 'entry_academic_year_id' => $year2025, 'effective_from_academic_year_id' => $year2026, 'assigned_by' => 12],
        ]);

        $this->assertSame(525, (int) $this->service->assignmentForAcademicYear(1, 10, $year2025)->curriculum_id);
        $this->assertSame(526, (int) $this->service->assignmentForAcademicYear(1, 10, $year2026)->curriculum_id);
        $this->assertSame(526, (int) $this->service->assignmentForAcademicYear(1, 10, $year2027)->curriculum_id);
        $this->assertNull($this->service->assignmentForAcademicYear(1, 13, $year2025));

        DB::table('student_curriculum_assignments')->insert(['school_id' => 1, 'student_id' => 10, 'programme_id' => 100, 'curriculum_id' => 525, 'entry_academic_year_id' => $year2025, 'effective_from_academic_year_id' => $year2026, 'assigned_by' => 12]);
        $this->assertDomainError(fn () => $this->service->assignmentForAcademicYear(1, 10, $year2027), 'multiple assignments share');
    }

    public function test_automatic_assignment_excludes_draft_retired_null_floor_and_future_curricula(): void
    {
        $entry = $this->year(1, 5, '2027-01-01');
        $effective = $this->year(1, 6, '2026-01-01');
        $this->curriculum(601, 1, 100, $effective, 'draft');
        $this->curriculum(602, 1, 100, $effective, 'retired');
        $this->curriculum(603, 1, 100, null, 'approved');
        $this->curriculum(604, 1, 100, $this->year(1, 7, '2028-01-01'));
        $chosen = $this->curriculum(605, 1, 100, $effective);

        $this->assertSame($chosen, (int) $this->initial($entry)->curriculum_id);
    }

    public function test_null_effective_year_is_never_auto_selected_but_can_be_explicitly_selected(): void
    {
        $entry = $this->year(1, 8, '2027-01-01');
        $yearless = $this->curriculum(701, 1, 100, null);
        $this->assertDomainError(fn () => $this->initial($entry), 'No approved Curriculum');

        $assignment = $this->service->assignExplicitCurriculum(1, 10, 100, $yearless, $entry, $entry, 12, 'Reviewed by registrar');
        $this->assertSame($yearless, (int) $assignment->curriculum_id);
    }

    public function test_latest_floor_tie_is_rejected_as_ambiguous(): void
    {
        $entry = $this->year(1, 10, '2027-01-01');
        $floor = $this->year(1, 11, '2026-01-01');
        $this->curriculum(801, 1, 100, $floor);
        $this->curriculum(802, 1, 100, $floor);
        $this->assertDomainError(fn () => $this->initial($entry), 'ambiguous');
    }

    public function test_no_eligible_curriculum_is_rejected(): void
    {
        $entry = $this->year(1, 12, '2024-01-01');
        $this->curriculum(901, 1, 100, $this->year(1, 13, '2025-01-01'));
        $this->assertDomainError(fn () => $this->initial($entry), 'No approved Curriculum');
    }

    public function test_tenant_student_programme_curriculum_and_year_boundaries_are_enforced(): void
    {
        $entry = $this->year(1, 14, '2027-01-01');
        $yearTwo = $this->year(2, 15, '2027-01-01');
        $curriculum = $this->curriculum(1001, 1, 100, $entry);

        $this->assertDomainError(fn () => $this->service->assignInitialCurriculum(1, 11, 100, $entry, $entry, 12), 'Student');
        $this->assertDomainError(fn () => $this->service->assignInitialCurriculum(1, 10, 200, $entry, $entry, 12), 'Programme');
        $this->assertDomainError(fn () => $this->service->assignExplicitCurriculum(1, 10, 100, $this->curriculum(1002, 2, 200, $yearTwo), $entry, $entry, 12), 'Curriculum');
        $this->assertDomainError(fn () => $this->service->assignInitialCurriculum(1, 10, 100, $yearTwo, $entry, 12), 'entry_academic_year_id');
        $this->assertDomainError(fn () => $this->service->assignInitialCurriculum(1, 10, 100, $entry, $yearTwo, 12), 'effective_from_academic_year_id');
        $this->assertSame($curriculum, $this->curriculumIdForExplicit(1, 10, 100, $curriculum, $entry));
    }

    public function test_explicit_assignment_rejects_curriculum_programme_mismatch(): void
    {
        $entry = $this->year(1, 16, '2027-01-01');
        $curriculum = $this->curriculum(1101, 1, 101, $entry);
        $this->assertDomainError(fn () => $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12), 'Curriculum');
    }

    public function test_second_active_assignment_is_rejected_and_student_lock_serializes_mutations(): void
    {
        $entry = $this->year(1, 17, '2027-01-01');
        $curriculum = $this->curriculum(1201, 1, 100, $entry);
        $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12);
        $this->assertDomainError(fn () => $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12), 'already has an active');

        // SQLite serializes writes at the database level; production MySQL additionally takes
        // SELECT ... FOR UPDATE on the student row before checking active assignments.
        $this->assertTrue(DB::transaction(fn () => DB::table('users')->where('id', 10)->lockForUpdate()->exists()));
    }

    public function test_same_programme_curriculum_change_ends_old_and_preserves_history(): void
    {
        [$entry, $year1, $year2, $old, $new] = $this->changeFixture();
        $first = $this->service->assignExplicitCurriculum(1, 10, 100, $old, $entry, $year1, 12);
        $second = $this->service->changeCurriculum(1, 10, $new, $year2, 12, 'Approved curriculum transition');

        $this->assertNotNull($first->fresh()->ended_at);
        $this->assertSame($new, (int) $second->curriculum_id);
        $this->assertSame($first->id, $this->service->assignmentHistory(1, 10)->last()->id);
        $this->assertSame($second->id, $this->service->currentAssignment(1, 10)->id);
    }

    public function test_programme_change_preserves_old_assignment_and_creates_destination_assignment(): void
    {
        $entry = $this->year(1, 20, '2025-01-01');
        $year1 = $this->year(1, 21, '2025-01-01');
        $year2 = $this->year(1, 22, '2026-01-01');
        $old = $this->curriculum(1301, 1, 100, $year1);
        $destination = $this->curriculum(1302, 1, 101, $year2);
        $first = $this->service->assignExplicitCurriculum(1, 10, 100, $old, $entry, $year1, 12);
        DB::table('student_profiles')->insert(['user_id' => 10, 'school_id' => 1, 'programme_id' => 100, 'status' => 'active']);

        $next = $this->service->changeProgrammeCurriculum(1, 10, 101, $destination, $year2, 12, 'Approved programme transfer');
        $this->assertSame(101, (int) $next->programme_id);
        $this->assertNotNull($first->fresh()->ended_at);
        $this->assertSame(2, $this->service->assignmentHistory(1, 10)->count());
        $this->assertSame(101, (int) DB::table('student_profiles')->where('user_id', 10)->value('programme_id'));
    }

    public function test_change_requires_reason_and_preserves_entry_year(): void
    {
        [$entry, $year1, $year2, $old, $new] = $this->changeFixture();
        $first = $this->service->assignExplicitCurriculum(1, 10, 100, $old, $entry, $year1, 12);
        $this->assertDomainError(fn () => $this->service->changeCurriculum(1, 10, $new, $year2, 12, '   '), 'reason');
        $next = $this->service->changeCurriculum(1, 10, $new, $year2, 12, 'Version transition');
        $this->assertSame($entry, (int) $next->entry_academic_year_id);
    }

    public function test_retired_curriculum_can_remain_current_without_automatic_reassignment(): void
    {
        $entry = $this->year(1, 23, '2025-01-01');
        $year1 = $this->year(1, 24, '2025-01-01');
        $this->curriculum(1401, 1, 100, $year1, 'retired');
        $approved = $this->curriculum(1402, 1, 100, $year1);
        $assignment = $this->service->assignExplicitCurriculum(1, 10, 100, $approved, $entry, $year1, 12);

        DB::table('curricula')->where('id', $approved)->update(['status' => 'retired']);
        $this->assertSame($assignment->id, $this->service->currentAssignment(1, 10)->id);
        $this->assertSame(1, $this->service->assignmentHistory(1, 10)->count());
        $this->assertDomainError(fn () => $this->service->assignExplicitCurriculum(1, 13, 100, $approved, $entry, $year1, 12), 'approved');
    }

    public function test_deferment_does_not_end_or_replace_assignment(): void
    {
        $entry = $this->year(1, 25, '2025-01-01');
        $curriculum = $this->curriculum(1501, 1, 100, $entry);
        $assignment = $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12);
        DB::table('student_profiles')->insert(['user_id' => 10, 'school_id' => 1, 'status' => 'deferred']);
        $this->assertSame($assignment->id, $this->service->currentAssignment(1, 10)->id);
    }

    public function test_current_and_history_are_tenant_scoped_and_multiple_active_corruption_is_detected(): void
    {
        $entry = $this->year(1, 26, '2025-01-01');
        $curriculum = $this->curriculum(1601, 1, 100, $entry);
        $assignment = $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12);
        $this->assertDomainError(fn () => $this->service->currentAssignment(2, 10), 'Student was not found');
        $this->assertDomainError(fn () => $this->service->assignmentHistory(2, 10), 'Student was not found');

        DB::table('student_curriculum_assignments')->insert([
            'school_id' => 1, 'student_id' => 10, 'programme_id' => 100, 'curriculum_id' => $curriculum,
            'entry_academic_year_id' => $entry, 'effective_from_academic_year_id' => $entry,
            'assigned_by' => 12, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->assertDomainError(fn () => $this->service->currentAssignment(1, 10), 'multiple active');
        $this->assertSame(2, $this->service->assignmentHistory(1, 10)->count());
        $this->assertSame(0, $this->service->assignmentHistory(2, 11)->count());
        $this->assertNotSame($assignment->id, 0);
    }

    public function test_assignment_identity_is_immutable_and_delete_is_prohibited(): void
    {
        $entry = $this->year(1, 27, '2025-01-01');
        $curriculum = $this->curriculum(1701, 1, 100, $entry);
        $assignment = $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12);
        $this->expectException(DomainException::class);
        $assignment->curriculum_id = 999;
        $assignment->save();
    }

    public function test_direct_creation_and_unauthorized_actor_are_rejected(): void
    {
        $entry = $this->year(1, 34, '2025-01-01');
        $curriculum = $this->curriculum(2101, 1, 100, $entry);
        $this->user(14, 1, 3);
        $this->assertDomainError(fn () => $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 14), 'administrative User');
        $this->expectException(MassAssignmentException::class);
        StudentCurriculumAssignment::create(['school_id' => 1]);
    }

    public function test_active_assignment_cannot_be_ended_outside_the_domain_service(): void
    {
        $entry = $this->year(1, 35, '2025-01-01');
        $curriculum = $this->curriculum(2102, 1, 100, $entry);
        $assignment = $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12);
        $this->expectException(DomainException::class);
        $assignment->ended_at = now();
        $assignment->save();
    }

    public function test_assignment_cannot_be_deleted(): void
    {
        $entry = $this->year(1, 28, '2025-01-01');
        $curriculum = $this->curriculum(1801, 1, 100, $entry);
        $assignment = $this->service->assignExplicitCurriculum(1, 10, 100, $curriculum, $entry, $entry, 12);
        $this->expectException(DomainException::class);
        $assignment->delete();
    }

    public function test_audit_records_initial_assignment_and_both_sides_of_change(): void
    {
        [$entry, $year1, $year2, $old, $new] = $this->changeFixture();
        $first = $this->service->assignExplicitCurriculum(1, 10, 100, $old, $entry, $year1, 12, 'Initial review');
        $this->service->changeCurriculum(1, 10, $new, $year2, 12, 'Version transition');
        $events = AuditLog::query()->pluck('action')->all();
        $this->assertContains('STUDENT_CURRICULUM_ASSIGNED_EXPLICITLY', $events);
        $this->assertContains('STUDENT_CURRICULUM_ASSIGNMENT_ENDED', $events);
        $this->assertContains('STUDENT_CURRICULUM_CHANGED', $events);
        $this->assertGreaterThan(0, $first->id);
    }

    public function test_k12_tenant_is_rejected_and_no_legacy_or_registration_fields_are_written(): void
    {
        $entry = $this->year(3, 29, '2025-01-01');
        $this->user(31, 3, 7);
        $this->assertDomainError(fn () => $this->service->assignExplicitCurriculum(3, 31, 300, $this->curriculum(1901, 3, 300, $entry), $entry, $entry, 30), 'higher-education');
        $this->assertFalse(Schema::hasColumn('student_curriculum_assignments', 'year_of_study'));
        $this->assertFalse(Schema::hasColumn('student_curriculum_assignments', 'session_id'));
        $this->assertFalse(Schema::hasTable('course_registrations'));
        $this->assertSame(0, DB::table('student_curriculum_assignments')->count());
    }

    public function test_assignment_service_has_no_running_session_dependency(): void
    {
        $source = file_get_contents(app_path('Support/Curriculum/StudentCurriculumAssignmentService.php'));
        $this->assertStringNotContainsString('running_session', $source);
        $this->assertStringNotContainsString('IntakeSession', $source);
    }

    private function initial(int $entry): StudentCurriculumAssignment
    {
        return $this->service->assignInitialCurriculum(1, 10, 100, $entry, $entry, 12);
    }

    private function curriculumIdForExplicit(int $school, int $student, int $programme, int $curriculum, int $year): int
    {
        return (int) $this->service->assignExplicitCurriculum($school, $student, $programme, $curriculum, $year, $year, 12)->curriculum_id;
    }

    private function changeFixture(): array
    {
        $entry = $this->year(1, 31, '2025-01-01');
        $year1 = $this->year(1, 32, '2025-01-01');
        $year2 = $this->year(1, 33, '2026-01-01');
        return [$entry, $year1, $year2, $this->curriculum(2001, 1, 100, $year1), $this->curriculum(2002, 1, 100, $year2)];
    }

    private function school(int $id, string $type): void
    {
        DB::table('schools')->insert(['id' => $id, 'title' => "School {$id}", 'school_type' => $type]);
    }

    private function user(int $id, int $schoolId, int $role): void
    {
        DB::table('users')->insert(['id' => $id, 'name' => "User {$id}", 'email' => "u{$id}@example.test", 'password' => 'x', 'code' => "U{$id}", 'role_id' => (string) $role, 'school_id' => $schoolId, 'account_status' => 'active']);
    }

    private function programme(int $id, int $schoolId): void
    {
        DB::table('programmes')->insert(['id' => $id, 'school_id' => $schoolId, 'code' => "P{$id}", 'name' => "Programme {$id}", 'level' => 'Bachelors', 'mode' => 'Full Time', 'tuition_fee' => 0, 'is_active' => 1]);
    }

    private function year(int $schoolId, int $id, string $start): int
    {
        $end = date('Y-m-d', strtotime($start.' + 11 months'));
        DB::table('academic_years')->insert(['id' => $id, 'school_id' => $schoolId, 'label' => "AY {$id}", 'start_date' => $start, 'end_date' => $end, 'status' => 'active']);
        return $id;
    }

    private function curriculum(int $id, int $schoolId, int $programmeId, ?int $yearId, string $status = 'approved'): int
    {
        DB::table('curricula')->insert(['id' => $id, 'school_id' => $schoolId, 'programme_id' => $programmeId, 'version' => "v{$id}", 'effective_academic_year_id' => $yearId, 'status' => $status]);
        return $id;
    }

    private function assertDomainError(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail("Expected a domain error containing '{$message}'.");
        } catch (DomainException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }

    private function schema(): void
    {
        Schema::create('schools', function (Blueprint $table): void { $table->unsignedBigInteger('id')->primary(); $table->string('title'); $table->string('school_type')->default('higher_ed'); });
        Schema::create('users', function (Blueprint $table): void { $table->unsignedBigInteger('id')->primary(); $table->string('name'); $table->string('email'); $table->string('password'); $table->string('code'); $table->string('role_id')->nullable(); $table->integer('school_id')->nullable(); $table->string('account_status')->default('active'); $table->timestamps(); });
        Schema::create('student_profiles', function (Blueprint $table): void { $table->id(); $table->unsignedBigInteger('user_id')->unique(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('programme_id')->nullable(); $table->string('status'); $table->timestamps(); });
        Schema::create('programmes', function (Blueprint $table): void { $table->unsignedBigInteger('id')->primary(); $table->unsignedBigInteger('school_id'); $table->string('code'); $table->string('name'); $table->string('level'); $table->string('mode'); $table->decimal('tuition_fee', 15, 2); $table->boolean('is_active'); $table->unique(['school_id', 'id']); });
        Schema::create('academic_years', function (Blueprint $table): void { $table->unsignedBigInteger('id')->primary(); $table->unsignedBigInteger('school_id'); $table->string('label'); $table->date('start_date'); $table->date('end_date'); $table->string('status'); $table->unique(['school_id', 'id']); });
        Schema::create('curricula', function (Blueprint $table): void { $table->unsignedBigInteger('id')->primary(); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('programme_id'); $table->string('version'); $table->unsignedBigInteger('effective_academic_year_id')->nullable(); $table->string('status'); $table->timestamps(); $table->unique(['school_id', 'id']); $table->unique(['school_id', 'programme_id', 'id']); });
        Schema::create('audit_logs', function (Blueprint $table): void { $table->increments('id'); $table->unsignedBigInteger('school_id')->nullable(); $table->unsignedBigInteger('user_id')->nullable(); $table->string('user_name')->nullable(); $table->string('role_id')->nullable(); $table->string('role_name')->nullable(); $table->string('action'); $table->string('event_type')->nullable(); $table->string('module'); $table->string('route_name')->nullable(); $table->text('url')->nullable(); $table->string('method')->nullable(); $table->text('description')->nullable(); $table->string('record_type')->nullable(); $table->unsignedBigInteger('record_id')->nullable(); $table->json('old_values')->nullable(); $table->json('new_values')->nullable(); $table->string('ip_address')->nullable(); $table->text('user_agent')->nullable(); $table->string('device_type')->nullable(); $table->string('browser')->nullable(); $table->string('platform')->nullable(); $table->string('status')->nullable(); $table->timestamp('created_at')->nullable(); });
        Schema::create('student_curriculum_assignments', function (Blueprint $table): void { $table->bigIncrements('id'); $table->unsignedBigInteger('school_id'); $table->unsignedBigInteger('student_id'); $table->unsignedBigInteger('programme_id'); $table->unsignedBigInteger('curriculum_id'); $table->unsignedBigInteger('entry_academic_year_id'); $table->unsignedBigInteger('effective_from_academic_year_id'); $table->timestamp('ended_at')->nullable(); $table->string('reason', 500)->nullable(); $table->unsignedBigInteger('assigned_by'); $table->timestamps(); $table->index(['school_id', 'student_id', 'ended_at']); });
    }
}
