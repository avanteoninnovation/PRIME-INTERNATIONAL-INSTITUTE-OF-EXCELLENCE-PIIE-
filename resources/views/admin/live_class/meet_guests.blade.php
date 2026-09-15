@extends('admin.navigation')
@section('content')

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Google Meet Guests') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                        <li><a href="{{ route('admin.live_classes.index') }}">{{ get_phrase('Live Classes') }}</a></li>
                        <li><a href="#">{{ get_phrase('Meet Guests') }}</a></li>
                    </ul>
                </div>
                <a href="{{ route('admin.live_classes.index') }}" class="eBtn eBtn-dark">{{ get_phrase('Back') }}</a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap mb-3" style="max-width: 720px;">
            <div class="alert alert-info mb-3">
                Every email added here is invited as an attendee on every Google Meet class this school schedules, and gets an emailed calendar invite. Signing into one of these Google accounts when joining a class gives that person recognised-guest status on the meeting, instead of joining as an anonymous stranger who has to knock to get in.
            </div>

            <h5 class="mb-3">{{ get_phrase('Add a Guest Email') }}</h5>
            <form method="POST" action="{{ route('admin.live_classes.meet_guests.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-5">
                    <label class="eForm-label">{{ get_phrase('Email') }} *</label>
                    <input type="email" class="form-control eForm-control" name="email" value="{{ old('email') }}" placeholder="teacher@gmail.com" required>
                </div>
                <div class="col-md-5">
                    <label class="eForm-label">{{ get_phrase('Label') }}</label>
                    <input type="text" class="form-control eForm-control" name="label" value="{{ old('label') }}" placeholder="{{ get_phrase('e.g. John (Lecturer)') }}">
                </div>
                <div class="col-md-2 d-grid">
                    <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Add') }}</button>
                </div>
            </form>
        </div>

        <div class="eSection-wrap" style="max-width: 720px;">
            <div class="title mb-3 pb-0 border-0 d-flex justify-content-between align-items-center">
                <h3 class="mb-0">{{ get_phrase('Current Guests') }}</h3>
                <span class="badge bg-secondary">{{ $guests->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table eTable">
                    <thead>
                    <tr>
                        <th>{{ get_phrase('Email') }}</th>
                        <th>{{ get_phrase('Label') }}</th>
                        <th>{{ get_phrase('Added') }}</th>
                        <th class="text-end">{{ get_phrase('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($guests as $guest)
                        <tr>
                            <td>{{ $guest->email }}</td>
                            <td>{{ $guest->label ?: '—' }}</td>
                            <td>{{ $guest->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.live_classes.meet_guests.destroy', $guest->id) }}" onsubmit="return confirm('{{ get_phrase('Remove this guest?') }}')" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Remove') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">{{ get_phrase('No guest emails added yet') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
