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
        <input type="password" name="password" class="form-control" required>
        <div class="ap-hint">{{ get_phrase('At least 8 characters.') }}</div>
    </div>

    <div class="mb-4">
        <label class="form-label">{{ get_phrase('Confirm New Password') }} <span class="req">*</span></label>
        <input type="password" name="password_confirmation" class="form-control" required>
    </div>

    <button type="submit" class="ap-btn ap-btn-primary w-100">
        <i class="bi bi-shield-check"></i> {{ get_phrase('Reset Password') }}
    </button>
</form>
@endsection
