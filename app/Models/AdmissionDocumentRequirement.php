<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdmissionDocumentRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'key', 'label', 'description', 'is_required',
        'allow_multiple', 'applies_to_levels', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'applies_to_levels' => 'array',
        'is_required'       => 'boolean',
        'allow_multiple'    => 'boolean',
        'is_active'         => 'boolean',
    ];

    /**
     * The set every school starts with (installed by
     * AdmissionDocumentRequirementSeeder and by the admin screen's "restore
     * defaults" action). Schools are free to edit, disable or add to these —
     * nothing in the portal hardcodes these keys.
     */
    public const DEFAULTS = [
        ['key' => 'passport_photo',   'label' => 'Passport Photograph',            'description' => 'Recent colour passport-size photograph on a plain background.', 'is_required' => true,  'allow_multiple' => false, 'applies_to_levels' => null],
        ['key' => 'national_id',      'label' => 'National ID or Passport',        'description' => 'Clear scan of your national ID (both sides) or passport bio-data page.', 'is_required' => true, 'allow_multiple' => true, 'applies_to_levels' => null],
        ['key' => 'academic_certs',   'label' => 'Academic Certificates',          'description' => 'Certificates for the qualifications listed in your education history.', 'is_required' => true, 'allow_multiple' => true, 'applies_to_levels' => null],
        ['key' => 'academic_transcripts', 'label' => 'Academic Transcripts',       'description' => 'Official transcripts / results slips from your previous institution.', 'is_required' => true, 'allow_multiple' => true, 'applies_to_levels' => ['Bachelors', 'PGD', 'Masters', 'Degree', 'PhD']],
        ['key' => 'recommendation',   'label' => 'Recommendation Letter',          'description' => 'A signed reference from an academic or professional referee.', 'is_required' => false, 'allow_multiple' => true, 'applies_to_levels' => ['PGD', 'Masters', 'PhD']],
        ['key' => 'other',            'label' => 'Other Supporting Documents',     'description' => 'Anything else you would like the admissions committee to consider.', 'is_required' => false, 'allow_multiple' => true, 'applies_to_levels' => null],
    ];

    /**
     * Whether this requirement applies to an applicant on a given programme
     * level. An unset/empty `applies_to_levels` means "all levels" — that is
     * the common case and is stored as NULL rather than an exhaustive list so
     * adding a new programme level doesn't silently drop requirements.
     */
    public function appliesToLevel(?string $level): bool
    {
        $levels = $this->applies_to_levels;

        if (empty($levels)) {
            return true;
        }

        if ($level === null) {
            // Programme not chosen yet — show it rather than hide a
            // requirement the applicant may well need.
            return true;
        }

        return in_array($level, $levels, true);
    }
}
