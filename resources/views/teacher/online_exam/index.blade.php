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
                    <a href="{{ route('teacher.online_exams.live_monitor') }}" class="export_btn bg-success">{{ get_phrase('Live Monitor') }}</a>
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

@php
    $statCards = [
        'drafts' => ['label' => 'Draft', 'value' => $stats['draft'], 'color' => '#797c8b'],
        'pending_review' => ['label' => 'Pending Review', 'value' => $stats['pending_review'], 'color' => '#f5a623'],
        'upcoming' => ['label' => 'Upcoming', 'value' => $stats['published'], 'color' => '#3a86ff'],
        'active' => ['label' => 'Ongoing', 'value' => $stats['active'], 'color' => '#1fa971'],
        'completed' => ['label' => 'Completed', 'value' => $stats['completed'], 'color' => '#181c32'],
        'cancelled' => ['label' => 'Cancelled', 'value' => $stats['cancelled'], 'color' => '#e5484d'],
    ];
@endphp
<div class="row g-3 mb-3 online-exam-stat-row">
    @foreach($statCards as $key => $card)
        <div class="col-6 col-md-4 col-lg-2">
            <a href="{{ route('teacher.online_exams.index', ['tab' => $key]) }}"
               class="online-exam-stat-card d-block text-decoration-none {{ request('tab') === $key ? 'is-active' : '' }}"
               style="--stat-color: {{ $card['color'] }};">
                <span class="online-exam-stat-value">{{ $card['value'] }}</span>
                <span class="online-exam-stat-label">{{ get_phrase($card['label']) }}</span>
            </a>
        </div>
    @endforeach
</div>

<div class="eSection-wrap mb-3">
    <div class="title mb-3 pb-0 border-0">
        <h3 class="mb-0">{{ get_phrase('Search & Filter') }}</h3>
    </div>
    <form method="GET" action="{{ route('teacher.online_exams.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="eForm-label">{{ get_phrase('Title') }}</label>
            <input class="form-control eForm-control" type="text" name="title" value="{{ request('title') }}" placeholder="{{ get_phrase('Search by title') }}">
        </div>
        <div class="col-md-2">
            <label class="eForm-label">{{ get_phrase('Subject') }}</label>
            <select class="form-select eForm-select" name="subject_id">
                <option value="">{{ get_phrase('All Subjects') }}</option>
                @foreach($subjects as $subject)
                    <option value="{{ $subject->id }}" {{ (string) request('subject_id') === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="eForm-label">{{ get_phrase('Class') }}</label>
            <select class="form-select eForm-select" name="class_id">
                <option value="">{{ get_phrase('All Classes') }}</option>
                @foreach($classes as $class)
                    <option value="{{ $class->id }}" {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="eForm-label">{{ get_phrase('Workflow') }}</label>
            <select class="form-select eForm-select" name="workflow_state">
                <option value="">{{ get_phrase('Any') }}</option>
                @foreach(['draft', 'pending_review', 'published', 'cancelled'] as $state)
                    <option value="{{ $state }}" {{ request('workflow_state') === $state ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $state)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="eForm-label">{{ get_phrase('From') }}</label>
            <input class="form-control eForm-control" type="date" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="col-md-1 d-grid">
            <button class="eBtn eBtn-primary" type="submit">{{ get_phrase('Filter') }}</button>
        </div>
    </form>
</div>

<div class="eSection-wrap">
    <div class="title mb-3 pb-0 border-0 d-flex justify-content-between align-items-center flex-wrap gr-15">
        <h3 class="mb-0">{{ get_phrase('Exam List') }}</h3>
        <div class="online-exam-tabs d-flex gap-2 flex-wrap">
            @foreach(['' => 'All', 'drafts' => 'Drafts', 'pending_review' => 'Pending Review', 'upcoming' => 'Upcoming', 'active' => 'Active', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                <a href="{{ route('teacher.online_exams.index', array_merge(request()->except(['page', 'tab']), $key !== '' ? ['tab' => $key] : [])) }}"
                   class="online-exam-tab-pill {{ (string) request('tab', '') === (string) $key ? 'is-active' : '' }}">{{ get_phrase($label) }}</a>
            @endforeach
        </div>
    </div>

    <div class="table-responsive">
        <table class="table eTable">
            <thead>
            <tr>
                <th>#</th>
                <th>{{ get_phrase('Title') }}</th>
                <th>{{ get_phrase('Subject / Class') }}</th>
                <th>{{ get_phrase('Start') }}</th>
                <th>{{ get_phrase('End') }}</th>
                <th>{{ get_phrase('Duration') }}</th>
                <th>{{ get_phrase('Questions') }}</th>
                <th>{{ get_phrase('Attempts') }}</th>
                <th>{{ get_phrase('Workflow') }}</th>
                <th>{{ get_phrase('Lifecycle') }}</th>
                <th class="text-end">{{ get_phrase('Actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @php
                $workflowColors = [
                    'draft' => 'secondary',
                    'pending_review' => 'warning',
                    'published' => 'success',
                    'cancelled' => 'danger',
                ];
                $lifecycleColors = [
                    'draft' => 'secondary',
                    'pending_review' => 'warning',
                    'published' => 'info',
                    'active' => 'success',
                    'ended' => 'dark',
                    'cancelled' => 'danger',
                ];
            @endphp
            @forelse($exams as $index => $exam)
                @php
                    $workflowState = $exam->workflow_state ?? 'draft';
                    $lifecycleState = $exam->lifecycle_status;
                @endphp
                <tr>
                    <td>{{ $exams->firstItem() + $index }}</td>
                    <td class="fw-semibold">{{ $exam->title }}</td>
                    <td>
                        <div>{{ optional($exam->subject)->name ?? '—' }}</div>
                        <div class="text-muted small">{{ optional($exam->classRoom)->name ?? get_phrase('All classes') }}</div>
                    </td>
                    <td>{{ optional($exam->start_datetime)->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ optional($exam->end_datetime)->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ $exam->duration_mins }}m</td>
                    <td>{{ $exam->questions_count }}</td>
                    <td>{{ $exam->submissions_count }}</td>
                    <td><span class="badge bg-{{ $workflowColors[$workflowState] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $workflowState)) }}</span></td>
                    <td><span class="badge bg-{{ $lifecycleColors[$lifecycleState] ?? 'info' }}">{{ ucfirst(str_replace('_', ' ', $lifecycleState)) }}</span></td>
                    <td class="text-end">
                        <div class="adminTable-action d-inline-block">
                            <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2 online-exam-action-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                {{ get_phrase('Actions') }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
                                <li><a class="dropdown-item" href="{{ route('teacher.online_exams.show', $exam->id) }}">{{ get_phrase('View') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('teacher.online_exams.edit', $exam->id) }}">{{ get_phrase('Edit') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('teacher.online_exams.questions.index', $exam->id) }}">{{ get_phrase('Questions') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('teacher.online_exams.attempts', $exam->id) }}">{{ get_phrase('Attempts') }}</a></li>
                                <li><a class="dropdown-item" href="{{ route('teacher.online_exams.results', $exam->id) }}">{{ get_phrase('Results') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('teacher.online_exams.submit_review', $exam->id) }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">{{ get_phrase('Submit Review') }}</button>
                                    </form>
                                </li>
                                @if($canPublish)
                                    <li>
                                        <form method="POST" action="{{ route('teacher.online_exams.publish', $exam->id) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">{{ get_phrase('Publish') }}</button>
                                        </form>
                                    </li>
                                    <li>
                                        <form method="POST" action="{{ route('teacher.online_exams.unpublish', $exam->id) }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">{{ get_phrase('Unpublish') }}</button>
                                        </form>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('teacher.online_exams.cancel', $exam->id) }}" onsubmit="return confirm('{{ get_phrase('Cancel this exam?') }}')">
                                        @csrf
                                        <input type="hidden" name="reason" value="Cancelled by teacher">
                                        <button type="submit" class="dropdown-item text-danger">{{ get_phrase('Cancel') }}</button>
                                    </form>
                                </li>
                                <li>
                                    <form method="POST" action="{{ route('teacher.online_exams.destroy', $exam->id) }}" onsubmit="return confirm('{{ get_phrase('Delete exam?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">{{ get_phrase('Delete') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="11" class="text-center text-muted py-4">{{ get_phrase('No online exams found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $exams->links() }}
</div>

<style>
    .online-exam-stat-card {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: 16px 18px;
        background: #fff;
        border-radius: 10px;
        border-left: 4px solid var(--stat-color, #cacfd4);
        box-shadow: 0 6px 20px rgba(121, 124, 139, 0.0156862745);
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .online-exam-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(121, 124, 139, 0.12);
    }
    .online-exam-stat-card.is-active {
        background: var(--stat-color, #181c32);
    }
    .online-exam-stat-card.is-active .online-exam-stat-value,
    .online-exam-stat-card.is-active .online-exam-stat-label {
        color: #fff;
    }
    .online-exam-stat-value {
        font-size: 26px;
        font-weight: 700;
        line-height: 1.1;
        color: #181c32;
    }
    .online-exam-stat-label {
        font-size: 12px;
        color: #797c8b;
        text-transform: uppercase;
        letter-spacing: .03em;
    }
    .online-exam-tab-pill {
        display: inline-block;
        padding: 5px 14px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        background: #f1f2f6;
        color: #4b4f5c;
        text-decoration: none;
    }
    .online-exam-tab-pill:hover {
        background: #e3e5eb;
        color: #181c32;
    }
    .online-exam-tab-pill.is-active {
        background: #3a86ff;
        color: #fff;
    }
</style>

<script>
    /*
     * Bootstrap's .table-responsive sets overflow-x: auto, and per the CSS
     * overflow spec that forces overflow-y to compute as "auto" too (you
     * can't have one axis visible and the other not) — so any dropdown menu
     * that opens near the bottom of the scroll area gets silently clipped
     * instead of showing. Re-initialising these specific dropdowns with a
     * "fixed" Popper strategy detaches the menu from that clipping ancestor
     * so it always renders in full, regardless of where the row sits in the
     * table.
     */
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.online-exam-action-toggle').forEach(function (toggle) {
            new bootstrap.Dropdown(toggle, { popperConfig: { strategy: 'fixed' } });
        });
    });
</script>
@endsection
