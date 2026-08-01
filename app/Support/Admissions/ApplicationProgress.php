<?php

namespace App\Support\Admissions;

use App\Models\Admission;

/**
 * The single definition of what an application consists of, and therefore of
 * what "33% complete" means.
 *
 * Completion is *derived from the saved data*, never from a stored flag. A
 * "completed_steps" list would drift the moment a reviewer sends a document
 * back or an applicant clears a field they had already filled, and the
 * applicant would be told they were done while the submit button stayed
 * disabled. Deriving costs one extra query and is always true.
 *
 * Step order is not arbitrary: the programme is chosen before documents
 * because the required document list depends on the programme's level, and
 * before payment because the fee comes from the chosen intake.
 */
class ApplicationProgress
{
    public const STEP_PERSONAL   = 'personal';
    public const STEP_PROGRAMME  = 'programme';
    public const STEP_EDUCATION  = 'education';
    public const STEP_DOCUMENTS  = 'documents';
    public const STEP_PAYMENT    = 'payment';
    public const STEP_REVIEW     = 'review';

    public const ORDER = [
        self::STEP_PERSONAL,
        self::STEP_PROGRAMME,
        self::STEP_EDUCATION,
        self::STEP_DOCUMENTS,
        self::STEP_PAYMENT,
        self::STEP_REVIEW,
    ];

    /** Personal-details fields an application cannot be submitted without. */
    private const REQUIRED_PERSONAL_FIELDS = [
        'first_name', 'last_name', 'email', 'phone', 'dob', 'gender',
        'nationality', 'physical_address', 'nok_name', 'nok_phone',
    ];

    private static function meta(): array
    {
        return [
            self::STEP_PERSONAL => [
                'label' => get_phrase('Personal Information'),
                'hint'  => get_phrase('Your name, contact details and next of kin.'),
                'icon'  => 'bi-person-vcard',
            ],
            self::STEP_PROGRAMME => [
                'label' => get_phrase('Programme Selection'),
                'hint'  => get_phrase('What you want to study, and when you want to start.'),
                'icon'  => 'bi-mortarboard',
            ],
            self::STEP_EDUCATION => [
                'label' => get_phrase('Education History'),
                'hint'  => get_phrase('Schools attended and qualifications obtained.'),
                'icon'  => 'bi-journal-text',
            ],
            self::STEP_DOCUMENTS => [
                'label' => get_phrase('Supporting Documents'),
                'hint'  => get_phrase('Certificates, transcripts and identification.'),
                'icon'  => 'bi-folder2-open',
            ],
            self::STEP_PAYMENT => [
                'label' => get_phrase('Application Fee'),
                'hint'  => get_phrase('Settle the application fee for your chosen intake.'),
                'icon'  => 'bi-credit-card',
            ],
            self::STEP_REVIEW => [
                'label' => get_phrase('Review & Submit'),
                'hint'  => get_phrase('Check everything, then send it to the admissions office.'),
                'icon'  => 'bi-send-check',
            ],
        ];
    }

    /**
     * Every step with its label, applicability and completion state.
     *
     * A step that does not apply (a free intake's payment step) is returned
     * with applicable=false rather than dropped, so callers that render the
     * sequence and callers that count progress see the same list.
     */
    public static function steps(Admission $admission): array
    {
        $meta  = self::meta();
        $steps = [];

        foreach (self::ORDER as $key) {
            $applicable = self::isApplicable($admission, $key);

            $steps[] = [
                'key'        => $key,
                'label'      => $meta[$key]['label'],
                'hint'       => $meta[$key]['hint'],
                'icon'       => $meta[$key]['icon'],
                'applicable' => $applicable,
                'complete'   => $applicable && self::isComplete($admission, $key),
                'url'        => route('applicant.application.step', $key),
            ];
        }

        return $steps;
    }

    public static function isApplicable(Admission $admission, string $step): bool
    {
        if ($step === self::STEP_PAYMENT) {
            return ApplicationFee::isRequired($admission);
        }

        return true;
    }

    public static function isComplete(Admission $admission, string $step): bool
    {
        switch ($step) {
            case self::STEP_PERSONAL:
                foreach (self::REQUIRED_PERSONAL_FIELDS as $field) {
                    if (blank($admission->{$field})) {
                        return false;
                    }
                }

                return true;

            case self::STEP_PROGRAMME:
                return ! blank($admission->programme_id) && ! blank($admission->intake_session_id);

            case self::STEP_EDUCATION:
                // Either structured rows or the legacy free-text summary
                // counts: staff-entered and pre-portal applications only ever
                // had the latter, and they are not incomplete.
                return $admission->educationHistory()->exists() || ! blank($admission->qualifications);

            case self::STEP_DOCUMENTS:
                return ApplicationDocuments::isComplete($admission);

            case self::STEP_PAYMENT:
                return ApplicationFee::isSettled($admission);

            case self::STEP_REVIEW:
                return ! blank($admission->submitted_at);
        }

        return false;
    }

    /** Applicable steps only — what the progress bar is measured against. */
    public static function applicableSteps(Admission $admission): array
    {
        return array_values(array_filter(self::steps($admission), fn ($step) => $step['applicable']));
    }

    public static function completedCount(Admission $admission): int
    {
        return count(array_filter(self::applicableSteps($admission), fn ($step) => $step['complete']));
    }

    public static function totalCount(Admission $admission): int
    {
        return count(self::applicableSteps($admission));
    }

    public static function percent(Admission $admission): int
    {
        $total = self::totalCount($admission);

        if ($total === 0) {
            return 0;
        }

        return (int) round((self::completedCount($admission) / $total) * 100);
    }

    /** The first unfinished step — what "Continue Application" points at. */
    public static function nextStep(Admission $admission): ?array
    {
        foreach (self::applicableSteps($admission) as $step) {
            if (! $step['complete']) {
                return $step;
            }
        }

        return null;
    }

    /**
     * Whether the application can be submitted: everything except the review
     * step itself must be complete. Review is excluded because submitting is
     * what completes it.
     */
    public static function canSubmit(Admission $admission): bool
    {
        return empty(self::blockers($admission));
    }

    /**
     * Human-readable reasons submission is blocked, ready to show as a list.
     * Document requirements are itemised rather than collapsed into
     * "Supporting Documents" so the applicant knows exactly what to upload.
     */
    public static function blockers(Admission $admission): array
    {
        $blockers = [];

        foreach (self::applicableSteps($admission) as $step) {
            if ($step['key'] === self::STEP_REVIEW || $step['complete']) {
                continue;
            }

            if ($step['key'] === self::STEP_DOCUMENTS) {
                foreach (ApplicationDocuments::outstanding($admission) as $item) {
                    $blockers[] = $item;
                }

                continue;
            }

            $blockers[] = $step['label'];
        }

        return $blockers;
    }

    /**
     * The dashboard checklist. Mirrors the wizard steps and adds the terminal
     * "submitted" row, which is not a step the applicant fills in but is the
     * one they most want to see ticked.
     */
    public static function checklist(Admission $admission): array
    {
        $rows = [];

        foreach (self::applicableSteps($admission) as $step) {
            if ($step['key'] === self::STEP_REVIEW) {
                continue;
            }

            $rows[] = [
                'label'    => $step['label'],
                'complete' => $step['complete'],
                'url'      => $step['url'],
            ];
        }

        $rows[] = [
            'label'    => get_phrase('Application Submitted'),
            'complete' => ! blank($admission->submitted_at),
            'url'      => route('applicant.application.step', self::STEP_REVIEW),
        ];

        return $rows;
    }
}
