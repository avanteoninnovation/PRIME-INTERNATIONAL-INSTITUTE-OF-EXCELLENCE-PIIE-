@extends('admin.navigation')
@section('content')
<div class="mainSection-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div><div class="d-flex gap-2 flex-wrap mb-2"><a href="{{ route('admin.course_offerings.show', $offering->id) }}" class="btn btn-sm btn-outline-secondary">Offering Workspace</a><a href="{{ route('admin.course_offerings.eligible_students', $offering->id) }}" class="btn btn-sm btn-outline-secondary">Eligible Students</a><a href="{{ route('admin.course_offerings.registrations', $offering->id) }}" class="btn btn-sm btn-outline-secondary">Registered Students</a></div>
            <h4>{{ $mode === 'eligible' ? 'Eligible Students' : 'Registered Students' }}</h4>
            <p class="text-muted mb-0">{{ $offering->reference ?: 'Course Offering' }} · {{ $offering->subject?->code }} {{ $offering->subject?->name }} · {{ $offering->academicYear?->label }} · {{ $offering->academicPeriod?->label }}</p>
        </div><span class="badge bg-primary align-self-start">{{ ucfirst(str_replace('_', ' ', $offering->status)) }}</span>
    </div>
</div>
@if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="eSection-wrap">
    <p class="text-muted">@if($mode === 'eligible')Eligibility is determined from each student's Study Plan assignment for this Academic Year and this Offering's approved Curriculum applicability. Cohort membership alone does not qualify a student.@else Registration is the authoritative teaching group for this Offering. Withdrawals remain in the registration history.@endif</p>
    @if($students->isEmpty())<div class="alert alert-info mb-0">{{ $mode === 'eligible' ? 'No eligible students are currently available for registration.' : 'No students are registered for this Offering.' }}</div>
    @else
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Student</th><th>Registration number</th><th>Programme</th><th>Programme Study Plan</th><th>Year of Study</th>@if($mode === 'registered')<th>Status</th><th>Registration date</th>@endif @if($canManage)<th>Action</th>@endif</tr></thead><tbody>
    @foreach($students as $student)
        @php
            $isEligible = $mode === 'eligible';
            $profile = $isEligible ? $student->studentProfile : null;
            $assignment = $isEligible ? $student->roster_assignment : null;
            $curriculum = $isEligible ? $student->roster_curriculum : null;
        @endphp
        <tr><td>{{ $isEligible ? $student->name : $student->student_name }}</td><td>{{ $isEligible ? ($student->code ?: '—') : ($student->registration_number ?: '—') }}</td>
            <td>{{ $isEligible ? ($curriculum?->programme?->code.' — '.$curriculum?->programme?->name) : (($student->programme_code ? $student->programme_code.' — ' : '').($student->programme_name ?: '—')) }}</td>
            <td>{{ $isEligible ? (($curriculum?->version ? 'Programme Study Plan · Version '.$curriculum->version : '—')) : ($student->curriculum_version ? 'Programme Study Plan · Version '.$student->curriculum_version : '—') }}</td>
            <td>{{ $isEligible ? ($profile?->year_of_study ?: '—') : ($student->year_of_study ?: '—') }}</td>
            @if($mode === 'registered')<td><span class="badge bg-{{ $student->status === 'confirmed' ? 'success' : ($student->status === 'dropped' ? 'secondary' : 'warning text-dark') }}">{{ ucfirst($student->status) }}</span></td><td>{{ $student->created_at?->format('Y-m-d') ?: '—' }}</td>
                @if($canManage)<td>@if(in_array($student->status, ['registered','confirmed'], true))<form method="POST" action="{{ route('admin.course_offerings.registrations.drop', [$offering->id, $student->id]) }}" onsubmit="return confirm('Withdraw this student from the Course Offering? The registration will remain in history.')">@csrf<input class="form-control form-control-sm mb-1" name="reason" placeholder="Withdrawal reason" required maxlength="1000"><button class="btn btn-sm btn-outline-danger">Withdraw</button></form>@else — @endif</td>@endif
            @elseif($canManage)<td><form method="POST" action="{{ route('admin.course_offerings.registrations.store', $offering->id) }}" onsubmit="return confirm('Register this student for the Course Offering?')">@csrf<input type="hidden" name="student_id" value="{{ $student->id }}"><button class="btn btn-sm btn-primary">Register Student</button></form></td>@endif
        </tr>
    @endforeach
    </tbody></table></div>
    @endif
</section>
@endsection
