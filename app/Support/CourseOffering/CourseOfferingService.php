<?php

namespace App\Support\CourseOffering;

use App\Models\AuditLog;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\CurriculumMembership;
use Illuminate\Support\Facades\DB;
use DomainException;

class CourseOfferingService
{
    public function createDraft(int $schoolId, int $subjectId, int $academicYearId, int $academicPeriodId, ?string $reference = null): CourseOffering
    {
        $reference = $this->normalizeReference($reference);
        return DB::transaction(function () use ($schoolId, $subjectId, $academicYearId, $academicPeriodId, $reference): CourseOffering {
            // Serialize reference allocation per tenant; the database also enforces
            // the existing unique (school_id, reference) constraint.
            if (! DB::table('schools')->where('id', $schoolId)->lockForUpdate()->first(['id'])) {
                throw new DomainException('The tenant School does not exist.');
            }
            $this->assertDraftIdentity($schoolId, $subjectId, $academicYearId, $academicPeriodId, null);
            $reference ??= $this->generatedReference($schoolId, $subjectId, $academicYearId, $academicPeriodId);
            $this->assertDraftIdentity($schoolId, $subjectId, $academicYearId, $academicPeriodId, $reference);

            $id = DB::table('course_offerings')->insertGetId([
                'school_id' => $schoolId,
                'subject_id' => $subjectId,
                'academic_year_id' => $academicYearId,
                'academic_period_id' => $academicPeriodId,
                'reference' => $reference,
                'status' => CourseOffering::STATUS_DRAFT,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $offering = CourseOffering::where('school_id', $schoolId)->findOrFail($id);
            $this->audit('COURSE_OFFERING_DRAFT_CREATED', $offering, [], $offering->only(['subject_id', 'academic_year_id', 'academic_period_id', 'reference', 'status']));

            return $offering;
        });
    }

    public function updateDraft(int $schoolId, int $offeringId, array $changes): CourseOffering
    {
        return DB::transaction(function () use ($schoolId, $offeringId, $changes): CourseOffering {
            $offering = $this->offering($schoolId, $offeringId, true, true);
            $allowed = ['subject_id', 'academic_year_id', 'academic_period_id', 'reference'];
            if (array_diff(array_keys($changes), $allowed)) {
                throw new DomainException('Only Offering identity and reference may be updated while draft.');
            }

            $before = $offering->only($allowed);
            $next = array_merge($before, $changes);
            $subjectId = (int) $next['subject_id'];
            $yearId = (int) $next['academic_year_id'];
            $periodId = (int) $next['academic_period_id'];
            $reference = $next['reference'] === null ? null : trim((string) $next['reference']);
            if ($reference === '') {
                $reference = null;
            }

            if ($subjectId !== (int) $offering->subject_id && $offering->applicability()->exists()) {
                throw new DomainException('Remove all applicability before changing the Offering Subject.');
            }
            $this->assertDraftIdentity($schoolId, $subjectId, $yearId, $periodId, $reference, $offeringId);

            if ($offering->applicability()->exists()) {
                $candidate = clone $offering;
                $candidate->setRawAttributes(array_merge($offering->getAttributes(), [
                    'subject_id' => $subjectId,
                    'academic_year_id' => $yearId,
                    'academic_period_id' => $periodId,
                ]), true);
                foreach ($offering->applicability()->get() as $link) {
                    $this->validateApplicability($candidate, (int) $link->curriculum_membership_id);
                }
            }

            DB::table('course_offerings')->where('school_id', $schoolId)->where('id', $offeringId)->update([
                'subject_id' => $subjectId,
                'academic_year_id' => $yearId,
                'academic_period_id' => $periodId,
                'reference' => $reference,
                'updated_at' => now(),
            ]);
            $offering->refresh();
            $after = $offering->only($allowed);
            if ($before != $after) {
                $this->audit('COURSE_OFFERING_DRAFT_UPDATED', $offering, $before, $after);
            }

            return $offering;
        });
    }

    public function addApplicability(int $schoolId, int $offeringId, int $membershipId): void
    {
        DB::transaction(function () use ($schoolId, $offeringId, $membershipId): void {
            $offering = $this->offering($schoolId, $offeringId, true, true);
            $membership = $this->validateApplicability($offering, $membershipId);
            if ($offering->applicability()->where('curriculum_membership_id', $membership->id)->exists()) {
                throw new DomainException('This Curriculum Membership is already applicable to the Offering.');
            }

            DB::table('course_offering_curriculum_memberships')->insert([
                'school_id' => $schoolId,
                'course_offering_id' => $offering->id,
                'curriculum_id' => $membership->curriculum_id,
                'curriculum_membership_id' => $membership->id,
                'subject_id' => $offering->subject_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit('COURSE_OFFERING_APPLICABILITY_ADDED', $offering, [], [
                'curriculum_id' => $membership->curriculum_id,
                'curriculum_membership_id' => $membership->id,
                'subject_id' => $offering->subject_id,
            ]);
        });
    }

    public function removeApplicability(int $schoolId, int $offeringId, int $membershipId): void
    {
        DB::transaction(function () use ($schoolId, $offeringId, $membershipId): void {
            $offering = $this->offering($schoolId, $offeringId, true, true);
            $link = $offering->applicability()->where('curriculum_membership_id', $membershipId)->first();
            if (! $link) {
                throw new DomainException('The Offering applicability was not found in this tenant.');
            }
            DB::table('course_offering_curriculum_memberships')->where('school_id', $schoolId)
                ->where('course_offering_id', $offeringId)->where('curriculum_membership_id', $membershipId)->delete();
            $this->audit('COURSE_OFFERING_APPLICABILITY_REMOVED', $offering, [
                'curriculum_id' => $link->curriculum_id,
                'curriculum_membership_id' => $link->curriculum_membership_id,
                'subject_id' => $link->subject_id,
            ], []);
        });
    }

    public function open(int $schoolId, int $offeringId): CourseOffering
    {
        return $this->transition($schoolId, $offeringId, CourseOffering::STATUS_DRAFT, CourseOffering::STATUS_OPEN, function (CourseOffering $offering): void {
            $links = $offering->applicability()->get();
            if ($links->isEmpty()) {
                throw new DomainException('At least one approved Curriculum Membership applicability is required to open an Offering.');
            }
            foreach ($links as $link) {
                $this->validateApplicability($offering, (int) $link->curriculum_membership_id);
            }
        }, 'COURSE_OFFERING_OPENED');
    }

    public function start(int $schoolId, int $offeringId): CourseOffering
    {
        return $this->transition($schoolId, $offeringId, CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS, null, 'COURSE_OFFERING_STARTED');
    }

    public function complete(int $schoolId, int $offeringId): CourseOffering
    {
        return $this->transition($schoolId, $offeringId, CourseOffering::STATUS_IN_PROGRESS, CourseOffering::STATUS_COMPLETED, null, 'COURSE_OFFERING_COMPLETED');
    }

    public function cancel(int $schoolId, int $offeringId, string $reason): CourseOffering
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('A nonblank cancellation reason is required.');
        }

        return DB::transaction(function () use ($schoolId, $offeringId, $reason): CourseOffering {
            $offering = $this->offering($schoolId, $offeringId, false, true);
            if (! in_array($offering->status, [CourseOffering::STATUS_DRAFT, CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true)) {
                throw new DomainException("An Offering in {$offering->status} cannot be cancelled.");
            }
            $before = ['status' => $offering->status];
            DB::table('course_offerings')->where('school_id', $schoolId)->where('id', $offeringId)
                ->update(['status' => CourseOffering::STATUS_CANCELLED, 'updated_at' => now()]);
            $offering->refresh();
            $this->audit('COURSE_OFFERING_CANCELLED', $offering, $before, ['status' => $offering->status, 'reason' => $reason]);

            return $offering;
        });
    }

    private function transition(int $schoolId, int $offeringId, string $from, string $to, ?callable $beforeTransition, string $event): CourseOffering
    {
        return DB::transaction(function () use ($schoolId, $offeringId, $from, $to, $beforeTransition, $event): CourseOffering {
            $offering = $this->offering($schoolId, $offeringId, false, true);
            if ($offering->status !== $from) {
                throw new DomainException("Only {$from} Offerings may transition to {$to}.");
            }
            if ($beforeTransition) {
                $beforeTransition($offering);
            }
            DB::table('course_offerings')->where('school_id', $schoolId)->where('id', $offeringId)
                ->update(['status' => $to, 'updated_at' => now()]);
            $offering->refresh();
            $this->audit($event, $offering, ['status' => $from], ['status' => $to]);

            return $offering;
        });
    }

    private function assertDraftIdentity(int $schoolId, int $subjectId, int $yearId, int $periodId, ?string $reference, ?int $ignoreOfferingId = null): void
    {
        if (! DB::table('subjects')->where('school_id', $schoolId)->where('id', $subjectId)->exists()) {
            throw new DomainException('The Subject must belong to the Offering tenant.');
        }
        $year = DB::table('academic_years')->where('school_id', $schoolId)->where('id', $yearId)->first();
        if (! $year) {
            throw new DomainException('The Academic Year must belong to the Offering tenant.');
        }
        $period = DB::table('academic_periods')->where('school_id', $schoolId)->where('academic_year_id', $yearId)->where('id', $periodId)->first();
        if (! $period) {
            throw new DomainException('The Academic Period must belong to the Offering tenant and selected Academic Year.');
        }
        if ($reference !== null && (mb_strlen($reference) > 50 || DB::table('course_offerings')->where('school_id', $schoolId)->where('reference', $reference)->when($ignoreOfferingId, fn ($query) => $query->where('id', '<>', $ignoreOfferingId))->exists())) {
            throw new DomainException('The optional Offering reference must be at most 50 characters and unique within the tenant.');
        }
    }

    private function normalizeReference(?string $reference): ?string
    {
        if ($reference === null) {
            return null;
        }
        $reference = trim($reference);
        return $reference === '' ? null : $reference;
    }

    private function generatedReference(int $schoolId, int $subjectId, int $yearId, int $periodId): string
    {
        $subject = DB::table('subjects')->where('school_id', $schoolId)->where('id', $subjectId)->first(['code']);
        $year = DB::table('academic_years')->where('school_id', $schoolId)->where('id', $yearId)->first(['start_date']);
        $period = DB::table('academic_periods')->where('school_id', $schoolId)->where('academic_year_id', $yearId)->where('id', $periodId)->first(['type', 'sequence']);

        if (! $subject || ! $year || ! $period) {
            throw new DomainException('A tenant-scoped Course Unit, Academic Year and Academic Period are required to generate the Offering reference.');
        }

        $code = strtoupper(trim((string) $subject->code));
        $code = trim((string) preg_replace('/[^A-Z0-9]+/', '-', $code), '-');
        if ($code === '') {
            $code = 'CU'.$subjectId;
        }
        $startYear = date('Y', strtotime((string) $year->start_date));
        $type = strtolower((string) $period->type);
        $abbreviation = match ($type) {
            'semester' => 'S',
            'term' => 'T',
            default => strtoupper(substr((string) preg_replace('/[^A-Z0-9]/i', '', $type), 0, 2)) ?: 'P',
        };
        $periodPart = $abbreviation.(int) $period->sequence;
        $tail = '-'.$startYear.'-'.$periodPart;

        for ($ordinal = 1; ; $ordinal++) {
            $suffix = $ordinal === 1 ? '' : '-'.$ordinal;
            $availableCodeLength = max(1, 50 - strlen($tail) - strlen($suffix));
            $candidate = substr($code, 0, $availableCodeLength).$tail.$suffix;
            if (! DB::table('course_offerings')->where('school_id', $schoolId)->where('reference', $candidate)->exists()) {
                return $candidate;
            }
        }
    }

    private function validateApplicability(CourseOffering $offering, int $membershipId): CurriculumMembership
    {
        $membership = CurriculumMembership::where('school_id', $offering->school_id)->whereKey($membershipId)->first();
        if (! $membership) {
            throw new DomainException('The Curriculum Membership must belong to the Offering tenant.');
        }
        if ((int) $membership->subject_id !== (int) $offering->subject_id) {
            throw new DomainException('The Curriculum Membership Subject must match the Offering Subject.');
        }
        $curriculum = Curriculum::where('school_id', $offering->school_id)->whereKey($membership->curriculum_id)->first();
        if (! $curriculum || $curriculum->status !== 'approved') {
            throw new DomainException('New or opened applicability requires a Curriculum that is approved.');
        }
        $academicYear = DB::table('academic_years')->where('school_id', $offering->school_id)->where('id', $offering->academic_year_id)->first();
        if (! $academicYear) {
            throw new DomainException('The Offering Academic Year is not valid for this tenant.');
        }
        if ($curriculum->effective_academic_year_id !== null) {
            $effectiveYear = DB::table('academic_years')->where('school_id', $offering->school_id)->where('id', $curriculum->effective_academic_year_id)->first();
            if (! $effectiveYear || $academicYear->start_date < $effectiveYear->start_date) {
                throw new DomainException('The Offering Academic Year cannot precede the Curriculum effective Academic Year.');
            }
        }
        $period = DB::table('academic_periods')->where('school_id', $offering->school_id)
            ->where('academic_year_id', $offering->academic_year_id)->where('id', $offering->academic_period_id)->first();
        if (! $period || $membership->period_type === null || $membership->period_sequence === null) {
            throw new DomainException('Applicability requires a placed Curriculum Membership and a valid Offering Academic Period.');
        }
        if ($membership->period_type !== $period->type || (int) $membership->period_sequence !== (int) $period->sequence) {
            throw new DomainException('The Curriculum Membership relative period must match the Offering Academic Period.');
        }

        return $membership;
    }

    private function offering(int $schoolId, int $offeringId, bool $draftOnly, bool $lock = false): CourseOffering
    {
        $query = CourseOffering::where('school_id', $schoolId)->whereKey($offeringId);
        if ($lock) {
            $query->lockForUpdate();
        }
        $offering = $query->first();
        if (! $offering) {
            throw new DomainException('Course Offering not found in this tenant.');
        }
        if ($draftOnly && $offering->status !== CourseOffering::STATUS_DRAFT) {
            throw new DomainException('Only draft Offerings may be structurally changed.');
        }

        return $offering;
    }

    private function audit(string $action, CourseOffering $offering, array $old, array $new): void
    {
        AuditLog::record($action, 'Course Offerings', "{$action} for Course Offering #{$offering->id}.", [
            'school_id' => $offering->school_id,
            'record_type' => CourseOffering::class,
            'record_id' => $offering->id,
            'event_type' => 'COURSE_OFFERING',
            'old_values' => $old ?: null,
            'new_values' => $new ?: null,
        ]);
    }
}
