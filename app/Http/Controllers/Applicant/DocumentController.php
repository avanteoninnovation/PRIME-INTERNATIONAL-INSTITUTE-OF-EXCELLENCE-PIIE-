<?php

namespace App\Http\Controllers\Applicant;

use App\Models\AdmissionDocument;
use App\Models\AuditLog;
use App\Support\Admissions\ApplicationDocuments;
use App\Support\Admissions\ApplicationProgress;
use Illuminate\Http\Request;

/**
 * Uploading, replacing and removing supporting documents.
 *
 * Reachable both as a wizard step and as a standalone "Documents" page,
 * because documents are the one part of an application people commonly come
 * back to after everything else is done (waiting on a transcript, re-scanning
 * a rejected ID).
 */
class DocumentController extends BaseApplicantController
{
    public function index()
    {
        $admission = $this->currentApplication();
        $admission->load(['programme', 'uploadedDocuments']);

        return view('applicant.documents', [
            'admission' => $admission,
            'checklist' => ApplicationDocuments::checklist($admission),
            'maxMb'     => ApplicationDocuments::MAX_FILE_MB,
            'readOnly'  => ! $admission->isEditableByApplicant(),
            'percent'   => ApplicationProgress::percent($admission),
        ]);
    }

    public function store(Request $request)
    {
        $admission = $this->currentApplication();

        if ($redirect = $this->guardEditable($admission)) {
            return $redirect;
        }

        $allowed = ApplicationDocuments::requirementsFor($admission)->pluck('key')->all();
        $maxKb   = ApplicationDocuments::MAX_FILE_MB * 1024;
        $mimes   = implode(',', ApplicationDocuments::ALLOWED_EXTENSIONS);

        $validated = $request->validate([
            'requirement_key' => 'required|string|max:60|in:' . implode(',', $allowed),
            'files'           => 'required|array|max:5',
            'files.*'         => "file|mimes:{$mimes}|max:{$maxKb}",
        ], [
            'files.*.mimes' => get_phrase('Only PDF, JPG and PNG files are accepted.'),
            'files.*.max'   => get_phrase('Each file must be no larger than') . ' ' . ApplicationDocuments::MAX_FILE_MB . 'MB.',
        ]);

        $requirement = ApplicationDocuments::requirementsFor($admission)
            ->firstWhere('key', $validated['requirement_key']);

        $files = $request->file('files');

        // A single-file requirement replaces rather than accumulates: two
        // passport photos on file means a reviewer has to guess which is
        // current. Multi-file requirements (certificates) append.
        if ($requirement && ! $requirement->allow_multiple) {
            $files = [reset($files)];

            foreach ($admission->uploadedDocuments()->where('requirement_key', $requirement->key)->get() as $existing) {
                ApplicationDocuments::delete($existing);
            }
        }

        foreach ($files as $file) {
            ApplicationDocuments::store($admission, $file, $validated['requirement_key'], $requirement->label ?? null, [
                'uploaded_by_applicant_id' => $this->applicant()->id,
            ]);
        }

        AuditLog::record('create', 'Admissions', "Applicant uploaded documents for {$admission->app_number} ({$validated['requirement_key']}).", [
            'event_type'  => 'DATA',
            'record_type' => \App\Models\Admission::class,
            'record_id'   => $admission->id,
            'school_id'   => $admission->school_id,
        ]);

        return back()->with('success', get_phrase('Document uploaded.'));
    }

    public function destroy(int $id)
    {
        $admission = $this->currentApplication();

        if ($redirect = $this->guardEditable($admission)) {
            return $redirect;
        }

        $document = AdmissionDocument::where('admission_id', $admission->id)->findOrFail($id);

        // A verified document is evidence a reviewer has already acted on.
        // Removing it silently would leave the review decision referring to a
        // file that no longer exists.
        if ($document->status === AdmissionDocument::STATUS_VERIFIED) {
            return back()->with('error', get_phrase('This document has already been verified and cannot be removed. Contact the admissions office if it needs to change.'));
        }

        ApplicationDocuments::delete($document);

        return back()->with('success', get_phrase('Document removed.'));
    }
}
