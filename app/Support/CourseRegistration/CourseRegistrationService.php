<?php

namespace App\Support\CourseRegistration;

use App\Models\AuditLog;
use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\Curriculum;
use App\Models\CurriculumMembership;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\User;
use App\Support\Curriculum\StudentCurriculumAssignmentService;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseRegistrationService
{
    public function __construct(private StudentRegistrationConfirmationEligibility $confirmationEligibility, private StudentCurriculumAssignmentService $assignments)
    {
    }

    public function registerStudentForOffering(int $schoolId, int $studentId, int $offeringId, ?int $membershipId = null, ?int $actorId = null): CourseRegistration
    {
        $actorId ??= auth()->id();
        return DB::transaction(function () use ($schoolId, $studentId, $offeringId, $membershipId, $actorId): CourseRegistration {
            $student = $this->student($schoolId, $studentId, true);
            $this->assertActor($student, $actorId);
            $offering = CourseOffering::where('school_id', $schoolId)->whereKey($offeringId)->first();
            if (! $offering) {
                throw new DomainException('Course Offering not found in this tenant.');
            }
            $membership = $this->applicableMembership($schoolId, $student, $offering, $membershipId);
            $offering = CourseOffering::where('school_id', $schoolId)->whereKey($offeringId)->lockForUpdate()->first();
            if ($offering->status !== CourseOffering::STATUS_OPEN) {
                throw new DomainException('Students may register only while the Course Offering is open.');
            }
            $existing = CourseRegistration::where('school_id', $schoolId)->where('student_id', $studentId)
                ->where('course_offering_id', $offeringId)->lockForUpdate()->first();
            if ($existing) {
                if ((int) $existing->curriculum_membership_id !== (int) $membership->id) {
                    throw new DomainException('An Offering registration already exists with different Curriculum provenance.');
                }
                if ($existing->status === CourseRegistration::STATUS_DROPPED) {
                    throw new DomainException('This student already has a dropped registration for this Offering; same-Offering re-registration is not allowed.');
                }
                return $existing;
            }

            try {
                $registration = CourseRegistration::create([
                    'school_id' => $schoolId,
                    'student_id' => $studentId,
                    'course_offering_id' => $offering->id,
                    'curriculum_membership_id' => $membership->id,
                    'subject_id' => $offering->subject_id,
                    'session_id' => null,
                    'registered_credits' => $membership->credits,
                    'registered_classification' => $membership->classification,
                    'status' => CourseRegistration::STATUS_REGISTERED,
                ]);
            } catch (\Illuminate\Database\QueryException $exception) {
                if ($this->isOfferingUniqueConflict($exception)) {
                    $existing = CourseRegistration::where('school_id', $schoolId)->where('student_id', $studentId)
                        ->where('course_offering_id', $offeringId)->first();
                    if ($existing && $existing->status !== CourseRegistration::STATUS_DROPPED) {
                        return $existing;
                    }
                    throw new DomainException('A registration for this Offering already exists.');
                }
                throw $exception;
            }

            $this->audit('COURSE_REGISTRATION_CREATED', $registration, null, $actorId);
            return $registration;
        });
    }

    public function confirmRegistration(int $schoolId, int $registrationId, ?int $actorId = null): CourseRegistration
    {
        $actorId ??= auth()->id();
        return DB::transaction(function () use ($schoolId, $registrationId, $actorId): CourseRegistration {
            $registration = CourseRegistration::where('school_id', $schoolId)->whereKey($registrationId)->first();
            if (! $registration || ! $registration->course_offering_id) {
                throw new DomainException('Offering-backed registration not found in this tenant.');
            }
            $student = $this->student($schoolId, (int) $registration->student_id, true);
            $this->assertActor($student, $actorId);
            $offering = CourseOffering::where('school_id', $schoolId)->whereKey($registration->course_offering_id)->first();
            if (! $offering) {
                throw new DomainException('Registration Offering and Subject context is invalid.');
            }
            $membership = $this->applicableMembership($schoolId, $student, $offering, (int) $registration->curriculum_membership_id, true);
            $offering = CourseOffering::where('school_id', $schoolId)->whereKey($registration->course_offering_id)->lockForUpdate()->first();
            $registration = CourseRegistration::where('school_id', $schoolId)->whereKey($registrationId)->lockForUpdate()->first();
            if (! $offering || (int) $offering->subject_id !== (int) $registration->subject_id) {
                throw new DomainException('Registration Offering and Subject context is invalid.');
            }
            if ((string) $registration->registered_credits !== number_format((float) $membership->credits, 2, '.', '')
                || $registration->registered_classification !== $membership->classification) {
                throw new DomainException('Registration academic provenance snapshot is inconsistent.');
            }
            if ($registration->status === CourseRegistration::STATUS_CONFIRMED) {
                return $registration;
            }
            if ($registration->status !== CourseRegistration::STATUS_REGISTERED) {
                throw new DomainException('Only registered students may confirm an Offering registration.');
            }
            if ($offering->status !== CourseOffering::STATUS_OPEN) {
                throw new DomainException('Registration confirmation is allowed only while the Course Offering is open.');
            }
            if (! $this->confirmationEligibility->allows($student)) {
                throw ValidationException::withMessages(['registration' => 'Outstanding fees must be settled before confirming course registration.']);
            }

            $registration->status = CourseRegistration::STATUS_CONFIRMED;
            $registration->save();
            $this->audit('COURSE_REGISTRATION_CONFIRMED', $registration, CourseRegistration::STATUS_REGISTERED, $actorId);
            return $registration;
        });
    }

    public function dropRegistration(int $schoolId, int $registrationId, ?int $actorId = null, ?string $reason = null): CourseRegistration
    {
        $actorId ??= auth()->id();
        return DB::transaction(function () use ($schoolId, $registrationId, $actorId, $reason): CourseRegistration {
            $registration = CourseRegistration::where('school_id', $schoolId)->whereKey($registrationId)->first();
            if (! $registration || ! $registration->course_offering_id) {
                throw new DomainException('Offering-backed registration not found in this tenant.');
            }
            $student = $this->student($schoolId, (int) $registration->student_id, true);
            $this->assertActor($student, $actorId);
            $offering = CourseOffering::where('school_id', $schoolId)->whereKey($registration->course_offering_id)->lockForUpdate()->first();
            $registration = CourseRegistration::where('school_id', $schoolId)->whereKey($registrationId)->lockForUpdate()->first();
            if (! $offering || (int) $offering->subject_id !== (int) $registration->subject_id) {
                throw new DomainException('Registration Offering and Subject context is invalid.');
            }
            $this->storedMembership($schoolId, $offering, $registration);
            if ($registration->status === CourseRegistration::STATUS_DROPPED) {
                return $registration;
            }
            if (! in_array($registration->status, [CourseRegistration::STATUS_REGISTERED, CourseRegistration::STATUS_CONFIRMED], true)) {
                throw new DomainException('Only active registrations may be dropped.');
            }
            if (! in_array($offering->status, [CourseOffering::STATUS_OPEN, CourseOffering::STATUS_IN_PROGRESS], true)) {
                throw new DomainException('Registration cannot be dropped for a completed or cancelled Offering.');
            }
            $before = $registration->status;
            $registration->status = CourseRegistration::STATUS_DROPPED;
            $registration->save();
            $this->audit('COURSE_REGISTRATION_DROPPED', $registration, $before, $actorId, $reason);
            return $registration;
        });
    }

    private function student(int $schoolId, int $studentId, bool $lock = false): User
    {
        $query = User::where('school_id', $schoolId)->whereKey($studentId);
        if ($lock) $query->lockForUpdate();
        $student = $query->first();
        if (! $student || (int) $student->role_id !== 7 || $student->account_status === 'disable') {
            throw new DomainException('Student must be an enabled student User in this tenant.');
        }
        return $student;
    }

    private function assertActor(User $student, ?int $actorId): void
    {
        $actor = $actorId === null ? null : User::where('school_id', $student->school_id)->whereKey($actorId)->first();
        if (! $actor || $actor->account_status === 'disable') {
            throw new DomainException('Registration actor must be an enabled User in this tenant.');
        }
        if ((int) $actor->role_id === 7 && (int) $actor->id !== (int) $student->id) {
            throw new DomainException('Student self-service may act only on its own registration.');
        }
    }

    private function applicableMembership(int $schoolId, User $student, CourseOffering $offering, ?int $membershipId, bool $allowRetired = false): CurriculumMembership
    {
        $assignment = $this->assignments->assignmentForAcademicYear($schoolId, (int) $student->id, (int) $offering->academic_year_id, true);
        if (! $assignment) throw new DomainException('No Student Curriculum assignment governs the Offering AcademicYear.');
        $curriculum = Curriculum::where('school_id', $schoolId)->whereKey($assignment->curriculum_id)->first();
        if (! $curriculum || (int) $curriculum->programme_id !== (int) $assignment->programme_id) {
            throw new DomainException('Student Curriculum assignment Programme/Curriculum provenance is corrupt.');
        }
        $profile = StudentProfile::where('school_id', $schoolId)->where('user_id', $student->id)->first();
        if ($profile?->programme_id !== null && (int) $profile->programme_id !== (int) $assignment->programme_id) {
            throw new DomainException('StudentProfile Programme does not match the governing Curriculum assignment; repair is required.');
        }
        if (! Subject::where('school_id', $schoolId)->whereKey($offering->subject_id)->exists()) {
            throw new DomainException('Course Offering Subject must belong to this tenant.');
        }
        $matches = DB::table('course_offering_curriculum_memberships as x')
            ->join('curriculum_memberships as m', function ($join) use ($schoolId): void {
                $join->on('m.id', '=', 'x.curriculum_membership_id')->where('m.school_id', '=', $schoolId);
            })
            ->where('x.school_id', $schoolId)->where('x.course_offering_id', $offering->id)
            ->where('x.curriculum_id', $assignment->curriculum_id)
            ->where('x.subject_id', $offering->subject_id)->where('m.subject_id', $offering->subject_id)
            ->select('m.id')->get();
        if ($matches->count() === 0) throw new DomainException('The assigned Curriculum has no Membership for this Offering Subject.');
        if ($matches->count() !== 1) throw new DomainException('The assigned Curriculum has multiple applicable Memberships; registration provenance is ambiguous.');
        $resolvedId = (int) $matches->first()->id;
        if ($membershipId !== null && $membershipId !== $resolvedId) throw new DomainException('Supplied Curriculum Membership conflicts with the student assignment-governed Membership.');

        $membership = CurriculumMembership::where('school_id', $schoolId)->where('curriculum_id', $assignment->curriculum_id)->whereKey($resolvedId)->first();
        $allowedCurriculumStatuses = $allowRetired ? ['approved', 'retired'] : ['approved'];
        if (! $membership || ! $curriculum || ! in_array($curriculum->status, $allowedCurriculumStatuses, true)) {
            throw new DomainException('New registration requires Membership in an approved Curriculum.');
        }
        if (! DB::table('programmes')->where('school_id', $schoolId)->where('id', $curriculum->programme_id)->exists()) {
            throw new DomainException('Curriculum Programme must belong to this tenant.');
        }
        if ((int) $membership->subject_id !== (int) $offering->subject_id) {
            throw new DomainException('Curriculum Membership Subject must match the Offering Subject.');
        }

        return $membership;
    }

    private function storedMembership(int $schoolId, CourseOffering $offering, CourseRegistration $registration): CurriculumMembership
    {
        $link = DB::table('course_offering_curriculum_memberships')
            ->where('school_id', $schoolId)->where('course_offering_id', $offering->id)
            ->where('curriculum_membership_id', $registration->curriculum_membership_id)
            ->where('subject_id', $registration->subject_id)->first();
        $membership = CurriculumMembership::where('school_id', $schoolId)
            ->where('subject_id', $registration->subject_id)->whereKey($registration->curriculum_membership_id)->first();
        $curriculum = $membership ? Curriculum::where('school_id', $schoolId)->whereKey($membership->curriculum_id)->first() : null;
        if (! $link || ! $membership || ! $curriculum || (int) $link->curriculum_id !== (int) $membership->curriculum_id
            || ! in_array($curriculum->status, ['approved', 'retired'], true)
            || (string) $registration->registered_credits !== number_format((float) $membership->credits, 2, '.', '')
            || $registration->registered_classification !== $membership->classification) {
            throw new DomainException('Stored registration Offering/Membership/snapshot provenance is corrupt.');
        }
        return $membership;
    }

    private function isOfferingUniqueConflict(\Illuminate\Database\QueryException $exception): bool
    {
        return str_contains(strtolower($exception->getMessage()), 'cr_school_student_offering_uq')
            || str_contains(strtolower($exception->getMessage()), 'course_registrations.school_id');
    }

    private function audit(string $action, CourseRegistration $registration, ?string $oldStatus, ?int $actorId, ?string $reason = null): void
    {
        AuditLog::record($action, 'Course Registrations', "{$action} for registration #{$registration->id}.", [
            'school_id' => $registration->school_id,
            'record_type' => CourseRegistration::class,
            'record_id' => $registration->id,
            'event_type' => 'COURSE_REGISTRATION',
            'old_values' => $oldStatus ? ['status' => $oldStatus] : null,
            'new_values' => array_filter([
                'student_id' => $registration->student_id,
                'course_offering_id' => $registration->course_offering_id,
                'curriculum_membership_id' => $registration->curriculum_membership_id,
                'status' => $registration->status,
                'actor_id' => $actorId,
                'reason' => $reason,
            ], fn ($value) => $value !== null),
        ]);
    }
}
