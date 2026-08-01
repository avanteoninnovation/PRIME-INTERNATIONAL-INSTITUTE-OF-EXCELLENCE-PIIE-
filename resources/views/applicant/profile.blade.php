@extends('applicant.layout')

@section('title', get_phrase('Account Settings'))
@section('subtitle', get_phrase('Your sign-in details and contact information.'))

@section('content')

<div class="row g-4">
    <div class="col-lg-7">
        <div class="ap-card">
            <div class="ap-card-head">
                <h2 class="ap-card-title"><i class="bi bi-person-gear"></i> {{ get_phrase('Your Details') }}</h2>
            </div>

            <form action="{{ route('applicant.profile.update') }}" method="POST">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('First Name') }} <span class="req">*</span></label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $applicant->first_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Last Name') }} <span class="req">*</span></label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $applicant->last_name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Email Address') }} <span class="req">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $applicant->email) }}" required>
                        <div class="ap-hint">{{ get_phrase('You sign in with this address and we send application updates to it.') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Phone Number') }} <span class="req">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone', $applicant->phone) }}" required>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="ap-btn ap-btn-primary"><i class="bi bi-save"></i> {{ get_phrase('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="ap-card">
            <div class="ap-card-head">
                <h2 class="ap-card-title"><i class="bi bi-shield-lock"></i> {{ get_phrase('Change Password') }}</h2>
            </div>

            <form action="{{ route('applicant.password.change') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">{{ get_phrase('Current Password') }} <span class="req">*</span></label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ get_phrase('New Password') }} <span class="req">*</span></label>
                    <input type="password" name="password" class="form-control" required>
                    <div class="ap-hint">{{ get_phrase('At least 8 characters.') }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ get_phrase('Confirm New Password') }} <span class="req">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="ap-btn ap-btn-ghost"><i class="bi bi-key"></i> {{ get_phrase('Update Password') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
