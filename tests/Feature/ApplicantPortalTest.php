<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionDocument;
use App\Models\AdmissionDocumentRequirement;
use App\Models\Applicant;
use App\Models\ApplicationPayment;
use App\Support\Admissions\ApplicationProgress;
use App\Support\Admissions\ApplicationReference;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Support\AdmissionsTestHelper;
use Tests\TestCase;

class ApplicantPortalTest extends TestCase
{
    use AdmissionsTestHelper;

    private int $schoolId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootAdmissionsTestSchema();

        $this->schoolId = $this->makeSchool(['title' => 'Prime International Institute of Excellence']);

        DB::table('global_settings')->insert([
            'key'        => 'primary_school_id',
            'value'      => (string) $this->schoolId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function signIn(?Applicant $applicant = null): Applicant
    {
        $applicant = $applicant ?: $this->makeApplicant($this->schoolId);
        $this->be($applicant, 'applicant');

        return $applicant;
    }

    // ── Public entry point ───────────────────────────────────────────────

    public function test_apply_page_no_longer_accepts_an_anonymous_submission_and_points_at_the_portal(): void
    {
        $this->makeProgramme($this->schoolId, ['name' => 'BSc Computer Science']);

        $response = $this->get(route('apply.form'));

        $response->assertStatus(200);
        $response->assertSee('BSc Computer Science');
        $response->assertSee(route('applicant.register'), false);
        // The old one-shot form is gone: there is no anonymous POST target.
        $this->assertFalse(app('router')->getRoutes()->hasNamedRoute('apply.submit'));
    }

    public function test_registration_creates_an_applicant_scoped_to_the_configured_school(): void
    {
        $response = $this->post(route('applicant.register.submit'), [
            'first_name'            => 'Alice',
            'last_name'             => 'Applicant',
            'email'                 => 'alice@example.com',
            'phone'                 => '0700111222',
            'password'              => 'secret-password',
            'password_confirmation' => 'secret-password',
            'terms'                 => '1',
        ]);

        $response->assertRedirect(route('applicant.dashboard'));

        $applicant = Applicant::first();
        $this->assertNotNull($applicant);
        $this->assertSame($this->schoolId, (int) $applicant->school_id);
        $this->assertTrue(Hash::check('secret-password', $applicant->password));

        // An applicant is never a `users` row — that is the whole point of
        // the separate guard.
        $this->assertSame(0, DB::table('users')->where('email', 'alice@example.com')->count());
    }

    public function test_portal_routes_reject_an_unauthenticated_visitor(): void
    {
        $this->get(route('applicant.dashboard'))->assertRedirect(route('applicant.login'));
        $this->get(route('applicant.documents'))->assertRedirect(route('applicant.login'));
        $this->get(route('applicant.track'))->assertRedirect(route('applicant.login'));
    }

    public function test_a_signed_in_staff_user_is_not_treated_as_an_applicant(): void
    {
        $admin = $this->makeAdminUser($this->schoolId);

        // Authenticated on the default web guard, not the applicant guard.
        $this->actingAs($admin)
            ->get(route('applicant.dashboard'))
            ->assertRedirect(route('applicant.login'));
    }

    // ── Draft lifecycle ──────────────────────────────────────────────────

    public function test_opening_the_dashboard_creates_a_draft_with_a_structured_reference(): void
    {
        $applicant = $this->signIn();

        $this->get(route('applicant.dashboard'))->assertStatus(200);

        $admission = Admission::first();

        $this->assertNotNull($admission);
        $this->assertSame(Admission::STATUS_DRAFT, $admission->status);
        $this->assertSame($applicant->id, (int) $admission->applicant_id);
        $this->assertMatchesRegularExpression('/^PIIE-\d{4}-S-P\d{4}$/', $admission->app_number);
    }

    public function test_a_second_dashboard_visit_reuses_the_same_draft(): void
    {
        $this->signIn();

        $this->get(route('applicant.dashboard'));
        $this->get(route('applicant.dashboard'));

        $this->assertSame(1, Admission::count());
    }

    public function test_drafts_are_hidden_from_the_staff_admissions_queue(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        $admin = $this->makeAdminUser($this->schoolId);

        $response = $this->actingAs($admin)->get(route('admin.hei_admissions.index'));

        $response->assertStatus(200);
        $response->assertDontSee(Admission::first()->app_number);
    }

    public function test_saving_the_personal_step_persists_the_answers(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        $response = $this->post(route('applicant.application.personal'), [
            'first_name'       => 'Alice',
            'last_name'        => 'Applicant',
            'email'            => 'alice@example.com',
            'phone'            => '0700111222',
            'dob'              => '2000-01-15',
            'gender'           => 'Female',
            'nationality'      => 'Ugandan',
            'physical_address' => 'Plot 1, Kampala',
            'nok_name'         => 'Mary Doe',
            'nok_relationship' => 'Mother',
            'nok_phone'        => '0700333444',
            'action'           => 'save',
        ]);

        $response->assertSessionHasNoErrors();

        $admission = Admission::first();
        $this->assertSame('Ugandan', $admission->nationality);
        $this->assertSame('Mary Doe', $admission->nok_name);
        $this->assertTrue(ApplicationProgress::isComplete($admission, ApplicationProgress::STEP_PERSONAL));
    }

    public function test_programme_step_rejects_a_programme_from_another_school(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        $otherSchoolId = $this->makeSchool(['title' => 'Other School']);
        $foreignProgramme = $this->makeProgramme($otherSchoolId);
        $intakeId = $this->makeIntakeSession($this->schoolId);

        $response = $this->post(route('applicant.application.programme'), [
            'programme_id'      => $foreignProgramme,
            'intake_session_id' => $intakeId,
        ]);

        $response->assertSessionHasErrors('programme_id');
        $this->assertNull(Admission::first()->programme_id);
    }

    // ── Documents ────────────────────────────────────────────────────────

    public function test_uploading_a_document_files_it_against_its_requirement(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        AdmissionDocumentRequirement::create([
            'school_id' => $this->schoolId,
            'key'       => 'national_id',
            'label'     => 'National ID',
            'is_required' => true,
            'allow_multiple' => false,
        ]);

        $response = $this->post(route('applicant.documents.upload'), [
            'requirement_key' => 'national_id',
            'files'           => [UploadedFile::fake()->create('id.pdf', 40, 'application/pdf')],
        ]);

        $response->assertSessionHasNoErrors();

        $document = AdmissionDocument::first();
        $this->assertNotNull($document);
        $this->assertSame('national_id', $document->requirement_key);
        $this->assertSame(AdmissionDocument::STATUS_PENDING, $document->status);
        // The applicant's filename is never what lands on disk.
        $this->assertNotSame('id.pdf', $document->stored_name);
        $this->assertSame('id.pdf', $document->original_name);

        @unlink($document->absolute_path);
    }

    public function test_a_single_file_requirement_replaces_rather_than_accumulates(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        AdmissionDocumentRequirement::create([
            'school_id'      => $this->schoolId,
            'key'            => 'passport_photo',
            'label'          => 'Passport Photo',
            'is_required'    => true,
            'allow_multiple' => false,
        ]);

        foreach (['one.pdf', 'two.pdf'] as $name) {
            $this->post(route('applicant.documents.upload'), [
                'requirement_key' => 'passport_photo',
                'files'           => [UploadedFile::fake()->create($name, 20, 'application/pdf')],
            ]);
        }

        $documents = AdmissionDocument::all();
        $this->assertCount(1, $documents);
        $this->assertSame('two.pdf', $documents->first()->original_name);

        @unlink($documents->first()->absolute_path);
    }

    public function test_an_applicant_cannot_delete_a_verified_document(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));
        $admission = Admission::first();

        $document = AdmissionDocument::create([
            'school_id'     => $this->schoolId,
            'admission_id'  => $admission->id,
            'requirement_key' => 'national_id',
            'original_name' => 'id.pdf',
            'stored_name'   => 'stored.pdf',
            'status'        => AdmissionDocument::STATUS_VERIFIED,
        ]);

        $this->post(route('applicant.documents.delete', $document->id))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('admission_documents', ['id' => $document->id]);
    }

    // ── Submission ───────────────────────────────────────────────────────

    public function test_submission_is_blocked_while_a_required_document_is_missing(): void
    {
        $applicant = $this->signIn();
        $this->get(route('applicant.dashboard'));

        $admission = $this->completeApplicationFields(Admission::first(), [
            'programme_id'      => $this->makeProgramme($this->schoolId),
            'intake_session_id' => $this->makeIntakeSession($this->schoolId, ['application_fee' => 0]),
        ]);

        AdmissionDocumentRequirement::create([
            'school_id'   => $this->schoolId,
            'key'         => 'national_id',
            'label'       => 'National ID',
            'is_required' => true,
        ]);

        $this->assertFalse(ApplicationProgress::canSubmit($admission));

        $this->post(route('applicant.application.submit'), ['declaration' => '1'])
            ->assertSessionHas('error');

        $this->assertNull(Admission::first()->submitted_at);
    }

    public function test_a_complete_application_submits_and_records_a_visible_timeline_entry(): void
    {
        $applicant = $this->signIn();
        $this->get(route('applicant.dashboard'));

        $admission = $this->completeApplicationFields(Admission::first(), [
            'programme_id'      => $this->makeProgramme($this->schoolId),
            'intake_session_id' => $this->makeIntakeSession($this->schoolId, ['application_fee' => 0]),
        ]);

        // No requirements configured for this school and no defaults seeded
        // would still block on the built-in set, so make the checklist empty.
        AdmissionDocumentRequirement::create([
            'school_id'   => $this->schoolId,
            'key'         => 'optional_extra',
            'label'       => 'Optional Extra',
            'is_required' => false,
        ]);

        $this->assertTrue(ApplicationProgress::canSubmit($admission->fresh()));

        $response = $this->post(route('applicant.application.submit'), ['declaration' => '1']);

        $response->assertRedirect(route('applicant.dashboard'));

        $admission = Admission::first();
        $this->assertSame(Admission::STATUS_SUBMITTED, $admission->status);
        $this->assertNotNull($admission->submitted_at);
        $this->assertNotNull($admission->declaration_accepted_at);

        $this->assertDatabaseHas('admission_status_events', [
            'admission_id'            => $admission->id,
            'to_status'               => Admission::STATUS_SUBMITTED,
            'actor_type'              => 'applicant',
            'is_visible_to_applicant' => true,
        ]);
    }

    public function test_a_submitted_application_can_no_longer_be_edited_by_the_applicant(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        Admission::first()->update(['status' => Admission::STATUS_UNDER_REVIEW, 'submitted_at' => now()]);

        $this->post(route('applicant.application.personal'), [
            'first_name'       => 'Changed',
            'last_name'        => 'Name',
            'email'            => 'changed@example.com',
            'phone'            => '0700999888',
            'dob'              => '1999-01-01',
            'gender'           => 'Male',
            'nationality'      => 'Kenyan',
            'physical_address' => 'Nairobi',
            'nok_name'         => 'Someone',
            'nok_relationship' => 'Friend',
            'nok_phone'        => '0700000111',
        ])->assertRedirect(route('applicant.dashboard'));

        $this->assertNotSame('Changed', Admission::first()->first_name);
    }

    // ── Tenancy ──────────────────────────────────────────────────────────

    public function test_an_applicant_only_ever_sees_their_own_application(): void
    {
        $other = $this->makeApplicant($this->schoolId, ['email' => 'other@example.com']);

        $otherAdmissionId = $this->makeAdmission($this->schoolId, [
            'applicant_id' => $other->id,
            'app_number'   => 'PIIE-2526-S-P9999',
            'status'       => Admission::STATUS_SUBMITTED,
        ]);

        $this->signIn();
        $response = $this->get(route('applicant.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('PIIE-2526-S-P9999');

        // A fresh draft was created for the signed-in applicant instead.
        $this->assertSame(2, Admission::count());
        $this->assertNotSame($otherAdmissionId, (int) Admission::where('applicant_id', '!=', $other->id)->first()->id);
    }

    public function test_a_document_belonging_to_another_applicant_is_not_viewable(): void
    {
        $other = $this->makeApplicant($this->schoolId, ['email' => 'other@example.com']);
        $otherAdmissionId = $this->makeAdmission($this->schoolId, ['applicant_id' => $other->id]);

        $foreignDocument = AdmissionDocument::create([
            'school_id'     => $this->schoolId,
            'admission_id'  => $otherAdmissionId,
            'original_name' => 'secret.pdf',
            'stored_name'   => 'secret_stored.pdf',
        ]);

        $this->signIn();
        $this->get(route('applicant.dashboard'));

        $this->get(route('applicant.document.view', $foreignDocument->id))->assertStatus(404);
    }

    // ── Fee ──────────────────────────────────────────────────────────────

    public function test_offline_payment_is_recorded_as_pending_and_never_self_confirmed(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        Admission::first()->update([
            'intake_session_id' => $this->makeIntakeSession($this->schoolId, ['application_fee' => 50000]),
        ]);

        $response = $this->post(route('applicant.payment.offline'), [
            'reference' => 'TXN-12345',
            'proof'     => UploadedFile::fake()->create('slip.pdf', 30, 'application/pdf'),
        ]);

        $response->assertSessionHasNoErrors();

        $payment = ApplicationPayment::first();
        $this->assertSame(ApplicationPayment::STATUS_PENDING, $payment->status);
        $this->assertSame('50000.00', $payment->amount);
        $this->assertSame(Admission::FEE_PENDING, Admission::first()->fee_status);

        @unlink(public_path(ApplicationPayment::PROOF_DIR . '/' . $payment->proof_file));
    }

    public function test_the_payment_step_does_not_apply_to_a_free_intake(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        $admission = Admission::first();
        $admission->update(['intake_session_id' => $this->makeIntakeSession($this->schoolId, ['application_fee' => 0])]);

        $this->assertFalse(ApplicationProgress::isApplicable($admission->fresh(), ApplicationProgress::STEP_PAYMENT));

        $this->get(route('applicant.payment'))->assertRedirect(route('applicant.dashboard'));
    }

    // ── Reference numbers ────────────────────────────────────────────────

    public function test_reference_numbers_are_unique_and_encode_the_channel(): void
    {
        $portalRef = ApplicationReference::generate($this->schoolId, 'public');
        $staffRef  = ApplicationReference::generate($this->schoolId, 'staff_entry');

        $this->assertStringContainsString('-S-P', $portalRef);
        $this->assertStringContainsString('-O-P', $staffRef);
        $this->assertStringStartsWith('PIIE-', $portalRef);
    }

    public function test_a_configured_prefix_overrides_the_derived_initials(): void
    {
        DB::table('global_settings')->insert([
            'key'        => 'application_ref_prefix',
            'value'      => 'IUEA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertStringStartsWith('IUEA-', ApplicationReference::generate($this->schoolId, 'public'));
    }

    // ── Dashboard status banner ──────────────────────────────────────────

    public function test_dashboard_shows_an_acceptance_banner_and_offer_letter_link(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        Admission::first()->update([
            'status' => Admission::STATUS_ACCEPTED,
            'decision_note' => 'Congratulations on your strong application.',
        ]);

        $response = $this->get(route('applicant.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Offer of admission made');
        $response->assertSee('Congratulations on your strong application.');
        $response->assertSee(route('applicant.offer_letter'), false);
    }

    public function test_dashboard_shows_a_rejection_banner_with_the_decision_note(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        Admission::first()->update([
            'status' => Admission::STATUS_REJECTED,
            'decision_note' => 'The intake was highly competitive this round.',
        ]);

        $response = $this->get(route('applicant.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Application unsuccessful');
        $response->assertSee('The intake was highly competitive this round.');
    }

    public function test_dashboard_shows_an_enrolled_banner_pointing_at_email(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        Admission::first()->update(['status' => Admission::STATUS_ENROLLED]);

        $response = $this->get(route('applicant.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Enrolment completed');
        $response->assertSee('Check your email for your student portal login details.');
    }

    public function test_dashboard_shows_an_under_review_banner(): void
    {
        $this->signIn();
        $this->get(route('applicant.dashboard'));

        Admission::first()->update(['status' => Admission::STATUS_UNDER_REVIEW]);

        $response = $this->get(route('applicant.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Under review by the admissions committee');
    }

    public function test_dashboard_shows_no_status_banner_while_still_a_draft(): void
    {
        $this->signIn();

        $response = $this->get(route('applicant.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Offer of admission made');
        $response->assertDontSee('Application unsuccessful');
        $response->assertDontSee('Enrolment completed');
        $response->assertDontSee('Under review by the admissions committee');
    }
}
