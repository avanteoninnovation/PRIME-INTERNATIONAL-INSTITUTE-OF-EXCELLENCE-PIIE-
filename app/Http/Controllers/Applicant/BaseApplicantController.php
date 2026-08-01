<?php

namespace App\Http\Controllers\Applicant;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\AdmissionStatusEvent;
use App\Models\Applicant;
use App\Support\Admissions\ApplicationReference;
use Illuminate\Support\Facades\Auth;

/**
 * Shared plumbing for the applicant portal: who is signed in, and which
 * application they are working on.
 *
 * Every portal controller resolves the application through
 * currentApplication(), which scopes by applicant_id — that is the tenancy
 * boundary here. No portal route accepts an application id from the request,
 * so there is no id to tamper with.
 */
abstract class BaseApplicantController extends Controller
{
    protected function applicant(): Applicant
    {
        /** @var Applicant $applicant */
        $applicant = Auth::guard('applicant')->user();

        return $applicant;
    }

    /**
     * The application in progress, created on first access.
     *
     * A draft is materialised as soon as an applicant opens the portal rather
     * than when they first save a step, so the reference number exists from
     * the outset — applicants quote it when they contact the admissions
     * office, including before they have filled anything in.
     */
    protected function currentApplication(): Admission
    {
        $applicant = $this->applicant();
        $existing  = $applicant->currentAdmission();

        if ($existing) {
            return $existing;
        }

        $admission = Admission::create([
            'school_id'    => $applicant->school_id,
            'applicant_id' => $applicant->id,
            'app_number'   => ApplicationReference::generate($applicant->school_id, 'public'),
            'first_name'   => $applicant->first_name,
            'last_name'    => $applicant->last_name,
            'email'        => $applicant->email,
            'phone'        => $applicant->phone,
            'status'       => Admission::STATUS_DRAFT,
            'source'       => 'public',
            'current_step' => 'personal',
            'fee_status'   => Admission::FEE_UNPAID,
        ]);

        AdmissionStatusEvent::create([
            'school_id'    => $admission->school_id,
            'admission_id' => $admission->id,
            'from_status'  => null,
            'to_status'    => Admission::STATUS_DRAFT,
            'title'        => get_phrase('Application started'),
            'note'         => get_phrase('Your application was created. Everything you enter is saved as you go.'),
            'actor_type'   => 'applicant',
            'actor_id'     => $applicant->id,
            'actor_name'   => $applicant->full_name,
        ]);

        return $admission;
    }

    /**
     * Blocks writes once an application has left the applicant's hands.
     * Returns a redirect to bail out with, or null when editing is allowed.
     */
    protected function guardEditable(Admission $admission)
    {
        if ($admission->isEditableByApplicant()) {
            return null;
        }

        return redirect()->route('applicant.dashboard')->with(
            'error',
            get_phrase('Your application has been submitted and can no longer be edited. Contact the admissions office if something needs to change.')
        );
    }
}
