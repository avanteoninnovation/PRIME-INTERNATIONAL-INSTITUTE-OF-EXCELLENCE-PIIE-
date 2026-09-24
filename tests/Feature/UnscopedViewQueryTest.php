<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

/**
 * Pre-RBAC cleanup D — the two unscoped queries left from Phase 2G. Both
 * read school-owned data (sections/classes/subjects/sessions all carry a
 * school, directly or through their class), so both are now scoped.
 *
 * 1. marks filter (admin + teacher): the page only renders when a
 *    same-school exam matches class+subject+session, but section_id is not
 *    part of that match, so another school's section name could be echoed.
 * 2. routine edit view: took the first active session platform-wide, so
 *    with several schools it could use another school's session and list the
 *    wrong subjects.
 */
class UnscopedViewQueryTest extends TestCase
{
    use StaffModuleTestHelper;

    private array $A;
    private array $B;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        if (!Schema::hasTable('exams')) {
            Schema::create('exams', function (Blueprint $t) {
                $t->id();
                foreach (['name', 'exam_type', 'class_id', 'section_id', 'subject_id', 'session_id', 'exam_category_id', 'school_id', 'starting_time', 'ending_time', 'total_marks', 'status'] as $c) {
                    $t->text($c)->nullable();
                }
                $t->timestamps();
            });
        }
        if (!Schema::hasTable('class_rooms')) {
            Schema::create('class_rooms', function (Blueprint $t) {
                $t->id();
                $t->string('name')->nullable();
                $t->unsignedBigInteger('school_id')->nullable();
                $t->timestamps();
            });
        }
        $this->B = $this->world('B');   // created first, so School B's rows have the lower ids
        $this->A = $this->world('A');
    }

    private function world(string $tag): array
    {
        $school = $this->makeSchool(['title' => "School {$tag}", 'status' => 1]);
        $class = $this->makeClass($school, ['name' => "Class{$tag}"]);
        $w = [
            'school' => $school,
            'class' => $class,
            'section' => DB::table('sections')->insertGetId(['name' => "Section{$tag} Zq", 'class_id' => $class]),
            'session' => DB::table('sessions')->insertGetId(['session_title' => "Session{$tag}", 'status' => 1, 'school_id' => $school]),
            'category' => DB::table('exam_categories')->insertGetId(['name' => "Cat{$tag}", 'school_id' => $school]),
            'admin' => User::factory()->create(['role_id' => 2, 'school_id' => $school, 'account_status' => 'active']),
            'teacher' => User::factory()->create(['role_id' => 3, 'school_id' => $school, 'account_status' => 'active']),
        ];
        $w['subject'] = DB::table('subjects')->insertGetId(['name' => "Subject{$tag} Zq", 'class_id' => $class, 'school_id' => $school, 'session_id' => $w['session']]);
        DB::table('exams')->insert(['name' => "Exam{$tag}", 'exam_type' => 'offline', 'class_id' => $class, 'subject_id' => $w['subject'],
            'session_id' => $w['session'], 'exam_category_id' => $w['category'], 'school_id' => $school]);
        DB::table('teacher_permissions')->insert(['class_id' => $class, 'section_id' => $w['section'], 'teacher_id' => $w['teacher']->id, 'marks' => 1, 'school_id' => $school]);

        return $w;
    }

    private function marksQuery(array $w, int $sectionId): array
    {
        return ['exam_category_id' => $w['category'], 'class_id' => $w['class'], 'section_id' => $sectionId, 'subject_id' => $w['subject'], 'session_id' => $w['session']];
    }

    public function test_marks_filter_never_echoes_another_schools_section(): void
    {
        foreach (['admin' => 'admin.marks.list', 'teacher' => 'teacher.marks.list'] as $who => $route) {
            // Own exam, but School B's section id.
            $response = $this->actingAs($this->A[$who])->get(route($route, $this->marksQuery($this->A, $this->B['section'])));
            $this->assertStringNotContainsString('SectionB Zq', $response->getContent(), "{$who}: School B section name returned");
            $response->assertNotFound();

            // Same school still works.
            $own = $this->actingAs($this->A[$who])->get(route($route, $this->marksQuery($this->A, $this->A['section'])));
            $own->assertOk();
            $this->assertSame('success', $own->json('status'), "{$who}: same-school marks filter");
            $this->assertStringContainsString('SectionA Zq', $own->json('html'));
        }
    }

    public function test_routine_edit_uses_the_schools_own_active_session(): void
    {
        $routine = DB::table('routines')->insertGetId(['class_id' => $this->A['class'], 'section_id' => $this->A['section'], 'subject_id' => $this->A['subject'],
            'teacher_id' => $this->A['teacher']->id, 'day' => 'monday', 'starting_hour' => 9, 'ending_hour' => 10, 'starting_minute' => 0, 'ending_minute' => 0, 'room_id' => 0, 'school_id' => $this->A['school'], 'session_id' => $this->A['session']]);

        $response = $this->actingAs($this->A['admin'])->get(route('admin.routine_edit_modal', $routine));

        $response->assertOk();
        $response->assertSee('SubjectA Zq');   // needs School A's active session, not the first one on the platform (School B's)
        $response->assertDontSee('SubjectB Zq');
    }
}
