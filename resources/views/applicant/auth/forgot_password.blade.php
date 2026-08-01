@extends('applicant.auth.layout')

@section('title', get_phrase('Forgot Password'))
@section('heading', get_phrase('Reset your password'))
@section('subheading', get_phrase('Enter the email you applied with and we will send you a reset link.'))

@section('content')
<form action="{{ route('applicant.password.email') }}" method="POST">
    @csrf

    <div class="mb-4">
        <label class="form-label">{{ get_phrase('Email Address') }} <span class="req">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
    </div>

    <button type="submit" class="ap-btn ap-btn-primary w-100">
        <i class="bi bi-envelope"></i> {{ get_phrase('Send Reset Link') }}
    </button>
</form>

<p class="text-center mt-4 mb-0" style="font-size:14.5px;">
    <a href="{{ route('applicant.login') }}" style="color:var(--ap-primary); font-weight:600;">
        <i class="bi bi-arrow-left"></i> {{ get_phrase('Back to sign in') }}
    </a>
</p>
@endsection
