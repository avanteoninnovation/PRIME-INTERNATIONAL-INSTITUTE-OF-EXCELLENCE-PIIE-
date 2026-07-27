<?php

namespace Tests\Feature;

use App\Models\Admission;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class PublicApplicationTest extends TestCase
{
    use AdmissionsTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();
    }

    private function configurePrimarySchool(int $schoolId): void
    {
        DB::table('global_settings')->insert([
            'key' => 'primary_school_id',
            'value' => (string) $schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_apply_form_shows_no_school_selector_and_uses_configured_institution(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);
        $this->makeProgramme($schoolId, ['name' => 'BSc Computer Science']);

        $response = $this->get(route('apply.form'));

        $response->assertStatus(200);
        $response->assertSee('BSc Computer Science');
        $response->assertDontSee('name="school_id"', false);
        $response->assertDontSee('Select a school', false);
    }

    public function test_submitting_application_creates_admission_under_configured_school_without_any_school_input(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $response = $this->post(route('apply.submit'), [
            'first_name' => 'Alice',
            'last_name' => 'Applicant',
            'email' => 'alice@example.com',
            'phone' => '0700111222',
            'programme_id' => $programmeId,
        ]);

        $response->assertRedirect(route('apply.form'));

        $admission = Admission::first();
        $this->assertNotNull($admission);
        $this->assertSame($schoolId, $admission->school_id);
        $this->assertSame('submitted', $admission->status);
        $this->assertSame('Alice', $admission->first_name);
    }

    public function test_submission_is_rejected_without_required_fields(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);

        $response = $this->post(route('apply.submit'), []);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'phone', 'programme_id']);
        $this->assertSame(0, Admission::count());
    }

    public function test_honeypot_field_silently_blocks_bot_submissions(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $response = $this->post(route('apply.submit'), [
            'first_name' => 'Bot',
            'last_name' => 'Submission',
            'email' => 'bot@example.com',
            'phone' => '000',
            'programme_id' => $programmeId,
            'website' => 'http://spam.example.com',
        ]);

        $response->assertRedirect(route('apply.form'));
        $this->assertSame(0, Admission::count());
    }

    public function test_public_post_endpoint_is_rate_limited(): void
    {
        $schoolId = $this->makeSchool();
        $this->configurePrimarySchool($schoolId);
        $programmeId = $this->makeProgramme($schoolId);

        $payload = [
            'first_name' => 'Rate',
            'last_name' => 'Limited',
            'email' => 'rate@example.com',
            'phone' => '0700000000',
            'programme_id' => $programmeId,
        ];

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('apply.submit'), $payload)->assertStatus(302);
        }

        $response = $this->post(route('apply.submit'), $payload);
        $response->assertStatus(429);
    }
}
