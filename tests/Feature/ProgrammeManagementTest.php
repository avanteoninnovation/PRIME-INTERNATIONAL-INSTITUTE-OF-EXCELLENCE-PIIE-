<?php

namespace Tests\Feature;

use App\Models\Programme;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the Programmes management screen being reorganised to group by
 * faculty (Department), plus two real bugs found while doing that:
 *  - the create/edit modal's level/mode <select>s were hardcoded to the
 *    legacy option lists and never offered the client's current preferred
 *    values (Bachelors, PGD, ODEL, Full Time, Weekend) at all;
 *  - destroy() had no guard against deleting a programme an application or
 *    student already references.
 */
class ProgrammeManagementTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('school_id');
            $table->timestamps();
        });
    }

    private function makeDepartment(int $schoolId, string $name): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('departments')->insertGetId([
            'name' => $name,
            'school_id' => $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_index_groups_programmes_under_their_faculty(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $engineering = $this->makeDepartment($schoolId, 'Faculty of Engineering');
        $business = $this->makeDepartment($schoolId, 'Faculty of Business');

        $this->makeProgramme($schoolId, ['name' => 'BSc Civil Engineering', 'code' => 'ENG1', 'department_id' => $engineering]);
        $this->makeProgramme($schoolId, ['name' => 'BSc Accounting', 'code' => 'BUS1', 'department_id' => $business]);
        $this->makeProgramme($schoolId, ['name' => 'Unassigned Programme', 'code' => 'UNA1', 'department_id' => null]);

        $response = $this->actingAs($admin)->get(route('admin.programmes.index'));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Faculty of Business', 'BSc Accounting']);
        $response->assertSeeInOrder(['Faculty of Engineering', 'BSc Civil Engineering']);
        $response->assertSee('Unassigned (no faculty)');
        $response->assertSee('Unassigned Programme');
    }

    public function test_a_faculty_with_no_programmes_still_gets_its_own_empty_section_when_unfiltered(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $this->makeDepartment($schoolId, 'Faculty of Law');

        $response = $this->actingAs($admin)->get(route('admin.programmes.index'));

        $response->assertStatus(200);
        $response->assertSee('Faculty of Law');
        $response->assertSee('No programmes in this faculty yet.');
    }

    public function test_department_filter_narrows_to_one_faculty(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $engineering = $this->makeDepartment($schoolId, 'Faculty of Engineering');
        $business = $this->makeDepartment($schoolId, 'Faculty of Business');

        $this->makeProgramme($schoolId, ['name' => 'BSc Civil Engineering', 'code' => 'ENG1', 'department_id' => $engineering]);
        $this->makeProgramme($schoolId, ['name' => 'BSc Accounting', 'code' => 'BUS1', 'department_id' => $business]);

        $response = $this->actingAs($admin)->get(route('admin.programmes.index', ['department_id' => $engineering]));

        $response->assertStatus(200);
        $response->assertSee('BSc Civil Engineering');
        // The Business programme itself must be gone; "Faculty of Business"
        // as a filter-dropdown option is expected to remain so the admin can
        // still switch to it.
        $response->assertDontSee('BSc Accounting');
    }

    public function test_unassigned_filter_shows_only_programmes_with_no_faculty(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $engineering = $this->makeDepartment($schoolId, 'Faculty of Engineering');
        $this->makeProgramme($schoolId, ['name' => 'BSc Civil Engineering', 'code' => 'ENG1', 'department_id' => $engineering]);
        $this->makeProgramme($schoolId, ['name' => 'Orphan Programme', 'code' => 'ORP1', 'department_id' => null]);

        $response = $this->actingAs($admin)->get(route('admin.programmes.index', ['department_id' => 'none']));

        $response->assertStatus(200);
        $response->assertSee('Orphan Programme');
        $response->assertDontSee('BSc Civil Engineering');
    }

    public function test_programmes_from_another_school_never_leak_into_the_grouped_view(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminA = $this->makeAdminUser($schoolA);

        $this->makeProgramme($schoolA, ['name' => 'School A Programme', 'code' => 'A1']);
        $this->makeProgramme($schoolB, ['name' => 'School B Programme', 'code' => 'B1']);

        $response = $this->actingAs($adminA)->get(route('admin.programmes.index'));

        $response->assertStatus(200);
        $response->assertSee('School A Programme');
        $response->assertDontSee('School B Programme');
    }

    // ── Level/Mode dropdown fix ──────────────────────────────────────────

    public function test_a_programme_can_be_created_with_a_current_preferred_level_and_mode(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.programmes.store'), [
            'code' => 'NEW1',
            'name' => 'New Style Programme',
            'level' => 'Bachelors',
            'mode' => 'ODEL',
            'tuition_fee' => 500,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('programmes', ['code' => 'NEW1', 'level' => 'Bachelors', 'mode' => 'ODEL']);
    }

    public function test_the_create_modal_offers_every_current_and_legacy_level_and_mode(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->get(route('admin.programmes.open_modal'));

        $response->assertStatus(200);
        foreach (array_merge(Programme::LEVELS, Programme::LEVELS_LEGACY) as $level) {
            $response->assertSee('value="' . $level . '"', false);
        }
        foreach (array_merge(Programme::MODES, Programme::MODES_LEGACY) as $mode) {
            $response->assertSee('value="' . $mode . '"', false);
        }
    }

    public function test_an_invalid_level_or_mode_is_rejected(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $response = $this->actingAs($admin)->post(route('admin.programmes.store'), [
            'code' => 'BAD1',
            'name' => 'Bad Programme',
            'level' => 'NotARealLevel',
            'mode' => 'NotARealMode',
        ]);

        $response->assertSessionHasErrors(['level', 'mode']);
        $this->assertDatabaseMissing('programmes', ['code' => 'BAD1']);
    }

    public function test_a_duplicate_code_within_the_same_school_is_rejected(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);

        $this->makeProgramme($schoolId, ['code' => 'DUP1']);

        $response = $this->actingAs($admin)->post(route('admin.programmes.store'), [
            'code' => 'DUP1',
            'name' => 'Second Programme With Same Code',
            'level' => 'Diploma',
            'mode' => 'ODEL',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_the_same_code_is_allowed_across_different_schools(): void
    {
        $schoolA = $this->makeSchool();
        $schoolB = $this->makeSchool();
        $adminB = $this->makeAdminUser($schoolB);

        $this->makeProgramme($schoolA, ['code' => 'SHARED']);

        $response = $this->actingAs($adminB)->post(route('admin.programmes.store'), [
            'code' => 'SHARED',
            'name' => 'Programme In School B',
            'level' => 'Diploma',
            'mode' => 'ODEL',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('programmes', ['school_id' => $schoolB, 'code' => 'SHARED']);
    }

    // ── Delete guard ─────────────────────────────────────────────────────

    public function test_a_programme_with_an_admission_cannot_be_deleted(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $this->makeAdmission($schoolId, ['programme_id' => $programmeId]);

        $response = $this->actingAs($admin)->get(route('admin.programmes.destroy', $programmeId));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('programmes', ['id' => $programmeId]);
    }

    public function test_a_programme_with_a_student_profile_cannot_be_deleted(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);
        $student = $this->makeAdminUser($schoolId); // any user row works as the FK target here

        \Illuminate\Support\Facades\DB::table('student_profiles')->insert([
            'user_id' => $student->id,
            'school_id' => $schoolId,
            'programme_id' => $programmeId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.programmes.destroy', $programmeId));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('programmes', ['id' => $programmeId]);
    }

    public function test_an_untouched_programme_can_still_be_deleted(): void
    {
        $schoolId = $this->makeSchool();
        $admin = $this->makeAdminUser($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $response = $this->actingAs($admin)->get(route('admin.programmes.destroy', $programmeId));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('programmes', ['id' => $programmeId]);
    }
}
