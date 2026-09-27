<?php

namespace App\Support\CourseRegistration;

use App\Models\CourseOffering;
use App\Models\CourseRegistration;
use App\Models\Curriculum;
use App\Models\Programme;
use App\Models\User;
use App\Support\Curriculum\StudentCurriculumAssignmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Tenant-scoped administrator roster, derived from governed Study Plan assignments. */
class CourseOfferingRoster
{
    public function __construct(private StudentCurriculumAssignmentService $assignments) {}

    public function eligible(CourseOffering $offering)
    {
        $schoolId = (int) $offering->school_id;
        if ($offering->status !== CourseOffering::STATUS_OPEN
            || ! Schema::hasTable('course_registrations')
            || ! Schema::hasColumn('course_registrations', 'course_offering_id')
            || ! Schema::hasColumn('course_registrations', 'curriculum_membership_id')) return collect();

        $memberships = DB::table('course_offering_curriculum_memberships as x')
            ->join('curriculum_memberships as m', function ($join) use ($schoolId): void {
                $join->on('m.id', '=', 'x.curriculum_membership_id')->on('m.school_id', '=', 'x.school_id');
            })
            ->join('academic_periods as ap', function ($join) use ($schoolId, $offering): void {
                $join->where('ap.id', '=', (int) $offering->academic_period_id)->where('ap.school_id', '=', $schoolId)
                    ->where('ap.academic_year_id', '=', (int) $offering->academic_year_id);
            })
            ->where('x.school_id', $schoolId)->where('x.course_offering_id', $offering->id)
            ->where('x.subject_id', $offering->subject_id)->where('m.subject_id', $offering->subject_id)
            ->whereColumn('x.curriculum_id', 'm.curriculum_id')->whereColumn('m.period_type', 'ap.type')->whereColumn('m.period_sequence', 'ap.sequence')
            ->select('x.curriculum_id', 'm.id as membership_id', 'm.credits', 'm.classification')->get()
            ->groupBy('curriculum_id');

        if ($memberships->isEmpty()) return collect();
        $alreadyRegistered = CourseRegistration::query()->where('school_id', $schoolId)
            ->where('course_offering_id', $offering->id)->pluck('student_id')->map(fn ($id) => (int) $id)->flip();
        $students = User::query()->where('school_id', $schoolId)->where('role_id', 7)
            ->where(fn ($q) => $q->whereNull('account_status')->orWhere('account_status', '!=', 'disable'))
            ->with(['studentProfile' => fn ($q) => $q->where('school_id', $schoolId)])->orderBy('name')->get();

        return $students->filter(function (User $student) use ($offering, $schoolId, $memberships, $alreadyRegistered): bool {
            if ($alreadyRegistered->has((int) $student->id)) return false;
            try {
                $assignment = $this->assignments->assignmentForAcademicYear($schoolId, (int) $student->id, (int) $offering->academic_year_id);
            } catch (\DomainException) {
                return false;
            }
            if (! $assignment || ! $memberships->has((int) $assignment->curriculum_id)) return false;
            $profile = $student->studentProfile;
            if ($profile?->programme_id !== null && (int) $profile->programme_id !== (int) $assignment->programme_id) return false;
            $curriculum = Curriculum::query()->where('school_id', $schoolId)->whereKey($assignment->curriculum_id)->first();
            $matches = $memberships->get((int) $assignment->curriculum_id, collect());
            if (! $curriculum || $curriculum->status !== 'approved' || (int) $curriculum->programme_id !== (int) $assignment->programme_id || $matches->count() !== 1) return false;
            return DB::table('programmes')->where('school_id', $schoolId)->where('id', $assignment->programme_id)->exists();
        })->map(function (User $student) use ($offering, $schoolId, $memberships): User {
            $assignment = $this->assignments->assignmentForAcademicYear($schoolId, (int) $student->id, (int) $offering->academic_year_id);
            $membership = $memberships->get((int) $assignment->curriculum_id)->first();
            $student->setAttribute('roster_assignment', $assignment);
            $student->setAttribute('roster_membership_id', (int) $membership->membership_id);
            $curriculum = Curriculum::query()->where('school_id', $schoolId)->whereKey($assignment->curriculum_id)->first();
            if ($curriculum) {
                $curriculum->setRelation('programme', Programme::query()->where('school_id', $schoolId)->whereKey($curriculum->programme_id)->first());
            }
            $student->setAttribute('roster_curriculum', $curriculum);
            return $student;
        })->values();
    }

    public function registered(CourseOffering $offering)
    {
        $schoolId = (int) $offering->school_id;
        if (! Schema::hasTable('course_registrations')
            || ! Schema::hasColumn('course_registrations', 'course_offering_id')
            || ! Schema::hasColumn('course_registrations', 'curriculum_membership_id')) return collect();
        return CourseRegistration::query()->where('course_registrations.school_id', $schoolId)
            ->where('course_registrations.course_offering_id', $offering->id)
            ->join('users', function ($join) use ($schoolId): void {
                $join->on('users.id', '=', 'course_registrations.student_id')->where('users.school_id', '=', $schoolId);
            })->leftJoin('student_profiles as sp', function ($join) use ($schoolId): void {
                $join->on('sp.user_id', '=', 'users.id')->where('sp.school_id', '=', $schoolId);
            })->leftJoin('curriculum_memberships as m', function ($join) use ($schoolId): void {
                $join->on('m.id', '=', 'course_registrations.curriculum_membership_id')->where('m.school_id', '=', $schoolId);
            })->leftJoin('curricula as c', function ($join) use ($schoolId): void {
                $join->on('c.id', '=', 'm.curriculum_id')->where('c.school_id', '=', $schoolId);
            })->leftJoin('programmes as p', function ($join) use ($schoolId): void {
                $join->on('p.id', '=', 'c.programme_id')->where('p.school_id', '=', $schoolId);
            })->select([
                'course_registrations.*', 'users.name as student_name', 'users.code as registration_number',
                'p.name as programme_name', 'p.code as programme_code', 'c.version as curriculum_version',
                'sp.year_of_study',
            ])->orderBy('users.name')->get();
    }
}
