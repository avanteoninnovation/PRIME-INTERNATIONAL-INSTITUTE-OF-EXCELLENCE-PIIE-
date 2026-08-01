<?php

namespace Database\Seeders;

use App\Models\AdmissionDocumentRequirement;
use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * Installs the standard supporting-document checklist for every school.
 *
 * Merge, not overwrite: a requirement is created only if that school does not
 * already have one under the same key, so re-running the seeder after an
 * upgrade adds newly-shipped defaults without discarding an institution's
 * wording, ordering or required/optional decisions — the same contract
 * OnlineExamPermissionSeeder honours for permission JSON.
 */
class AdmissionDocumentRequirementSeeder extends Seeder
{
    public function run()
    {
        $schoolIds = School::query()->pluck('id');

        foreach ($schoolIds as $schoolId) {
            foreach (AdmissionDocumentRequirement::DEFAULTS as $index => $definition) {
                $exists = AdmissionDocumentRequirement::where('school_id', $schoolId)
                    ->where('key', $definition['key'])
                    ->exists();

                if ($exists) {
                    continue;
                }

                AdmissionDocumentRequirement::create(array_merge($definition, [
                    'school_id'  => $schoolId,
                    'sort_order' => $index,
                    'is_active'  => true,
                ]));
            }
        }
    }
}
