@extends('admin.navigation')

@section('content')
@php($academicPeriodLabel = app(\App\Support\TenantConfiguration::class)->terminology($school)['academic_period'] ?? 'Academic Period')
<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
            <div><h4>Academic Years &amp; Periods</h4><ul class="d-flex align-items-center eBreadcrumb-2"><li>Academic Setup</li></ul></div>
        </div>
    </div></div>
</div>

@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-5">
        <div class="eSection-wrap">
            <h5>Create Academic Year</h5>
            <form method="post" action="{{ route('admin.academic_structure.years.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">Year label</label><input class="form-control" name="label" value="{{ old('label') }}" required maxlength="100" placeholder="2026/2027"></div>
                <div class="mb-3"><label class="form-label">Start date</label><input class="form-control" type="date" name="start_date" value="{{ old('start_date') }}" required></div>
                <div class="mb-3"><label class="form-label">End date</label><input class="form-control" type="date" name="end_date" value="{{ old('end_date') }}" required></div>
                <p class="text-muted">New academic years are created as Planned. Activate a year explicitly before selecting it as current.</p>
                <button class="eBtn eBtn-blueish" type="submit">Create year</button>
            </form>
        </div>
        <div class="eSection-wrap mt-3">
            <h5>Current Academic Context</h5>
            <form method="post" action="{{ route('admin.academic_structure.current') }}">
                @csrf
                <div class="mb-3"><label class="form-label">Current year</label><select class="form-select" name="current_academic_year_id" id="current_year"><option value="">No current year</option>@foreach($years->where('status', 'active') as $year)<option value="{{ $year->id }}" @selected((int)$school->current_academic_year_id === (int)$year->id)>{{ $year->label }}</option>@endforeach</select></div>
                <div class="mb-3"><label class="form-label">Current period</label><select class="form-select" name="current_academic_period_id" id="current_period"><option value="">No current period (break)</option>@foreach($years as $year)@foreach($year->periods->where('status', 'active') as $period)<option data-year="{{ $year->id }}" value="{{ $period->id }}" @selected((int)$school->current_academic_period_id === (int)$period->id)>{{ $year->label }} — {{ $academicPeriodLabel }} {{ $period->sequence }}</option>@endforeach @endforeach</select></div>
                <button class="eBtn eBtn-primary" type="submit">Save current context</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="eSection-wrap">
            <h5>Create Academic Period</h5>
            <p class="text-muted">Configured calendar pattern: {{ $school->academic_calendar_pattern ?: 'not set (semester or term)' }}. Configure the institution’s actual dates; no dates are assumed.</p>
            <form method="post" action="{{ route('admin.academic_structure.periods.store') }}">
                @csrf
                <div class="mb-3"><label class="form-label">Academic year</label><select class="form-select" name="academic_year_id" required>@foreach($years as $year)<option value="{{ $year->id }}">{{ $year->label }} ({{ $year->start_date->format('Y-m-d') }} — {{ $year->end_date->format('Y-m-d') }})</option>@endforeach</select></div>
                <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Type</label><select class="form-select" name="type" required>@foreach(['semester','term'] as $type)<option value="{{ $type }}" @selected($school->academic_calendar_pattern === $type)>{{ ucfirst($type) }}</option>@endforeach</select></div><div class="col-md-6 mb-3"><label class="form-label">Sequence</label><input class="form-control" type="number" min="1" name="sequence" value="1" required></div></div>
                <div class="mb-3"><label class="form-label">Label</label><input class="form-control" name="label" required maxlength="100" placeholder="Semester 1 or Term 1"></div>
                <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Start date</label><input class="form-control" type="date" name="start_date" required></div><div class="col-md-6 mb-3"><label class="form-label">End date</label><input class="form-control" type="date" name="end_date" required></div></div>
                <p class="text-muted">New academic periods are created as Planned. Activate a period explicitly before selecting it as current.</p>
                <button class="eBtn eBtn-blueish" type="submit">Create period</button>
            </form>
        </div>
        <div class="eSection-wrap mt-3">
            <h5>Academic Years</h5>
            @forelse($years as $year)
                <div class="border rounded p-3 mb-3"><div class="d-flex justify-content-between"><strong>{{ $year->label }}</strong><span>{{ ucfirst($year->status) }}</span></div><div class="text-muted">{{ $year->start_date->format('Y-m-d') }} — {{ $year->end_date->format('Y-m-d') }}</div>
                    @if($year->status === 'planned')
                        <form method="post" action="{{ route('admin.academic_structure.years.status', $year->id) }}" class="d-inline-block mt-2">@csrf<input type="hidden" name="status" value="active"><button class="btn btn-sm btn-outline-primary" type="submit">Activate year</button></form>
                    @endif
                    @if($year->status === 'active' && ! $year->periods->contains(fn ($period) => in_array($period->status, ['planned', 'active'], true)))
                        <form method="post" action="{{ route('admin.academic_structure.years.status', $year->id) }}" class="d-inline-block mt-2">@csrf<input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-outline-success" type="submit">Complete year</button></form>
                    @endif
                    @if(in_array($year->status, ['planned', 'active'], true))
                        <form method="post" action="{{ route('admin.academic_structure.years.status', $year->id) }}" class="d-inline-block mt-2" onsubmit="return confirm('Cancel this academic year? Historical academic records will be preserved.');">@csrf<input type="hidden" name="status" value="cancelled"><input type="hidden" name="confirm_cancellation" value="1"><button class="btn btn-sm btn-outline-danger" type="submit">Cancel year</button></form>
                    @endif
                    @if($year->periods->isNotEmpty())<ul class="mb-0 mt-2">@foreach($year->periods as $period)<li>{{ $academicPeriodLabel }} {{ $period->sequence }} · {{ $period->start_date->format('Y-m-d') }} — {{ $period->end_date->format('Y-m-d') }} · {{ ucfirst($period->status) }}
                        @if(app(\App\Support\Permissions\PermissionService::class)->allows(auth()->user(), 'academic.course_offering.view'))<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.course_offerings.index', ['year_id' => $year->id, 'period_id' => $period->id]) }}">Course Offerings</a>@endif
                        @if($period->status === 'planned')
                            <form method="post" action="{{ route('admin.academic_structure.periods.status', $period->id) }}" class="d-inline-block">@csrf<input type="hidden" name="status" value="active"><button class="btn btn-sm btn-outline-primary" type="submit">Activate</button></form>
                        @elseif($period->status === 'active')
                            <form method="post" action="{{ route('admin.academic_structure.periods.status', $period->id) }}" class="d-inline-block">@csrf<input type="hidden" name="status" value="completed"><button class="btn btn-sm btn-outline-success" type="submit">Complete</button></form>
                        @endif
                        @if(in_array($period->status, ['planned', 'active'], true))
                            <form method="post" action="{{ route('admin.academic_structure.periods.status', $period->id) }}" class="d-inline-block" onsubmit="return confirm('Cancel this academic period? Historical academic records will be preserved.');">@csrf<input type="hidden" name="status" value="cancelled"><input type="hidden" name="confirm_cancellation" value="1"><button class="btn btn-sm btn-outline-danger" type="submit">Cancel</button></form>
                        @endif
                    </li>@endforeach</ul>@else<p class="mb-0 mt-2 text-muted">No periods configured.</p>@endif
                </div>
            @empty<p class="text-muted mb-0">No academic years configured yet.</p>@endforelse
        </div>
    </div>
</div>
<script>
document.getElementById('current_year').addEventListener('change', function () {
    const year = this.value;
    const periods = document.getElementById('current_period');
    Array.from(periods.options).forEach(option => {
        if (!option.dataset.year) return;
        option.hidden = year !== '' && option.dataset.year !== year;
        if (option.hidden && option.selected) periods.value = '';
    });
});
</script>
@endsection
