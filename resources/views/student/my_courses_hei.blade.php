<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <h4>{{ get_phrase('My Courses') }}</h4>
            <p class="text-muted mb-0">
                @if($year && $period)
                    {{ $year->label }} · {{ $period->label }}
                @else
                    {{ get_phrase('Higher Education course registration') }}
                @endif
            </p>
        </div>
    </div>
</div>

@if(session('message'))<div class="alert alert-success mt-3">{{ session('message') }}</div>@endif
@if(session('error'))<div class="alert alert-warning mt-3">{{ session('error') }}</div>@endif
@if($message)<div class="alert {{ in_array($state, ['integrity_review', 'configuration', 'programme_mismatch', 'assignment_missing'], true) ? 'alert-warning' : 'alert-info' }} mt-3">{{ $message }}</div>@endif

@if($year && $period)
    <div class="eSection-wrap mt-3 mb-4">
        <h5>{{ get_phrase('Available to Register') }}</h5>
        <p class="text-muted small">{{ get_phrase('Choose an Offering for each Course Unit. Parallel Offerings are shown separately.') }}</p>
        @if($offerings->isNotEmpty())
            <div class="row g-3">
                @foreach($offerings as $offering)
                    <div class="col-12 col-xl-6">
                        <article class="border rounded p-3 h-100 d-flex flex-column">
                            <div class="d-flex justify-content-between gap-3">
                                <div>
                                    <h6 class="mb-1">{{ $offering->subject_code ? $offering->subject_code . ': ' : '' }}{{ $offering->subject_name }}</h6>
                                    <div class="text-muted small">{{ $courseUnitLabel }} · {{ $offering->academic_year_label }} · {{ $offering->academic_period_label }}</div>
                                </div>
                                @if($offering->reference)<span class="badge bg-light text-dark border align-self-start">{{ $offering->reference }}</span>@endif
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3 small">
                                <span class="badge bg-light text-dark border">{{ number_format((float) $offering->membership_credits, 2) }} {{ get_phrase('credits') }}</span>
                                <span class="badge bg-light text-dark border">{{ ucfirst($offering->membership_classification) }}</span>
                            </div>
                            @if($offering->teaching_team->isNotEmpty())
                                <div class="small text-muted mt-2">
                                    @if($offering->primary_lecturer)
                                        {{ get_phrase('Primary Lecturer') }}: {{ $offering->primary_lecturer }}
                                    @endif
                                    @php($additionalLecturers = $offering->teaching_team->reject(fn ($member) => $member->role === 'primary_lecturer')->pluck('name'))
                                    @if($additionalLecturers->isNotEmpty())
                                        <span class="d-block">{{ get_phrase('Teaching team') }}: {{ $additionalLecturers->implode(', ') }}</span>
                                    @endif
                                </div>
                            @endif
                            <form method="POST" action="{{ route('student.my_courses.register') }}" class="mt-auto pt-3">
                                @csrf
                                <input type="hidden" name="course_offering_id" value="{{ $offering->offering_id }}">
                                <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Register for this Offering') }}</button>
                            </form>
                        </article>
                    </div>
                @endforeach
            </div>
        @elseif(!in_array($state, ['integrity_review', 'configuration', 'programme_mismatch', 'assignment_missing', 'curriculum_unavailable'], true))
            <p class="text-muted mb-0">{{ get_phrase('No Course Offering is currently available for this period.') }}</p>
        @endif
    </div>
@endif

<div class="eSection-wrap mb-4">
    <h5>{{ get_phrase('Registered — Pending Confirmation') }}</h5>
    @if($pending->isEmpty())
        <p class="text-muted mb-0">{{ get_phrase('No registrations are waiting for confirmation.') }}</p>
    @else
        <div class="table-responsive">
            <table class="table eTable align-middle">
                <thead><tr><th>{{ get_phrase('Course Unit') }}</th><th>{{ get_phrase('Offering / Period') }}</th><th>{{ get_phrase('Credits') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
                <tbody>
                @foreach($pending as $registration)
                    <tr>
                        <td>{{ $registration->subject_code ? $registration->subject_code . ': ' : '' }}{{ $registration->subject_name }}</td>
                        <td>{{ $registration->reference ?: '—' }} · {{ $registration->academic_year_label }} · {{ $registration->academic_period_label }}</td>
                        <td>{{ $registration->registered_credits ?? '—' }} · {{ $registration->registered_classification ? ucfirst($registration->registered_classification) : '—' }}</td>
                        <td><span class="badge bg-warning text-dark">{{ get_phrase('Pending confirmation') }}</span></td>
                        <td class="text-nowrap">
                            @if($finance_eligible)
                                <form method="POST" action="{{ route('student.my_courses.confirm', $registration->id) }}" class="d-inline">@csrf<button type="submit" class="eBtn eBtn-sm eBtn-success">{{ get_phrase('Confirm') }}</button></form>
                            @else
                                <span class="small text-muted">{{ get_phrase('Confirmation is unavailable until your outstanding balance is resolved.') }}</span>
                                <a href="{{ route('student.fee_manager.list') }}" class="small d-block">{{ get_phrase('View fee information') }}</a>
                            @endif
                            @if($registration->offering_status === 'open')
                                <form method="POST" action="{{ route('student.my_courses.drop', $registration->id) }}" class="d-inline" onsubmit="return confirm('{{ get_phrase('Drop this course?') }}')">@csrf<button type="submit" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Drop') }}</button></form>
                            @elseif($registration->offering_status === 'in_progress')
                                <span class="small text-muted d-block">{{ get_phrase('Withdrawal requires Academic Office assistance.') }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<div class="eSection-wrap mb-4">
    <h5>{{ get_phrase('Confirmed Courses') }}</h5>
    @if($confirmed->isEmpty())
        <p class="text-muted mb-0">{{ get_phrase('No confirmed courses for this academic history.') }}</p>
    @else
        <div class="table-responsive"><table class="table eTable align-middle">
            <thead><tr><th>{{ get_phrase('Course Unit') }}</th><th>{{ get_phrase('Offering / Period') }}</th><th>{{ get_phrase('Credits') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>@foreach($confirmed as $registration)<tr>
                <td>{{ $registration->subject_code ? $registration->subject_code . ': ' : '' }}{{ $registration->subject_name }}</td>
                <td>{{ $registration->reference ?: '—' }} · {{ $registration->academic_year_label }} · {{ $registration->academic_period_label }}</td>
                <td>{{ $registration->registered_credits ?? '—' }} · {{ $registration->registered_classification ? ucfirst($registration->registered_classification) : '—' }}</td>
                <td><span class="badge bg-success">{{ get_phrase('Confirmed') }}</span></td>
                <td>
                    @if($registration->offering_status === 'open')
                        <form method="POST" action="{{ route('student.my_courses.drop', $registration->id) }}" onsubmit="return confirm('{{ get_phrase('Drop this course?') }}')">@csrf<button type="submit" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Drop') }}</button></form>
                    @elseif($registration->offering_status === 'in_progress')
                        <span class="small text-muted">{{ get_phrase('Withdrawal requires Academic Office assistance.') }}</span>
                    @endif
                </td>
            </tr>@endforeach</tbody>
        </table></div>
    @endif
</div>

<div class="eSection-wrap">
    <h5>{{ get_phrase('Dropped / History') }}</h5>
    @if($history->isEmpty())
        <p class="text-muted mb-0">{{ get_phrase('No dropped Offering registrations.') }}</p>
    @else
        <div class="table-responsive"><table class="table eTable align-middle">
            <thead><tr><th>{{ get_phrase('Course Unit') }}</th><th>{{ get_phrase('Offering / Period') }}</th><th>{{ get_phrase('Credits') }}</th><th>{{ get_phrase('Status') }}</th></tr></thead>
            <tbody>@foreach($history as $registration)<tr>
                <td>{{ $registration->subject_code ? $registration->subject_code . ': ' : '' }}{{ $registration->subject_name }}</td>
                <td>{{ $registration->reference ?: '—' }} · {{ $registration->academic_year_label }} · {{ $registration->academic_period_label }}</td>
                <td>{{ $registration->registered_credits ?? '—' }} · {{ $registration->registered_classification ? ucfirst($registration->registered_classification) : '—' }}</td>
                <td><span class="badge bg-secondary">{{ get_phrase('Dropped') }}</span></td>
            </tr>@endforeach</tbody>
        </table></div>
    @endif
</div>
