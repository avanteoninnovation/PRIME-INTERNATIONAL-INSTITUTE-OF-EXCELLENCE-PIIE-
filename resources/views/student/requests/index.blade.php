@extends('student.navigation')
@section('content')

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Student Affairs') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route('student.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Student Affairs') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

<div class="row">
    <div class="col-md-5">
        <div class="eSection-wrap">
            <div class="title mb-3 pb-0 border-0">
                <h3 class="mb-0">{{ get_phrase('New Request') }}</h3>
            </div>
            <form method="POST" action="{{ route('student.requests.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="eForm-label">{{ get_phrase('Type') }} *</label>
                    <select name="type" class="form-select eForm-select" required>
                        <option value="">{{ get_phrase('Select type') }}</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ get_phrase($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="eForm-label">{{ get_phrase('Subject') }} *</label>
                    <input type="text" class="form-control eForm-control" name="subject" value="{{ old('subject') }}" required>
                </div>
                <div class="mb-3">
                    <label class="eForm-label">{{ get_phrase('Details') }} *</label>
                    <textarea class="form-control eForm-control" name="details" rows="5" required>{{ old('details') }}</textarea>
                </div>
                <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Submit Request') }}</button>
            </form>
        </div>
    </div>

    <div class="col-md-7">
        <div class="eSection-wrap">
            <div class="title mb-3 pb-0 border-0">
                <h3 class="mb-0">{{ get_phrase('My Requests') }}</h3>
            </div>
            @forelse($requests as $req)
                <div class="d-flex justify-content-between align-items-start py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                    <div class="pe-3">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-secondary">{{ get_phrase($types[$req->type] ?? $req->type) }}</span>
                            <span class="badge bg-{{ $req->status === 'approved' ? 'success' : ($req->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </div>
                        <strong>{{ $req->subject }}</strong>
                        <p class="mb-1 text-muted">{{ $req->details }}</p>
                        @if($req->admin_response)
                            <div class="alert alert-info py-2 px-3 mb-1" style="font-size:13px;">
                                <strong>{{ get_phrase('Response') }}:</strong> {{ $req->admin_response }}
                            </div>
                        @endif
                        <span class="text-muted small">{{ $req->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="text-muted text-center py-4 mb-0">{{ get_phrase('No requests submitted yet') }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
