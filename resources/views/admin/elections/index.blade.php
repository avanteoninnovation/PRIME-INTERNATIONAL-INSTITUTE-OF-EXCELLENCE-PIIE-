@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Elections') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="#">{{ get_phrase('Elections') }}</a></li>
            </ul>
        </div>
        <a href="{{ route('admin.elections.create') }}" class="eBtn eBtn-primary">{{ get_phrase('Create Election') }}</a>
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
                            <th>{{ get_phrase('Positions') }}</th>
                            <th>{{ get_phrase('Voting Window') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th>{{ get_phrase('Results') }}</th>
                            <th class="text-end">{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($elections as $election)
                        <tr>
                            <td>{{ $election->title }}</td>
                            <td>{{ $election->positions_count }}</td>
                            <td>{{ $election->start_at->format('d M Y H:i') }} &ndash; {{ $election->end_at->format('d M Y H:i') }}</td>
                            <td>
                                @php $status = $election->computed_status; @endphp
                                <span class="badge bg-{{ $status === 'open' ? 'success' : ($status === 'upcoming' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td>
                                @if($election->results_published)
                                    <span class="badge bg-success">{{ get_phrase('Published') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ get_phrase('Not published') }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.elections.show', $election->id) }}" class="eBtn eBtn-sm eBtn-primary">{{ get_phrase('Manage') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No elections found') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
