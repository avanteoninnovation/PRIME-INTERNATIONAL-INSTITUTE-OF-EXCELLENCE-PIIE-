<?php

namespace App\Support\Admissions;

use App\Models\Admission;
use App\Models\IntakeSession;
use App\Models\School;
use Illuminate\Support\Carbon;

/**
 * Builds the human-facing application reference stored in
 * `admissions.app_number`, e.g. PIIE-2526-S-P0042.
 *
 *   PIIE  institution prefix   global_settings.application_ref_prefix, or
 *                              initials derived from the school title
 *   2526  academic year        the year the intake belongs to, in YYYY form
 *                              (2025/26 → "2526")
 *   S     channel              S(elf-service portal) / A(gent) / O(ffice)
 *   P0042 sequence             per school, per academic year
 *
 * Every segment is knowable the moment a draft is created, which is the
 * point: applicants quote this number when they contact the admissions
 * office mid-application, so it must be issued up front and must never
 * change afterwards. That rules out encoding the programme or intake, which
 * the applicant has not chosen yet at step one.
 */
class ApplicationReference
{
    private const CHANNEL_LETTERS = [
        'public'      => 'S',
        'agent'       => 'A',
        'staff_entry' => 'O',
    ];

    public static function generate(int $schoolId, string $source = 'public', ?int $intakeSessionId = null): string
    {
        $prefix   = self::prefix($schoolId);
        $yearCode = self::academicYearCode($intakeSessionId);
        $channel  = self::CHANNEL_LETTERS[$source] ?? 'S';
        $stem     = "{$prefix}-{$yearCode}-{$channel}-P";

        // Sequence is derived from what already exists rather than a counter
        // table, then re-checked on insert collision. Two applicants starting
        // a draft in the same second is rare but not impossible, and a
        // duplicate app_number would hit the unique index and lose one of
        // them, so the loop is the cheap insurance.
        $existing = Admission::where('school_id', $schoolId)
            ->where('app_number', 'like', $stem . '%')
            ->count();

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $candidate = $stem . str_pad((string) ($existing + 1 + $attempt), 4, '0', STR_PAD_LEFT);

            if (! Admission::where('app_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Pathological contention — fall back to something guaranteed unique
        // rather than failing the applicant's very first action.
        return $stem . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Institution prefix. Configurable because the derived initials are a
     * guess: "Prime International Institute of Excellence" gives PIIE, but a
     * school whose brand acronym doesn't match its legal title needs to say so.
     */
    public static function prefix(int $schoolId): string
    {
        $configured = get_settings('application_ref_prefix');

        if (! empty($configured)) {
            return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $configured));
        }

        $title = (string) (School::where('id', $schoolId)->value('title') ?? '');

        // Initials of the significant words: "Prime International Institute
        // of Excellence" → PIIE (stop-words dropped).
        $stopWords = ['of', 'the', 'and', 'for', 'in', 'at', 'a', 'an'];
        $initials  = '';

        foreach (preg_split('/\s+/', trim($title)) as $word) {
            $word = preg_replace('/[^A-Za-z0-9]/', '', $word);

            if ($word === '' || in_array(strtolower($word), $stopWords, true)) {
                continue;
            }

            $initials .= strtoupper($word[0]);
        }

        return $initials !== '' ? substr($initials, 0, 6) : 'APP';
    }

    /**
     * Academic year in YYYY form. Anchored to the intake's opening date when
     * there is one, so a January-2027 intake opened in late 2026 still reads
     * as its own academic year rather than the year the form was filled in.
     */
    public static function academicYearCode(?int $intakeSessionId = null): string
    {
        $anchor = null;

        if ($intakeSessionId) {
            $openDate = IntakeSession::where('id', $intakeSessionId)->value('open_date');
            $anchor   = $openDate ? Carbon::parse($openDate) : null;
        }

        $anchor = $anchor ?: Carbon::now();

        // Academic years are taken to start in August, the usual intake
        // boundary for the institutions this product serves.
        $startYear = $anchor->month >= 8 ? $anchor->year : $anchor->year - 1;

        return substr((string) $startYear, 2, 2) . substr((string) ($startYear + 1), 2, 2);
    }
}
