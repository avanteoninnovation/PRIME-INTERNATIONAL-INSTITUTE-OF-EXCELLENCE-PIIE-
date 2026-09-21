@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex flex-column">
        <h4>{{ $election->title }} — {{ get_phrase('Results') }}</h4>
        <ul class="d-flex align-items-center eBreadcrumb-2">
            <li><a href="{{ route('student.dashboard') }}">{{ get_phrase('Home') }}</a></li>
            <li><a href="{{ route('student.elections.index') }}">{{ get_phrase('Elections') }}</a></li>
            <li><a href="#">{{ get_phrase('Results') }}</a></li>
        </ul>
    </div>
</div></div></div>

<div class="row">
    <div class="col-12 mb-3">
        <div class="eSection-wrap">
            <p class="text-muted mb-0">
                {{ get_phrase('Turnout') }}: {{ $results['total_voters'] }} / {{ $results['registered_students'] }} ({{ $results['turnout_percent'] }}%)
            </p>
        </div>
    </div>

    @foreach($election->positions as $position)
        <div class="col-12 mb-3">
            <div class="eSection-wrap">
                <h5 class="mb-3">{{ $position->title }}</h5>
                @php $row = $results['positions'][$position->id] ?? null; @endphp
                @if($row && $row['winner'])
                    <p class="mb-3">
                        <span class="badge bg-success fs-6">{{ get_phrase('Winner') }}: {{ $row['winner']['candidate']->student?->name }}</span>
                    </p>
                @endif
                <div class="table-responsive">
                    <table class="table eTable">
                        <thead>
                            <tr>
                                <th>{{ get_phrase('Candidate') }}</th>
                                <th>{{ get_phrase('Votes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($row['candidates'] ?? [] as $c)
                            <tr>
                                <td>{{ $c['candidate']->student?->name }}</td>
                                <td>{{ $c['votes'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">{{ get_phrase('No candidates') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
