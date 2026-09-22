<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

/**
 * Covers the student's own "Personal Information" self-edit form
 * (StudentController::profile()/profile_update()) — the form itself
 * already existed (name/email/birthday/gender/phone/address/photo), but
 * Blood Group had no field at all despite the ID card prominently
 * displaying it, and — since profile_update() rebuilds the whole
 * user_information JSON from just the fields the form posts — every
 * profile edit was silently wiping out a blood_group an admin had set at
 * admission time. Both fixed together: the field is now read into the
 * form and written back on save.
 */
class StudentProfileUpdateTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    private function makeStudent(int $schoolId, string $email, array $userInfo = []): User
    {
        return User::create([
            'name' => 'Profile Student', 'email' => $email,
            'password' => bcrypt('secret'), 'code' => student_code(), 'role_id' => 7, 'school_id' => $schoolId,
            'user_information' => json_encode($userInfo),
        ]);
    }

    public function test_a_student_can_set_their_blood_group(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'bloodgroup@example.com');

        $response = $this->actingAs($student)->post(route('student.profile.update'), [
            'name' => $student->name,
            'email' => $student->email,
            'eDefaultDateRange' => now()->format('m/d/Y'),
            'gender' => 'Male',
            'phone' => '0700111222',
            'address' => 'Kampala',
            'blood_group' => 'O+',
            'old_photo' => '',
        ]);

        $response->assertRedirect(route('student.profile'));
        $info = json_decode($student->fresh()->user_information, true);
        $this->assertSame('O+', $info['blood_group']);
    }

    public function test_editing_the_profile_does_not_wipe_a_blood_group_set_at_admission(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'preexisting@example.com', ['blood_group' => 'AB-', 'phone' => '0700000000']);

        $this->actingAs($student)->post(route('student.profile.update'), [
            'name' => $student->name,
            'email' => $student->email,
            'eDefaultDateRange' => now()->format('m/d/Y'),
            'gender' => 'Male',
            'phone' => '0700999888',
            'address' => 'Entebbe',
            'blood_group' => 'AB-',
            'old_photo' => '',
        ]);

        $info = json_decode($student->fresh()->user_information, true);
        $this->assertSame('AB-', $info['blood_group']);
        $this->assertSame('0700999888', $info['phone']);
    }

    public function test_the_profile_page_shows_the_current_blood_group(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'showbloodgroup@example.com', ['blood_group' => 'B+']);

        $response = $this->actingAs($student)->get(route('student.profile'));

        $response->assertOk();
        $response->assertSee('B+');
    }

    public function test_a_student_can_upload_a_new_profile_photo(): void
    {
        $schoolId = $this->makeSchool();
        $student = $this->makeStudent($schoolId, 'photoupload@example.com');
        \Illuminate\Support\Facades\Storage::fake('public');

        $response = $this->actingAs($student)->post(route('student.profile.update'), [
            'name' => $student->name,
            'email' => $student->email,
            'eDefaultDateRange' => now()->format('m/d/Y'),
            'gender' => 'Male',
            'phone' => '0700111222',
            'address' => 'Kampala',
            'blood_group' => 'O+',
            'old_photo' => '',
            'photo' => UploadedFile::fake()->image('avatar.png'),
        ]);

        $response->assertRedirect(route('student.profile'));
        $info = json_decode($student->fresh()->user_information, true);
        $this->assertNotEmpty($info['photo']);
        $this->assertFileExists(public_path('assets/uploads/user-images/' . $info['photo']));

        @unlink(public_path('assets/uploads/user-images/' . $info['photo']));
    }
}
