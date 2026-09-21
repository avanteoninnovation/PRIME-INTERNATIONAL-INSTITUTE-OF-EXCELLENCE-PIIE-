@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Create Election') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="{{ route('admin.elections.index') }}">{{ get_phrase('Elections') }}</a></li>
                <li><a href="#">{{ get_phrase('Create') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <form method="POST" action="{{ route('admin.elections.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="eForm-label">{{ get_phrase('Title') }}</label>
                    <input type="text" name="title" class="form-control eForm-control" value="{{ old('title') }}" required>
                </div>
                <div class="mb-3">
                    <label class="eForm-label">{{ get_phrase('Description') }}</label>
                    <textarea name="description" class="form-control eForm-control" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="eForm-label">{{ get_phrase('Voting Opens') }}</label>
                        <input type="datetime-local" name="start_at" class="form-control eForm-control" value="{{ old('start_at') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="eForm-label">{{ get_phrase('Voting Closes') }}</label>
                        <input type="datetime-local" name="end_at" class="form-control eForm-control" value="{{ old('end_at') }}" required>
                    </div>
                </div>
                <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Create Election') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection
