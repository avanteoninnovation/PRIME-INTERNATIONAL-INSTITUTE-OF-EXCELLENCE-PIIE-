<?php

namespace Tests\Feature;

use App\Models\StaffProfile;
use App\Models\User;
use App\Support\Permissions\PermissionService;
use App\Support\Staff\StaffNin;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Support\StaffModuleTestHelper;
use Tests\TestCase;

class GenericStaffProfileEditTest extends TestCase
{
    use StaffModuleTestHelper;

    private int $school;
    private int $otherSchool;
    private int $department;
    private int $designation;
    private int $foreignDepartment;
    private int $foreignDesignation;
    private User $admin;
    private User $otherAdmin;
    private User $staff;
    private string $passwordHash = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootStaffModuleTestSchema();
        Schema::table('schools', function (Blueprint $table): void {
            $table->string('school_type')->default('k12');
        });
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        (require base_path('database/migrations/2026_09_23_000003_create_rbac_tables.php'))->up();
        (require base_path('database/migrations/2026_09_23_000004_add_is_active_to_staff_roles.php'))->up();
        (require base_path('database/migrations/2026_09_24_000001_create_staff_professional_records_tables.php'))->up();
        (require base_path('database/migrations/2014_10_12_100000_create_password_resets_table.php'))->up();

        $this->school = $this->makeSchool(['title' => 'School A', 'status' => 1]);
        $this->otherSchool = $this->makeSchool(['title' => 'School B', 'status' => 1]);
        $this->department = $this->makeDepartment($this->school, 'Administration');
        $this->designation = $this->makeDesignation($this->school, 'Registrar');
        $this->foreignDepartment = $this->makeDepartment($this->otherSchool, 'Foreign Department');
        $this->foreignDesignation = $this->makeDesignation($this->otherSchool, 'Foreign Designation');
        $this->admin = $this->makeAdminUser($this->school);
        $this->otherAdmin = $this->makeAdminUser($this->otherSchool);
        $this->passwordHash = Hash::make('Existing-Staff-Password-987!');
        $this->staff = User::factory()->create([
            'name' => 'Test Registrar',
            'first_name' => 'Test',
            'last_name' => 'Registrar',
            'email' => 'registrar.test@school.test',
            'role_id' => 20,
            'school_id' => $this->school,
            'code' => 'PIIE-STAFF-0007',
            'department_id' => $this->department,
            'designation_id' => $this->designation,
            'employment_type' => 'Full Time',
            'staff_status' => 'active',
            'account_status' => 'active',
            'force_password_change' => true,
            'password' => $this->passwordHash,
            'user_information' => json_encode([
                'gender' => 'Female',
                'birthday' => strtotime('1992-04-05'),
                'phone' => '+256700000007',
                'address' => 'Old address',
                'photo' => 'preserve-this-photo.jpg',
                'blood_group' => 'O+',
            ]),
        ]);

        $profile = new StaffProfile(['user_id' => $this->staff->id, 'school_id' => $this->school]);
        StaffNin::assign($profile, 'REG12345678');
        $profile->save();

        DB::table('staff_qualifications')->insert([
            'user_id' => $this->staff->id,
            'school_id' => $this->school,
            'qualification_level' => 'Degree',
            'qualification_name' => 'Bachelor of Records Management',
            'institution' => 'PIIE Test University',
            'verification_status' => 'verified',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('staff_professional_registrations')->insert([
            'user_id' => $this->staff->id,
            'school_id' => $this->school,
            'professional_body' => 'Records Association',
            'registration_number' => 'RA-1234',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('staff_experiences')->insert([
            'user_id' => $this->staff->id,
            'school_id' => $this->school,
            'employer' => 'Previous Institution',
            'position' => 'Records Officer',
            'start_date' => '2018-01-01',
            'currently_working' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleId = DB::table('staff_roles')->insertGetId([
            'school_id' => $this->school,
            'name' => 'Registrar Custom Role',
            'description' => 'Test custom role',
            'is_active' => true,
            'created_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('staff_role_permissions')->insert([
            'staff_role_id' => $roleId,
            'permission' => 'admissions.view',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_staff_roles')->insert([
            'school_id' => $this->school,
            'user_id' => $this->staff->id,
            'staff_role_id' => $roleId,
            'assigned_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('user_permissions')->insert([
            'school_id' => $this->school,
            'user_id' => $this->staff->id,
            'permission' => 'admissions.review',
            'granted_by' => $this->admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::fake();
    }

    private function validProfile(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Test',
            'last_name' => 'Registrar Updated',
            'email' => 'registrar.real@example.org',
            'phone' => '+256700000099',
            'gender' => 'Female',
            'birthday' => '1992-04-05',
            'nin' => 'REG12345678',
            'address' => 'Updated office address',
            'department_id' => $this->department,
            'designation_id' => $this->designation,
            'employment_type' => 'Full Time',
            'staff_status' => 'active',
        ], $overrides);
    }

    public function test_directory_offers_separate_edit_profile_action_and_form_uses_existing_profile_records(): void
    {
        $directory = $this->actingAs($this->admin)->get(route('admin.rbac.staff.index'))->assertOk()
            ->assertSee('Edit profile')
            ->assertSee(route('admin.staff.other.edit', $this->staff->id), false);

        $page = $this->get(route('admin.staff.other.edit', $this->staff->id))->assertOk()
            ->assertSee('Edit Other Staff Profile')
            ->assertSee('First Name')
            ->assertSee('NIN / Identity Number')
            ->assertSee('Department')
            ->assertSee('Account Access')
            ->assertSee('Bachelor of Records Management')
            ->assertSee('Records Association')
            ->assertDontSee($this->passwordHash);
        $this->assertNotEmpty($directory->getContent());
        $this->assertStringContainsString('qualifications', $page->getContent());
    }

    public function test_profile_edit_updates_existing_user_and_preserves_identity_credentials_rbac_and_professional_records(): void
    {
        DB::table('password_resets')->insert([
            'email' => $this->staff->email,
            'token' => Hash::make('an-old-reset-token'),
            'created_at' => now(),
        ]);
        $beforeUserCount = User::count();
        $beforeRoleAssignments = DB::table('user_staff_roles')->where('user_id', $this->staff->id)->pluck('staff_role_id')->all();
        $beforeDirectPermissions = DB::table('user_permissions')->where('user_id', $this->staff->id)->pluck('permission')->all();
        $beforeEffective = app(PermissionService::class)->effectivePermissions($this->staff);
        $profileId = DB::table('staff_profiles')->where('user_id', $this->staff->id)->value('id');
        $qualificationId = DB::table('staff_qualifications')->where('user_id', $this->staff->id)->value('id');
        $registrationId = DB::table('staff_professional_registrations')->where('user_id', $this->staff->id)->value('id');
        $experienceId = DB::table('staff_experiences')->where('user_id', $this->staff->id)->value('id');

        $this->actingAs($this->admin)->put(route('admin.staff.other.update', $this->staff->id), $this->validProfile())
            ->assertRedirect(route('admin.rbac.staff.index'))
            ->assertSessionHas('message');

        $staff = User::findOrFail($this->staff->id);
        $this->assertSame($this->staff->id, $staff->id);
        $this->assertSame($beforeUserCount, User::count());
        $this->assertSame('PIIE-STAFF-0007', $staff->code);
        $this->assertSame(20, (int) $staff->role_id);
        $this->assertSame('registrar.real@example.org', $staff->email);
        $this->assertSame('Test Registrar Updated', $staff->name);
        $this->assertSame($this->passwordHash, $staff->password);
        $this->assertTrue((bool) $staff->force_password_change);
        $this->assertSame('active', $staff->account_status);
        $this->assertSame($this->department, (int) $staff->department_id);
        $this->assertSame($this->designation, (int) $staff->designation_id);
        $this->assertSame($beforeRoleAssignments, DB::table('user_staff_roles')->where('user_id', $staff->id)->pluck('staff_role_id')->all());
        $this->assertSame($beforeDirectPermissions, DB::table('user_permissions')->where('user_id', $staff->id)->pluck('permission')->all());
        $this->assertEqualsCanonicalizing($beforeEffective, app(PermissionService::class)->effectivePermissions($staff));
        $this->assertSame(1, DB::table('staff_profiles')->where('user_id', $staff->id)->count());
        $this->assertSame(1, DB::table('staff_qualifications')->where('user_id', $staff->id)->count());
        $this->assertSame(1, DB::table('staff_professional_registrations')->where('user_id', $staff->id)->count());
        $this->assertSame(1, DB::table('staff_experiences')->where('user_id', $staff->id)->count());
        $this->assertSame($profileId, DB::table('staff_profiles')->where('user_id', $staff->id)->value('id'));
        $this->assertSame($qualificationId, DB::table('staff_qualifications')->where('user_id', $staff->id)->value('id'));
        $this->assertSame($registrationId, DB::table('staff_professional_registrations')->where('user_id', $staff->id)->value('id'));
        $this->assertSame($experienceId, DB::table('staff_experiences')->where('user_id', $staff->id)->value('id'));
        $this->assertSame(0, DB::table('password_resets')->whereRaw('LOWER(email) = ?', ['registrar.test@school.test'])->count());
        $this->assertSame('+256700000099', json_decode($staff->user_information, true)['phone']);
        $this->assertSame('preserve-this-photo.jpg', json_decode($staff->user_information, true)['photo']);
        Mail::assertNothingOutgoing();
    }

    public function test_duplicate_email_is_rejected_globally_without_mutating_the_existing_user(): void
    {
        $initialUserCount = User::count();
        User::factory()->create([
            'email' => 'already.used@example.org',
            'role_id' => 3,
            'school_id' => $this->otherSchool,
        ]);
        $oldEmail = $this->staff->email;

        $this->actingAs($this->admin)->put(route('admin.staff.other.update', $this->staff->id), $this->validProfile([
            'email' => 'ALREADY.USED@example.org',
        ]))->assertSessionHasErrors('email')->assertSessionHasInput('email', 'ALREADY.USED@example.org');

        $this->assertSame($oldEmail, $this->staff->fresh()->email);
        $this->assertSame($initialUserCount + 1, User::count());
    }

    public function test_foreign_tenant_admin_and_non_generic_staff_targets_are_rejected(): void
    {
        $this->actingAs($this->otherAdmin)->get(route('admin.staff.other.edit', $this->staff->id))->assertNotFound();
        $this->put(route('admin.staff.other.update', $this->staff->id), $this->validProfile())->assertNotFound();

        $teacher = User::factory()->create([
            'email' => 'lecturer@school.test',
            'role_id' => 3,
            'school_id' => $this->school,
        ]);
        $this->actingAs($this->admin)->get(route('admin.staff.other.edit', $teacher->id))->assertNotFound();
        $this->put(route('admin.staff.other.update', $teacher->id), $this->validProfile())->assertNotFound();
        $this->assertSame('registrar.test@school.test', $this->staff->fresh()->email);
    }

    public function test_foreign_department_and_designation_ids_are_rejected_with_human_errors(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.staff.other.edit', $this->staff->id))
            ->put(route('admin.staff.other.update', $this->staff->id), $this->validProfile([
                'department_id' => $this->foreignDepartment,
                'designation_id' => $this->foreignDesignation,
            ]))->assertRedirect(route('admin.staff.other.edit', $this->staff->id))
            ->assertSessionHasErrors(['department_id', 'designation_id'])
            ->assertSessionHasInput('email', 'registrar.real@example.org');

        $messages = implode(' ', session('errors')->all());
        $this->assertStringContainsString('from your school', $messages);
        $this->assertStringNotContainsString('department_id', $messages);
        $this->assertStringNotContainsString('designation_id', $messages);
        $this->assertSame('registrar.test@school.test', $this->staff->fresh()->email);
        $this->assertNotEmpty($response->getSession()->get('_old_input'));
    }

    public function test_profile_validation_uses_human_qualification_messages_and_preserves_submitted_values(): void
    {
        $this->actingAs($this->admin)->from(route('admin.staff.other.edit', $this->staff->id))
            ->put(route('admin.staff.other.update', $this->staff->id), $this->validProfile([
                'email' => 'not-an-email',
                'first_name' => '',
                'designation_id' => '',
            ]))->assertSessionHasErrors(['email', 'first_name', 'designation_id'])
            ->assertSessionHasInput('last_name', 'Registrar Updated');

        $messages = implode(' ', session('errors')->all());
        $this->assertStringContainsString('Enter a valid email address.', $messages);
        $this->assertStringContainsString('Enter the first name.', $messages);
        $this->assertStringNotContainsString('designation_id', $messages);
        $this->assertStringNotContainsString('qualifications.0.', $messages);
    }
}
