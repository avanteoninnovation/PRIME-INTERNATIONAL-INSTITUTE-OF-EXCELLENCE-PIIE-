@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Apply for Graduation') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Academic') }}</a></li><li><a href="#">{{ get_phrase('Graduation') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
<div class="row justify-content-center"><div class="col-md-7"><div class="eSection-wrap">
    @if($app)
    <div class="text-center py-4">
        <div class="display-1 {{ $app->status=='graduated'?'text-success':($app->status=='approved'?'text-primary':'text-warning') }}">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <h4 class="mt-3">{{ get_phrase('Your Application Status') }}</h4>
        <span class="badge bg-{{ $app->status=='graduated'?'success':($app->status=='approved'?'primary':($app->status=='pending'?'warning':'danger')) }} fs-6 mt-2">{{ ucfirst($app->status) }}</span>
        @if($app->classification)<div class="mt-2"><strong>{{ get_phrase('Classification') }}:</strong> {{ $app->classification }}</div>@endif
        @if($app->gpa)<div><strong>{{ get_phrase('GPA') }}:</strong> {{ $app->gpa }}</div>@endif
    </div>
    @else
    <h5 class="mb-4">{{ get_phrase('Graduation Application Form') }}</h5>
    <form method="POST" action="{{ route('student.graduation.store') }}" class="d-block ajaxForm">
        @csrf
        <div class="fpb-7">
            <label class="eForm-label">{{ get_phrase('Programme') }}</label>
            <select class="form-control eForm-control" name="programme_id">
                <option value="">{{ get_phrase('Select Programme') }}</option>
                @foreach($programmes as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fpb-7 mt-3">
            <label class="eForm-label">{{ get_phrase('Notes / Message') }}</label>
            <textarea class="form-control eForm-control" name="notes" rows="3" placeholder="{{ get_phrase('Any additional information...') }}"></textarea>
        </div>
        <div class="fpb-7 pt-3">
            <button class="btn-form" type="submit">{{ get_phrase('Submit Application') }}</button>
        </div>
    </form>
    @endif
</div></div></div>
@endsection
