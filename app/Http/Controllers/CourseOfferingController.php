<?php

namespace App\Http\Controllers;

use App\Models\AcademicPeriod;
use App\Models\AcademicYear;
use App\Models\CourseOffering;
use App\Models\Curriculum;
use App\Models\CurriculumMembership;
use App\Models\LiveClass;
use App\Models\Subject;
use App\Models\CourseOfferingLecturerAllocation;
use App\Support\CourseRegistration\CourseOfferingRoster;
use App\Support\CourseRegistration\CourseRegistrationService;
use App\Support\CourseOffering\CourseOfferingService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class CourseOfferingController extends Controller
{
    public function __construct(private CourseOfferingService $offerings, private CourseOfferingRoster $roster, private CourseRegistrationService $registrations) {}

    public function index(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $query = CourseOffering::query()->where('course_offerings.school_id', $schoolId)
            ->join('subjects', fn ($j) => $j->on('subjects.id', '=', 'course_offerings.subject_id')->where('subjects.school_id', $schoolId))
            ->join('academic_years', fn ($j) => $j->on('academic_years.id', '=', 'course_offerings.academic_year_id')->where('academic_years.school_id', $schoolId))
            ->join('academic_periods', fn ($j) => $j->on('academic_periods.id', '=', 'course_offerings.academic_period_id')->where('academic_periods.school_id', $schoolId))
            ->select('course_offerings.*')
            ->when($request->filled('year_id'), fn ($q) => $q->where('course_offerings.academic_year_id', $request->integer('year_id')))
            ->when($request->filled('period_id'), fn ($q) => $q->where('course_offerings.academic_period_id', $request->integer('period_id')))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('course_offerings.subject_id', $request->integer('subject_id')))
            ->when($request->filled('status') && in_array($request->status, CourseOffering::STATUSES, true), fn ($q) => $q->where('course_offerings.status', $request->status))
            ->when($request->filled('reference'), fn ($q) => $q->where('course_offerings.reference', 'like', '%'.trim($request->reference).'%'))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($s) => $s->where('subjects.name', 'like', '%'.trim($request->search).'%')->orWhere('subjects.code', 'like', '%'.trim($request->search).'%')))
            ->when($request->filled('programme_id') || $request->filled('curriculum_id') || $request->filled('department_id'), function ($q) use ($request, $schoolId) {
                $q->whereExists(function ($sub) use ($request, $schoolId) {
                    $sub->selectRaw('1')->from('course_offering_curriculum_memberships as cofm')
                        ->join('curricula as c', fn ($j) => $j->on('c.id', '=', 'cofm.curriculum_id')->where('c.school_id', $schoolId))
                        ->join('programmes as p', fn ($j) => $j->on('p.id', '=', 'c.programme_id')->where('p.school_id', $schoolId))
                        ->whereColumn('cofm.course_offering_id', 'course_offerings.id')->where('cofm.school_id', $schoolId)
                        ->when($request->filled('programme_id'), fn ($x) => $x->where('c.programme_id', $request->integer('programme_id')))
                        ->when($request->filled('curriculum_id'), fn ($x) => $x->where('c.id', $request->integer('curriculum_id')))
                        ->when($request->filled('department_id'), fn ($x) => $x->where('p.department_id', $request->integer('department_id')));
                });
            })
            ->orderByDesc('course_offerings.updated_at');

        $offerings = $query->paginate(25)->withQueryString();
        $pageOfferingIds = $offerings->getCollection()->pluck('id');
        if ($pageOfferingIds->isNotEmpty()) {
            $pageOfferings = $offerings->getCollection();
            $subjectById = Subject::where('school_id', $schoolId)
                ->whereIn('id', $pageOfferings->pluck('subject_id')->unique())
                ->get()->keyBy('id');
            $yearById = AcademicYear::where('school_id', $schoolId)
                ->whereIn('id', $pageOfferings->pluck('academic_year_id')->unique())
                ->get()->keyBy('id');
            $periodById = AcademicPeriod::where('school_id', $schoolId)
                ->whereIn('id', $pageOfferings->pluck('academic_period_id')->unique())
                ->get()->keyBy('id');

            foreach ($pageOfferings as $offering) {
                $offering->setRelation('subject', $subjectById->get($offering->subject_id));
                $offering->setRelation('academicYear', $yearById->get($offering->academic_year_id));
                $offering->setRelation('academicPeriod', $periodById->get($offering->academic_period_id));
            }

            $applicabilityByOffering = DB::table('course_offering_curriculum_memberships as x')
                ->join('curricula as c', fn ($j) => $j->on('c.id', '=', 'x.curriculum_id')->where('c.school_id', $schoolId))
                ->join('programmes as p', fn ($j) => $j->on('p.id', '=', 'c.programme_id')->where('p.school_id', $schoolId))
                ->where('x.school_id', $schoolId)
                ->whereIn('x.course_offering_id', $pageOfferingIds)
                ->orderBy('p.name')->orderBy('c.version')
                ->get(['x.course_offering_id', 'p.code as programme_code', 'c.version as curriculum_version'])
                ->groupBy('course_offering_id');

            foreach ($pageOfferings as $offering) {
                $summary = $applicabilityByOffering->get($offering->id, collect())
                    ->map(fn ($context) => $context->programme_code.' / Study Plan '.$context->curriculum_version)
                    ->unique()->implode(', ');
                $offering->setAttribute('applicability_summary', $summary);
            }
        }
        $years = AcademicYear::where('school_id', $schoolId)->orderByDesc('start_date')->get();
        $periods = AcademicPeriod::where('school_id', $schoolId)->when($request->filled('year_id'), fn ($q) => $q->where('academic_year_id', $request->integer('year_id')))->orderBy('sequence')->get();
        $courseUnits = Subject::where('school_id', $schoolId)->orderBy('code')->orderBy('name')->get();
        $programmes = DB::table('programmes')->where('school_id', $schoolId)->orderBy('name')->get();
        $curricula = Curriculum::where('school_id', $schoolId)->with('programme')->orderBy('version')->get();
        $departments = DB::table('departments')->where('school_id', $schoolId)->orderBy('name')->get();
        $tenantConfiguration = app(\App\Support\TenantConfiguration::class);
        $tenant = \App\Models\School::query()->find($schoolId);
        $tenantDisplay = $tenantConfiguration->resolve($tenant, $request->user());
        $courseUnitLabel = $tenantDisplay['terminology_profile']['course_unit'] ?? 'Course Unit';
        $calendarPattern = $tenantDisplay['academic_calendar_pattern'] ?? null;
        $hasSemesterPeriod = $periods->contains(fn ($period) => $period->type === 'semester');
        $periodLabel = match (true) {
            ($tenant->school_type ?? 'k12') === 'higher_ed' && ($calendarPattern === 'semester' || $hasSemesterPeriod) => 'Semester',
            $calendarPattern === 'term' => 'Term',
            default => 'Academic Period',
        };
        $hasAnyOfferings = CourseOffering::query()->where('school_id', $schoolId)->exists();

        return view('admin.course_offerings.index', compact('offerings', 'years', 'periods', 'courseUnits', 'programmes', 'curricula', 'departments', 'courseUnitLabel', 'periodLabel', 'hasAnyOfferings'));
    }

    public function create(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $subjects = Subject::where('school_id', $schoolId)->orderBy('name')->get();
        $years = AcademicYear::where('school_id', $schoolId)->orderByDesc('start_date')->get();
        $periods = AcademicPeriod::where('school_id', $schoolId)->when($request->filled('year_id'), fn ($q) => $q->where('academic_year_id', $request->integer('year_id')))->orderBy('sequence')->get();
        $curricula = Curriculum::where('school_id', $schoolId)->where('status', 'approved')->with('programme')->orderBy('version')->get();
        $tenantConfiguration = app(\App\Support\TenantConfiguration::class);
        $tenant = \App\Models\School::query()->find($schoolId);
        $tenantDisplay = $tenantConfiguration->resolve($tenant, $request->user());
        $courseUnitLabel = $tenantDisplay['terminology_profile']['course_unit'] ?? 'Course Unit';
        $calendarPattern = $tenantDisplay['academic_calendar_pattern'] ?? null;
        $hasSemesterPeriod = $periods->contains(fn ($period) => $period->type === 'semester');
        $periodLabel = match (true) {
            ($tenant->school_type ?? 'k12') === 'higher_ed' && ($calendarPattern === 'semester' || $hasSemesterPeriod) => 'Semester',
            $calendarPattern === 'term' => 'Term',
            default => 'Academic Period',
        };
        return view('admin.course_offerings.create', compact('subjects', 'years', 'periods', 'curricula', 'courseUnitLabel', 'periodLabel'));
    }

    public function store(Request $request)
    {
        $schoolId = (int) $request->user()->school_id;
        $data = $request->validate(['subject_id' => ['required','integer', Rule::exists('subjects','id')->where('school_id',$schoolId)], 'academic_year_id' => ['required','integer', Rule::exists('academic_years','id')->where('school_id',$schoolId)], 'academic_period_id' => ['required','integer', Rule::exists('academic_periods','id')->where('school_id',$schoolId)->where('academic_year_id',$request->input('academic_year_id'))]]);
        try { $offering = $this->offerings->createDraft($schoolId, (int)$data['subject_id'], (int)$data['academic_year_id'], (int)$data['academic_period_id']); }
        catch (Throwable $e) { return back()->withInput()->withErrors(['offering' => $e->getMessage()]); }
        return redirect()->route('admin.course_offerings.show', $offering->id)->with('success', 'Draft Offering created.');
    }

    public function show(Request $request, int $id)
    {
        $schoolId = (int)$request->user()->school_id;
        $offering = CourseOffering::where('school_id', $schoolId)->findOrFail($id);
        $offering->setRelation('subject', Subject::where('school_id', $schoolId)->whereKey($offering->subject_id)->firstOrFail());
        $offering->setRelation('academicYear', AcademicYear::where('school_id', $schoolId)->whereKey($offering->academic_year_id)->firstOrFail());
        $offering->setRelation('academicPeriod', AcademicPeriod::where('school_id', $schoolId)
            ->where('academic_year_id', $offering->academic_year_id)
            ->whereKey($offering->academic_period_id)
            ->firstOrFail());
        $links = DB::table('course_offering_curriculum_memberships as x')->join('curricula as c', fn ($j) => $j->on('c.id','=','x.curriculum_id')->where('c.school_id',$schoolId))->join('programmes as p', fn ($j) => $j->on('p.id','=','c.programme_id')->where('p.school_id',$schoolId))->join('curriculum_memberships as m', fn ($j) => $j->on('m.id','=','x.curriculum_membership_id')->where('m.school_id',$schoolId))->leftJoin('curriculum_stages as st', fn ($j) => $j->on('st.id','=','m.curriculum_stage_id')->where('st.school_id',$schoolId))->where('x.school_id',$schoolId)->where('x.course_offering_id',$id)->select('x.curriculum_membership_id','c.id as curriculum_id','c.version','p.code as programme_code','p.name as programme_name','m.period_type','m.period_sequence','m.classification','st.label as stage_label')->orderBy('p.name')->get();
        $candidates = collect();
        if ($offering->status === 'draft') {
            $candidates = CurriculumMembership::query()->where('curriculum_memberships.school_id',$schoolId)->where('curriculum_memberships.subject_id',$offering->subject_id)->whereNotIn('curriculum_memberships.id',$links->pluck('curriculum_membership_id'))
                ->join('curricula as c', fn ($j) => $j->on('c.id','=','curriculum_memberships.curriculum_id')->where('c.school_id',$schoolId)->where('c.status','approved'))
                ->join('programmes as p', fn ($j) => $j->on('p.id','=','c.programme_id')->where('p.school_id',$schoolId))
                ->join('academic_years as oy', fn ($j) => $j->where('oy.id', $offering->academic_year_id)->where('oy.school_id', $schoolId))
                ->join('academic_periods as op', fn ($j) => $j->where('op.id', $offering->academic_period_id)->where('op.school_id', $schoolId)->where('op.academic_year_id', $offering->academic_year_id))
                ->leftJoin('academic_years as ey', fn ($j) => $j->on('ey.id','=','c.effective_academic_year_id')->where('ey.school_id',$schoolId))
                ->where(fn ($q) => $q->whereNull('c.effective_academic_year_id')->orWhereColumn('oy.start_date','>=','ey.start_date'))
                ->whereColumn('curriculum_memberships.period_type','op.type')->whereColumn('curriculum_memberships.period_sequence','op.sequence')
                ->select('curriculum_memberships.id','curriculum_memberships.period_type','curriculum_memberships.period_sequence','c.version','p.code as programme_code','p.name as programme_name')->orderBy('p.name')->limit(100)->get();
        }
        $subjects = Subject::where('school_id',$schoolId)->orderBy('name')->get();
        $years = AcademicYear::where('school_id',$schoolId)->orderByDesc('start_date')->get();
        $periods = AcademicPeriod::where('school_id',$schoolId)->where('academic_year_id',$offering->academic_year_id)->orderBy('sequence')->get();
        $audit = DB::table('audit_logs')->where('school_id',$schoolId)->where('record_type',CourseOffering::class)->where('record_id',$id)->orderByDesc('id')->limit(50)->get();
        $liveClassAccess = app(\App\Support\LiveClasses\LiveClassAccessService::class);
        $canCreateLiveClass = $liveClassAccess->canAdminCreateForOffering($request->user(), $offering);
        $liveClasses = LiveClass::query()
            ->where('school_id', $schoolId)
            ->where('course_offering_id', $offering->id)
            ->with(['teacher:id,name'])
            ->withCount(['materials as resource_count' => fn ($query) => $query
                ->where('school_id', $schoolId)->where('category', 'resource')])
            ->orderByRaw("CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END")
            ->orderBy('scheduled_at')
            ->get();

        $routePrefix = (int) $request->user()->role_id === 3 ? 'teacher' : 'admin';
        $liveClasses = $liveClasses->map(function (LiveClass $class) use ($liveClassAccess, $request, $routePrefix) {
            $user = $request->user();
            $canManage = $routePrefix === 'admin'
                ? $liveClassAccess->canTenantAdminManage($user, $class)
                : $liveClassAccess->canLecturerManage($user, $class);
            $canJoin = $routePrefix === 'admin'
                ? $liveClassAccess->canTenantAdminJoin($user, $class)
                : $liveClassAccess->canLecturerJoin($user, $class);
            $canHost = $routePrefix === 'admin'
                ? $canJoin
                : $liveClassAccess->canLecturerHost($user, $class);
            $canView = $routePrefix === 'admin'
                ? $liveClassAccess->canTenantAdmin($user, $class, 'live_classes.view')
                : $liveClassAccess->canLecturerView($user, $class);
            $class->setAttribute('workspace_actions', [
                'manage' => $canManage,
                'join' => $canJoin,
                'host' => $canHost,
                'view' => $canView,
                'starting_soon' => $class->computed_status === LiveClass::STATUS_SCHEDULED
                    && $liveClassAccess->withinJoinWindow($class),
                'route_prefix' => $routePrefix,
            ]);

            return $class;
        });

        $activeTeachingTeam = CourseOfferingLecturerAllocation::query()->where('school_id', $schoolId)
            ->where('course_offering_id', $id)->where('status', 'active')->whereDate('starts_on', '<=', now()->toDateString())
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()->toDateString()))
            ->with(['lecturer' => fn ($q) => $q->where('school_id', $schoolId)])->orderByRaw("CASE WHEN role = 'primary_lecturer' THEN 0 ELSE 1 END")->get();
        $hasPlannedLecturers = CourseOfferingLecturerAllocation::query()->where('school_id', $schoolId)->where('course_offering_id', $id)->where('status', 'planned')->exists();
        $roleLabels = [
            'primary_lecturer' => 'Primary Lecturer', 'co_lecturer' => 'Co-Lecturer',
            'teaching_assistant' => 'Teaching Assistant', 'lab_instructor' => 'Lab Instructor', 'guest_lecturer' => 'Guest Lecturer',
        ];
        $canViewRegistrations = app(\App\Support\Permissions\PermissionService::class)->allows($request->user(), 'academic.course_registration.view');
        $registeredStudents = $canViewRegistrations ? $this->roster->registered($offering) : collect();
        $eligibleStudents = $canViewRegistrations ? $this->roster->eligible($offering) : collect();
        $registeredStudentCount = $registeredStudents->whereIn('status', ['registered', 'confirmed'])->count();

        $liveClassGroups = [
            'Upcoming' => $liveClasses->filter(fn ($session) => in_array($session->computed_status, [LiveClass::STATUS_DRAFT, LiveClass::STATUS_SCHEDULED], true)),
            'Live Now' => $liveClasses->filter(fn ($session) => $session->computed_status === LiveClass::STATUS_LIVE),
            'Past' => $liveClasses->filter(fn ($session) => $session->computed_status === LiveClass::STATUS_ENDED),
            'Cancelled' => $liveClasses->filter(fn ($session) => $session->computed_status === LiveClass::STATUS_CANCELLED),
        ];

        $courseUnitLabel = app(\App\Support\TenantConfiguration::class)->terminology($request->user()->school)['course_unit'] ?? 'Course Unit';
        $periodLabel = $offering->academicPeriod->type === 'semester' && ($request->user()->school->school_type ?? '') !== 'k12' ? 'Semester' : 'Academic Period';
        $nextStep = match (true) {
            $offering->status === 'draft' && $links->isEmpty() && $request->user()->hasPermission('academic.course_offering.manage') => 'Link a compatible Programme Study Plan before opening this Course Offering.',
            $offering->status === 'draft' && $links->isEmpty() => 'A Programme Study Plan must be linked before this Course Offering can be opened. Ask an authorized administrator to link one.',
            $offering->status === 'draft' => 'Review the Course Offering and applicable Programme Study Plans before opening it.',
            in_array($offering->status, ['completed', 'cancelled'], true) => 'This Course Offering is closed; its teaching and registration history is retained.',
            $activeTeachingTeam->isEmpty() && $hasPlannedLecturers => 'Review and activate the planned lecturer allocations for this Course Offering.',
            $activeTeachingTeam->isEmpty() => 'Assign the teaching team for this Course Offering.',
            $registeredStudents->isEmpty() && $canViewRegistrations => 'Review eligible students and begin Course Registration.',
            default => 'Course Offering ready for teaching.',
        };
        return view('admin.course_offerings.show', compact('offering','links','candidates','subjects','years','periods','audit','canCreateLiveClass','liveClasses','liveClassGroups','courseUnitLabel','activeTeachingTeam','hasPlannedLecturers','registeredStudents','registeredStudentCount','eligibleStudents','canViewRegistrations','periodLabel','nextStep','roleLabels'));
    }

    public function eligibleStudents(Request $request, int $id)
    {
        $offering = $this->studentWorkspaceOffering((int) $request->user()->school_id, $id);
        return view('admin.course_offerings.students', ['offering' => $offering, 'students' => $this->roster->eligible($offering), 'mode' => 'eligible', 'canManage' => app(\App\Support\Permissions\PermissionService::class)->allows($request->user(), 'academic.course_registration.manage')]);
    }

    public function registeredStudents(Request $request, int $id)
    {
        $offering = $this->studentWorkspaceOffering((int) $request->user()->school_id, $id);
        return view('admin.course_offerings.students', ['offering' => $offering, 'students' => $this->roster->registered($offering), 'mode' => 'registered', 'canManage' => app(\App\Support\Permissions\PermissionService::class)->allows($request->user(), 'academic.course_registration.manage')]);
    }

    public function registerStudent(Request $request, int $id)
    {
        $schoolId = (int) $request->user()->school_id;
        $offering = $this->tenantOffering($schoolId, $id);
        $data = $request->validate(['student_id' => ['required', 'integer', 'min:1']]);
        $student = $this->roster->eligible($offering)->firstWhere('id', (int) $data['student_id']);
        abort_unless($student, 422, 'This student is not currently eligible for this Course Offering.');
        try {
            $this->registrations->registerStudentForOffering($schoolId, (int) $student->id, $id, (int) $student->roster_membership_id, (int) $request->user()->id);
        } catch (\DomainException $exception) {
            return back()->withErrors(['registration' => $exception->getMessage()]);
        }
        return redirect()->route('admin.course_offerings.registrations', $id)->with('success', 'Student registered for this Course Offering.');
    }

    public function dropStudent(Request $request, int $id, int $registration)
    {
        $schoolId = (int) $request->user()->school_id;
        $record = \App\Models\CourseRegistration::query()->where('school_id', $schoolId)->where('course_offering_id', $id)->whereKey($registration)->firstOrFail();
        $data = $request->validate(['reason' => ['required', 'string', 'min:1', 'max:1000']]);
        try {
            $this->registrations->dropRegistration($schoolId, (int) $record->id, (int) $request->user()->id, trim($data['reason']));
        } catch (\DomainException $exception) {
            return back()->withErrors(['registration' => $exception->getMessage()]);
        }
        return back()->with('success', 'Registration withdrawn; the history has been retained.');
    }

    public function update(Request $request, int $id)
    {
        $schoolId=(int)$request->user()->school_id; $this->tenantOffering($schoolId,$id);
        $data=$request->validate(['subject_id'=>['required','integer',Rule::exists('subjects','id')->where('school_id',$schoolId)],'academic_year_id'=>['required','integer',Rule::exists('academic_years','id')->where('school_id',$schoolId)],'academic_period_id'=>['required','integer',Rule::exists('academic_periods','id')->where('school_id',$schoolId)->where('academic_year_id',$request->input('academic_year_id'))],'reference'=>['nullable','string','max:50']]);
        try {$this->offerings->updateDraft($schoolId,$id,['subject_id'=>(int)$data['subject_id'],'academic_year_id'=>(int)$data['academic_year_id'],'academic_period_id'=>(int)$data['academic_period_id'],'reference'=>$data['reference']??null]);} catch(Throwable $e){return back()->withInput()->withErrors(['offering'=>$e->getMessage()]);}
        return back()->with('success','Draft Offering updated.');
    }

    public function addApplicability(Request $request,int $id){$schoolId=(int)$request->user()->school_id;$this->tenantOffering($schoolId,$id);$d=$request->validate(['curriculum_membership_id'=>['required','integer',Rule::exists('curriculum_memberships','id')->where('school_id',$schoolId)]]);try{$this->offerings->addApplicability($schoolId,$id,(int)$d['curriculum_membership_id']);}catch(Throwable $e){return back()->withErrors(['applicability'=>$e->getMessage()]);}return back()->with('success','Curriculum applicability added.');}
    public function removeApplicability(Request $request,int $id,int $membershipId){$schoolId=(int)$request->user()->school_id;$this->tenantOffering($schoolId,$id);try{$this->offerings->removeApplicability($schoolId,$id,$membershipId);}catch(Throwable $e){return back()->withErrors(['applicability'=>$e->getMessage()]);}return back()->with('success','Curriculum applicability removed.');}

    public function lifecycle(Request $request,int $id,string $action)
    {
        $schoolId=(int)$request->user()->school_id;$this->tenantOffering($schoolId,$id);
        if($action==='cancel')$data=$request->validate(['reason'=>['required','string','min:1','max:1000']]);
        try { $this->offerings->{$action}($schoolId,$id,...($action==='cancel'?[trim($data['reason'])]:[])); }
        catch(Throwable $e){return back()->withErrors(['lifecycle'=>$e->getMessage()]);}
        return back()->with('success','Offering lifecycle updated.');
    }

    private function tenantOffering(int $schoolId,int $id): CourseOffering{return CourseOffering::where('school_id',$schoolId)->findOrFail($id);}

    private function studentWorkspaceOffering(int $schoolId, int $id): CourseOffering
    {
        $offering = $this->tenantOffering($schoolId, $id);
        $offering->setRelation('subject', Subject::where('school_id', $schoolId)->whereKey($offering->subject_id)->firstOrFail());
        $offering->setRelation('academicYear', AcademicYear::where('school_id', $schoolId)->whereKey($offering->academic_year_id)->firstOrFail());
        $offering->setRelation('academicPeriod', AcademicPeriod::where('school_id', $schoolId)->where('academic_year_id', $offering->academic_year_id)->whereKey($offering->academic_period_id)->firstOrFail());
        return $offering;
    }
}
