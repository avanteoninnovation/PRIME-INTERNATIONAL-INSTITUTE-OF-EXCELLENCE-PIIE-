<div class="mainSection-title">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <ul class="d-flex align-items-center eBreadcrumb-2 mb-2">
                <li><a href="{{ route('admin.course_offerings.index') }}">{{ get_phrase('Course Offerings') }}</a></li>
                <li><a href="{{ route('admin.course_offerings.show', $offering->id) }}">{{ get_phrase('Offering Workspace') }}</a></li>
                <li>{{ get_phrase('Lecturers') }}</li>
            </ul>
            <h4>{{ get_phrase('Teaching Team') }} · {{ $offering->reference ?: '#'.$offering->id }}</h4>
            <p class="mb-2">
                <strong>{{ $offering->subject->code ? $offering->subject->code.' — ' : '' }}{{ $offering->subject->name }}</strong>
                <span class="text-muted"> · {{ $offering->academicYear->label }} · {{ $offering->academicPeriod->label }}</span>
            </p>
            <span class="badge bg-primary">{{ get_phrase(ucfirst(str_replace('_', ' ', $offering->status))) }}</span>
            <span class="text-muted small ms-2">{{ get_phrase('Offering Reference') }}: {{ $offering->reference ?: '—' }}</span>
        </div>
        <a href="{{ route('admin.course_offerings.show', $offering->id) }}" class="btn btn-outline-secondary">{{ get_phrase('Back to Course Offering') }}</a>
    </div>
</div>
