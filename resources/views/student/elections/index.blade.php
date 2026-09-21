@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex flex-column">
        <h4>{{ get_phrase('Elections') }}</h4>
        <ul class="d-flex align-items-center eBreadcrumb-2">
            <li><a href="{{ route('student.dashboard') }}">{{ get_phrase('Home') }}</a></li>
            <li><a href="#">{{ get_phrase('Elections') }}</a></li>
        </ul>
    </div>
</div></div></div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <div class="table-responsive">
                <table class="table eTable">
                    <thead>
                        <tr>
                            <th>{{ get_phrase('Title') }}</th>
                            <th>{{ get_phrase('Voting Window') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th class="text-end">{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($elections as $election)
                        @php $status = $election->computed_status; @endphp
                        <tr>
                            <td>{{ $election->title }}</td>
                            <td>{{ $election->start_at->format('d M Y H:i') }} &ndash; {{ $election->end_at->format('d M Y H:i') }}</td>
                            <td>
                                <span class="badge bg-{{ $status === 'open' ? 'success' : ($status === 'upcoming' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($status === 'open')
                                    <a href="{{ route('student.elections.show', $election->id) }}" class="eBtn eBtn-sm eBtn-primary">{{ get_phrase('Vote') }}</a>
                                @elseif($election->results_published)
                                    <a href="{{ route('student.elections.results', $election->id) }}" class="eBtn eBtn-sm eBtn-secondary">{{ get_phrase('View Results') }}</a>
                                @else
                                    <span class="text-muted">{{ get_phrase('Not available') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ get_phrase('No elections found') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
