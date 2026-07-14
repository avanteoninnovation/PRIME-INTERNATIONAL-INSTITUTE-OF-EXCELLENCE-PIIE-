@extends('teacher.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Online Exams') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Examination') }}</a></li>
                        <li><a href="#">{{ get_phrase('Online Exams') }}</a></li>
                    </ul>
                </div>
                <div class="export-btn-area d-flex gap-2">
                    @if($canManageQuestions)
                        <a href="{{ route('teacher.online_exams.question_bank') }}" class="export_btn bg-secondary">{{ get_phrase('Question Bank') }}</a>
                    @endif
                    @if($canMark)
                        <a href="{{ route('teacher.online_exams.marking') }}" class="export_btn bg-info">{{ get_phrase('Marking Queue') }}</a>
                    @endif
                    @if($canCreate)
                        <a href="{{ route('teacher.online_exams.create') }}" class="export_btn">{{ get_phrase('Create Exam') }}</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="eSection-wrap mb-3">
    <form method="GET" action="{{ route('teacher.online_exams.index') }}" class="row g-2">
        <div class="col-md-3"><input class="form-control eForm-control" type="text" name="title" value="{{ request('title') }}" placeholder="{{ get_phrase('Search by title') }}"></div>
        <div class="col-md-2">
            <select class="form-select eForm-select" name="subject_id">
                <option value="">{{ get_phrase('All Subjects') }}</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ (string) request('subject_id') === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select eForm-select" name="class_id">
                <option value="">{{ get_phrase('All Classes') }}</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select eForm-select" name="workflow_state">
                <option value="">{{ get_phrase('Workflow') }}</option>
                @foreach(['draft', 'pending_review', 'published', 'cancelled'] as $state)
                    <option value="{{ $state }}" {{ request('workflow_state') === $state ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $state)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select eForm-select" name="lifecycle_state">
                <option value="">{{ get_phrase('Lifecycle') }}</option>
                @foreach(['active', 'completed', 'cancelled'] as $state)
                    <option value="{{ $state }}" {{ request('lifecycle_state') === $state ? 'selected' : '' }}>{{ ucfirst($state) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-1"><button class="eBtn eBtn-primary" type="submit">{{ get_phrase('Filter') }}</button></div>

        <div class="col-md-2"><input class="form-control eForm-control" type="date" name="date_from" value="{{ request('date_from') }}"></div>
        <div class="col-md-2"><input class="form-control eForm-control" type="date" name="date_to" value="{{ request('date_to') }}"></div>
    </form>
</div>

<div class="mb-2 d-flex gap-2 flex-wrap">
    @foreach(['drafts' => 'Drafts', 'pending_review' => 'Pending Review', 'published' => 'Published', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
        <a href="{{ route('teacher.online_exams.index', array_merge(request()->except('page'), ['tab' => $key])) }}" class="badge {{ request('tab') === $key ? 'bg-primary' : 'bg-secondary' }}">{{ get_phrase($label) }}</a>
    @endforeach
</div>

<div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead>
            <tr>
                <th>#</th>
                <th>{{ get_phrase('Title') }}</th>
                <th>{{ get_phrase('Subject') }}</th>
                <th>{{ get_phrase('Class') }}</th>
                <th>{{ get_phrase('Start') }}</th>
                <th>{{ get_phrase('End') }}</th>
                <th>{{ get_phrase('Duration') }}</th>
                <th>{{ get_phrase('Questions') }}</th>
                <th>{{ get_phrase('Attempts') }}</th>
                <th>{{ get_phrase('Workflow') }}</th>
                <th>{{ get_phrase('Lifecycle') }}</th>
                <th>{{ get_phrase('Actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @forelse($exams as $index => $exam)
                <tr>
                    <td>{{ $exams->firstItem() + $index }}</td>
                    <td>{{ $exam->title }}</td>
                    <td>{{ optional($exam->subject)->name ?? '—' }}</td>
                    <td>{{ optional($exam->classRoom)->name ?? '—' }}</td>
                    <td>{{ optional($exam->start_datetime)->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ optional($exam->end_datetime)->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ $exam->duration_mins }}m</td>
                    <td>{{ $exam->questions_count }}</td>
                    <td>{{ $exam->submissions_count }}</td>
                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $exam->workflow_state ?? 'draft')) }}</span></td>
                    <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $exam->lifecycle_status)) }}</span></td>
                    <td class="d-flex gap-1 flex-wrap">
                        <a class="eBtn eBtn-sm eBtn-primary" href="{{ route('teacher.online_exams.show', $exam->id) }}">{{ get_phrase('View') }}</a>
                        <a class="eBtn eBtn-sm eBtn-secondary" href="{{ route('teacher.online_exams.edit', $exam->id) }}">{{ get_phrase('Edit') }}</a>
                        <a class="eBtn eBtn-sm eBtn-warning" href="{{ route('teacher.online_exams.questions.index', $exam->id) }}">{{ get_phrase('Questions') }}</a>
                        <a class="eBtn eBtn-sm eBtn-secondary" href="{{ route('teacher.online_exams.attempts', $exam->id) }}">{{ get_phrase('Attempts') }}</a>
                        <a class="eBtn eBtn-sm eBtn-secondary" href="{{ route('teacher.online_exams.results', $exam->id) }}">{{ get_phrase('Results') }}</a>

                        <form method="POST" action="{{ route('teacher.online_exams.submit_review', $exam->id) }}" class="d-inline">@csrf<button class="eBtn eBtn-sm eBtn-info" type="submit">{{ get_phrase('Submit Review') }}</button></form>
                        @if($canPublish)
                            <form method="POST" action="{{ route('teacher.online_exams.publish', $exam->id) }}" class="d-inline">@csrf<button class="eBtn eBtn-sm eBtn-success" type="submit">{{ get_phrase('Publish') }}</button></form>
                            <form method="POST" action="{{ route('teacher.online_exams.unpublish', $exam->id) }}" class="d-inline">@csrf<button class="eBtn eBtn-sm eBtn-warning" type="submit">{{ get_phrase('Unpublish') }}</button></form>
                        @endif
                        <form method="POST" action="{{ route('teacher.online_exams.cancel', $exam->id) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="reason" value="Cancelled by teacher">
                            <button class="eBtn eBtn-sm eBtn-danger" type="submit">{{ get_phrase('Cancel') }}</button>
                        </form>
                        <form method="POST" action="{{ route('teacher.online_exams.destroy', $exam->id) }}" class="d-inline" onsubmit="return confirm('{{ get_phrase('Delete exam?') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="eBtn eBtn-sm eBtn-danger" type="submit">{{ get_phrase('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="12" class="text-center text-muted">{{ get_phrase('No online exams found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $exams->links() }}
</div>
@endsection
