@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ $election->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="{{ route('admin.elections.index') }}">{{ get_phrase('Elections') }}</a></li>
                <li><a href="#">{{ $election->title }}</a></li>
            </ul>
        </div>
        @php $status = $election->computed_status; @endphp
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-{{ $status === 'open' ? 'success' : ($status === 'upcoming' ? 'warning' : 'secondary') }} fs-6">{{ ucfirst($status) }}</span>
            @if($status === 'closed' && !$election->results_published)
                <form method="POST" action="{{ route('admin.elections.publish_results', $election->id) }}">
                    @csrf
                    <button type="submit" class="eBtn eBtn-success">{{ get_phrase('Publish Results') }}</button>
                </form>
            @endif
        </div>
    </div>
</div></div></div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-12 mb-3">
        <div class="eSection-wrap">
            <p class="mb-2">{{ $election->description }}</p>
            <p class="text-muted mb-0">
                {{ get_phrase('Voting window') }}: {{ $election->start_at->format('d M Y H:i') }} &ndash; {{ $election->end_at->format('d M Y H:i') }}
            </p>
            <p class="text-muted mb-0">
                {{ get_phrase('Turnout') }}: {{ $results['total_voters'] }} / {{ $results['registered_students'] }} ({{ $results['turnout_percent'] }}%)
            </p>
        </div>
    </div>

    <div class="col-12 mb-3">
        <div class="eSection-wrap">
            <h5 class="mb-3">{{ get_phrase('Add Position') }}</h5>
            <form method="POST" action="{{ route('admin.elections.positions.store', $election->id) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-8">
                    <input type="text" name="title" class="form-control eForm-control" placeholder="{{ get_phrase('e.g. Guild President') }}" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Add Position') }}</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($election->positions as $position)
        <div class="col-12 mb-3">
            <div class="eSection-wrap">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">{{ $position->title }}</h5>
                    @if(isset($results['positions'][$position->id]))
                        <span class="text-muted">{{ get_phrase('Total votes') }}: {{ $results['positions'][$position->id]['total_votes'] }}</span>
                    @endif
                </div>

                <div class="table-responsive mb-3">
                    <table class="table eTable">
                        <thead>
                            <tr>
                                <th>{{ get_phrase('Candidate') }}</th>
                                <th>{{ get_phrase('Manifesto') }}</th>
                                <th>{{ get_phrase('Votes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($results['positions'][$position->id]['candidates'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['candidate']->student?->name ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($row['candidate']->manifesto, 80) }}</td>
                                <td>{{ $row['votes'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">{{ get_phrase('No candidates yet') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <form method="POST" action="{{ route('admin.elections.candidates.store', $position->id) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <select name="student_id" class="form-select eForm-select" required>
                            <option value="">{{ get_phrase('Select Student') }}</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="manifesto" class="form-control eForm-control" placeholder="{{ get_phrase('Manifesto (optional)') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="eBtn eBtn-primary w-100">{{ get_phrase('Add Candidate') }}</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
