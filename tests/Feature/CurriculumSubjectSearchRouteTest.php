<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

class CurriculumSubjectSearchRouteTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $schoolA;
    private int $schoolB;
    private int $curriculumA;
    private int $curriculumB;
    private int $memberSubjectId;
    private int $availableSubjectId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        (require base_path('database/migrations/2026_09_23_000003_create_rbac_tables.php'))->up();
        $this->schoolA = $this->makeSchool(['title' => 'Curriculum Search A', 'status' => 1]);
        $this->schoolB = $this->makeSchool(['title' => 'Curriculum Search B', 'status' => 1]);
        $this->createSearchSchema();

        $this->curriculumA = $this->curriculum($this->schoolA, 'A-v1');
        $this->curriculumB = $this->curriculum($this->schoolB, 'B-v1');
        $this->memberSubjectId = $this->subject($this->schoolA, 'Already included');
        $this->availableSubjectId = $this->subject($this->schoolA, 'Available Course Unit');
        $this->subject($this->schoolB, 'Other tenant Course Unit');

        DB::table('curriculum_memberships')->insert([
            'school_id' => $this->schoolA,
            'curriculum_id' => $this->curriculumA,
            'subject_id' => $this->memberSubjectId,
        ]);
    }

    public function test_curriculum_subject_search_route_contract_tenant_scope_and_view_authorization(): void
    {
        $viewer = $this->staff(3, $this->schoolA);
        $ungranted = $this->staff(3, $this->schoolA);
        DB::table('user_permissions')->insert([
            'school_id' => $this->schoolA,
            'user_id' => $viewer->id,
            'permission' => 'academic.curriculum.view',
        ]);

        $url = route('admin.curricula.subjects.search', ['id' => $this->curriculumA, 'search' => 'Course']);
        $this->assertStringContainsString('/admin/curricula/' . $this->curriculumA . '/subjects/search', $url);
        $this->assertSame(
            'academic.curriculum.view',
            app(\App\Support\Permissions\PermissionService::class)->routePermission('admin.curricula.subjects.search')
        );

        $response = $this->actingAs($viewer)->get($url);
        $response->assertOk()->assertJsonPath('data.0.id', $this->availableSubjectId);
        $response->assertJsonMissing(['id' => $this->memberSubjectId]);

        $this->actingAs($viewer)
            ->get(route('admin.curricula.subjects.search', ['id' => $this->curriculumB, 'search' => 'Course']))
            ->assertNotFound();

        $this->actingAs($ungranted)
            ->get(route('admin.curricula.subjects.search', ['id' => $this->curriculumA, 'search' => 'Course']))
            ->assertForbidden();
    }

    private function createSearchSchema(): void
    {
        Schema::create('curricula', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('programme_id');
            $table->string('version');
            $table->unsignedBigInteger('effective_academic_year_id')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
        Schema::create('curriculum_memberships', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedBigInteger('curriculum_id');
            $table->unsignedBigInteger('subject_id');
        });
    }

    private function curriculum(int $schoolId, string $version): int
    {
        return (int) DB::table('curricula')->insertGetId([
            'school_id' => $schoolId,
            'programme_id' => 1,
            'version' => $version,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function subject(int $schoolId, string $name): int
    {
        return (int) DB::table('subjects')->insertGetId([
            'school_id' => $schoolId,
            'name' => $name,
            'code' => strtoupper(str_replace(' ', '-', $name)),
            'programme_id' => null,
        ]);
    }

    private function staff(int $role, int $schoolId): User
    {
        return User::factory()->create([
            'role_id' => $role,
            'school_id' => $schoolId,
            'account_status' => 'active',
            'menu_permission' => null,
        ]);
    }
}
