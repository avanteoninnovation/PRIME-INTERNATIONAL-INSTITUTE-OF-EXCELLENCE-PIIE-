@extends('applicant.auth.layout')

@section('title', get_phrase('Reset Password'))
@section('heading', get_phrase('Choose a new password'))
@section('subheading', get_phrase('Pick something you will remember — you will need it to track your application.'))

@section('content')
<form action="{{ route('applicant.password.update') }}" method="POST">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label class="form-label">{{ get_phrase('Email Address') }} <span class="req">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ get_phrase('New Password') }} <span class="req">*</span></label>
        <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password', this)" tabindex="-1" aria-label="{{ get_phrase('Show password') }}">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <div class="ap-hint">{{ get_phrase('At least 8 characters.') }}</div>
    </div>

    <div class="mb-4">
        <label class="form-label">{{ get_phrase('Confirm New Password') }} <span class="req">*</span></label>
        <div class="input-group">
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
            <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password_confirmation', this)" tabindex="-1" aria-label="{{ get_phrase('Show password') }}">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="ap-btn ap-btn-primary w-100">
        <i class="bi bi-shield-check"></i> {{ get_phrase('Reset Password') }}
    </button>
</form>
@endsection
