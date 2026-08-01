@extends('applicant.auth.layout')

@section('title', get_phrase('Create an Account'))
@section('heading', get_phrase('Start your application'))
@section('subheading', get_phrase('Create an account so you can save your progress and come back at any time.'))

@section('content')
<form action="{{ route('applicant.register.submit') }}" method="POST">
    @csrf

    {{-- Honeypot: hidden from real visitors, filled in by bots. --}}
    <div style="position:absolute; left:-9999px;" aria-hidden="true">
        <label>{{ get_phrase('Leave this field blank') }}</label>
        <input type="text" name="website" tabindex="-1" autocomplete="off">
    </div>

    <div class="row g-3">
        <div class="col-sm-6">
            <label class="form-label">{{ get_phrase('First Name') }} <span class="req">*</span></label>
            <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required autofocus>
        </div>
        <div class="col-sm-6">
            <label class="form-label">{{ get_phrase('Last Name') }} <span class="req">*</span></label>
            <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
        </div>
        <div class="col-12">
            <label class="form-label">{{ get_phrase('Email Address') }} <span class="req">*</span></label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            <div class="ap-hint">{{ get_phrase('This is how you will sign in, and where we send updates about your application.') }}</div>
        </div>
        <div class="col-12">
            <label class="form-label">{{ get_phrase('Phone Number') }} <span class="req">*</span></label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
        </div>
        <div class="col-sm-6">
            <label class="form-label">{{ get_phrase('Password') }} <span class="req">*</span></label>
            <input type="password" name="password" class="form-control" required>
            <div class="ap-hint">{{ get_phrase('At least 8 characters.') }}</div>
        </div>
        <div class="col-sm-6">
            <label class="form-label">{{ get_phrase('Confirm Password') }} <span class="req">*</span></label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
    </div>

    <div class="form-check mt-4">
        <input class="form-check-input" type="checkbox" name="terms" id="terms" value="1" {{ old('terms') ? 'checked' : '' }} required>
        <label class="form-check-label" for="terms" style="font-size:14px;">
            {{ get_phrase('I confirm the information I provide will be true and complete, and I consent to it being used to process my application.') }}
        </label>
    </div>

    <button type="submit" class="ap-btn ap-btn-primary w-100 mt-4">
        <i class="bi bi-person-plus"></i> {{ get_phrase('Create Account & Start Applying') }}
    </button>
</form>

<p class="text-center mt-4 mb-0" style="font-size:14.5px; color:var(--ap-muted);">
    {{ get_phrase('Already started an application?') }}
    <a href="{{ route('applicant.login') }}" style="color:var(--ap-primary); font-weight:600;">{{ get_phrase('Sign in') }}</a>
</p>
@endsection
