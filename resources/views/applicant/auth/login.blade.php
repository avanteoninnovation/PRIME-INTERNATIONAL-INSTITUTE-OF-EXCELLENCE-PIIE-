@extends('applicant.auth.layout')

@section('title', get_phrase('Sign In'))
@section('heading', get_phrase('Sign in to continue'))
@section('subheading', get_phrase('Pick up your application exactly where you left it.'))

@section('content')
<form action="{{ route('applicant.login.submit') }}" method="POST">
    @csrf

    <div class="mb-3">
        <label class="form-label">{{ get_phrase('Email Address') }} <span class="req">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ get_phrase('Password') }} <span class="req">*</span></label>
        <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password', this)" tabindex="-1" aria-label="{{ get_phrase('Show password') }}">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
            <label class="form-check-label" for="remember">{{ get_phrase('Keep me signed in') }}</label>
        </div>
        <a href="{{ route('applicant.password.request') }}" style="color:var(--ap-primary); font-weight:600; font-size:14px;">
            {{ get_phrase('Forgot password?') }}
        </a>
    </div>

    <button type="submit" class="ap-btn ap-btn-primary w-100">
        <i class="bi bi-box-arrow-in-right"></i> {{ get_phrase('Sign In') }}
    </button>
</form>

<p class="text-center mt-4 mb-0" style="font-size:14.5px; color:var(--ap-muted);">
    {{ get_phrase('New applicant?') }}
    <a href="{{ route('applicant.register') }}" style="color:var(--ap-primary); font-weight:600;">{{ get_phrase('Create an account') }}</a>
</p>
@endsection
