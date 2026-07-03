@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Transcripts') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="#">{{ get_phrase('Transcripts') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

<div class="row">
<div class="col-12">
<div class="eSection-wrap mb-4">
    <div class="p-3">
        <form action="{{ route('admin.transcripts.index') }}" method="GET" class="d-flex gap-3 align-items-end flex-wrap">
            <div class="flex-grow-1">
                <label class="eForm-label">{{ get_phrase('Search Student') }} ({{ get_phrase('name, email or registration number') }})</label>
                <input type="text" name="search" class="form-control eForm-control"
                    value="{{ $search }}" placeholder="{{ get_phrase('Type student name, email or registration number...') }}" autofocus>
            </div>
            <button type="submit" class="eBtn eBtn-primary"><i class="bi bi-search"></i> {{ get_phrase('Search') }}</button>
        </form>
    </div>
</div>

@if($search)
<div class="eSection-wrap">
    @if($students->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-person-x" style="font-size:2.5rem"></i>
            <div class="mt-2">{{ get_phrase('No students found for') }} "<strong>{{ $search }}</strong>"</div>
        </div>
    @else
    <div class="table-responsive">
        <table class="table eTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ get_phrase('Name') }}</th>
                    <th>{{ get_phrase('Email') }}</th>
                    <th>{{ get_phrase('Reg. No') }}</th>
                    <th>{{ get_phrase('Status') }}</th>
                    <th>{{ get_phrase('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
            @foreach($students as $i => $s)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:50%;background:#1a3a6b;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11px;flex-shrink:0">
                            {{ strtoupper(substr($s->name, 0, 1)) }}
                        </div>
                        <strong>{{ $s->name }}</strong>
                    </div>
                </td>
                <td>{{ $s->email }}</td>
                <td>{{ $s->code ?? '—' }}</td>
                <td>
                    <span class="badge bg-{{ $s->status ? 'success' : 'secondary' }}">
                        {{ $s->status ? get_phrase('Active') : get_phrase('Inactive') }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('admin.transcripts.show', $s->id) }}" class="eBtn eBtn-sm eBtn-primary">
                        <i class="bi bi-file-text"></i> {{ get_phrase('View Transcript') }}
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif

</div></div>

@if(!$search)
<div class="row mt-3">
    <div class="col-12">
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i>
            {{ get_phrase('Search for a student above to view and generate their official academic transcript.') }}
        </div>
    </div>
</div>
@endif
@endsection
