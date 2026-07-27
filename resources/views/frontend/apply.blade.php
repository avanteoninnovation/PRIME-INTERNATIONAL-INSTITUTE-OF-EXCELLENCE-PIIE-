@extends('frontend.index')
@section('content')

<link rel="stylesheet" href="{{ asset('css/website.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Navigation Header (same standard header used across the public site) -->
<nav>
    <div class="container-xl">
        <div>
            <i class="fas fa-graduation-cap" style="font-size: 20px;"></i>
            <span>{{ get_settings('system_title') ?? 'PIIE' }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:0;">
            <a href="{{ route('landingPage') }}">{{ get_phrase('Home') }}</a>
            <a href="{{ route('apply.form') }}" class="active">{{ get_phrase('Apply Now') }}</a>
            <a href="{{ route('login') }}">{{ get_phrase('Login') }}</a>
        </div>
    </div>
</nav>

<!-- Page Header -->
<div class="page-header">
    <div class="container-xl">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:20px;">
            <div>
                <h1>{{ get_phrase('Apply Now') }}</h1>
                <div class="subtitle">{{ get_phrase('Submit your application below. Our admissions team will review it and get back to you.') }}</div>
            </div>
            <a href="{{ route('landingPage') }}" class="back-btn">
                <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> {{ get_phrase('Back to Home') }}
            </a>
        </div>
    </div>
</div>

<!-- Application Form -->
<main class="section-padding">
    <div class="container-xl" style="max-width:900px;">

        @if(session('success'))
            <div class="apply-alert apply-alert-success">
                <i class="fas fa-circle-check"></i> {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="apply-alert apply-alert-danger">
                <i class="fas fa-triangle-exclamation"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="apply-card">
            <form action="{{ route('apply.submit') }}" method="POST" enctype="multipart/form-data">
                @csrf

                {{-- Honeypot: hidden from real visitors via CSS, left blank by humans --}}
                <div style="position:absolute; left:-9999px;" aria-hidden="true">
                    <label>{{ get_phrase('Leave this field blank') }}</label>
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <div class="apply-section-heading">{{ get_phrase('Personal Details') }}</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('First Name') }} *</label>
                        <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Last Name') }} *</label>
                        <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Email Address') }} *</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Phone Contact') }} *</label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Date of Birth') }}</label>
                        <input type="date" name="dob" class="form-control" value="{{ old('dob') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Gender') }}</label>
                        <select name="gender" class="form-control">
                            <option value="">{{ get_phrase('Select gender') }}</option>
                            <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>{{ get_phrase('Male') }}</option>
                            <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>{{ get_phrase('Female') }}</option>
                            <option value="Others" {{ old('gender') == 'Others' ? 'selected' : '' }}>{{ get_phrase('Others') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Nationality') }}</label>
                        <input type="text" name="nationality" class="form-control" value="{{ old('nationality') }}">
                    </div>
                </div>

                <div class="apply-section-heading">{{ get_phrase('Programme & Intake') }}</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Programme') }} *</label>
                        <select name="programme_id" class="form-control" required>
                            <option value="">{{ get_phrase('Select a programme') }}</option>
                            @foreach($programmes as $programme)
                                <option value="{{ $programme->id }}" {{ old('programme_id') == $programme->id ? 'selected' : '' }}>{{ $programme->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">{{ get_phrase('Intake') }}</label>
                        <select name="intake_session_id" class="form-control">
                            <option value="">{{ get_phrase('Select an intake') }}</option>
                            @foreach($intakeSessions as $session)
                                <option value="{{ $session->id }}" {{ old('intake_session_id') == $session->id ? 'selected' : '' }}>{{ $session->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">{{ get_phrase('Qualifications') }}</label>
                        <textarea name="qualifications" class="form-control" rows="4" placeholder="{{ get_phrase('Summarize your academic qualifications') }}">{{ old('qualifications') }}</textarea>
                    </div>
                </div>

                <div class="apply-section-heading">{{ get_phrase('Supporting Documents') }}</div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">{{ get_phrase('Upload Documents') }}</label>
                        <input type="file" name="documents[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">{{ get_phrase('PDF, JPG or PNG, max 5MB each, up to 5 files.') }}</small>
                    </div>
                </div>

                <div class="text-center apply-submit-row">
                    <button type="submit" class="apply-submit-btn">
                        {{ get_phrase('Submit Application') }} <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<style>
.apply-card {
    background: var(--white-bg, #fff);
    border: 1px solid var(--border-color, #e8edf5);
    border-radius: var(--radius-lg, 14px);
    box-shadow: var(--shadow-md, 0 4px 16px rgba(0,0,0,0.1));
    padding: var(--spacing-xl, 3rem);
}

.apply-section-heading {
    font-size: 15px;
    font-weight: 700;
    color: var(--primary-color, #1466AF);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: var(--spacing-lg, 2rem) 0 var(--spacing-sm, 1rem);
    padding-bottom: 8px;
    border-bottom: 2px solid var(--border-light, #f0f4f9);
}

.apply-section-heading:first-child {
    margin-top: 0;
}

.apply-card .form-label {
    font-weight: 600;
    color: var(--text-primary, #1a1a1a);
    font-size: 14px;
    margin-bottom: 6px;
}

.apply-card .form-control {
    border: 1px solid var(--border-color, #e8edf5);
    border-radius: var(--radius-sm, 6px);
    padding: 10px 14px;
}

.apply-card .form-control:focus {
    border-color: var(--primary-color, #1466AF);
    box-shadow: 0 0 0 3px rgba(20, 102, 175, 0.12);
}

.apply-submit-row {
    margin-top: var(--spacing-lg, 2rem);
}

.apply-submit-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 40px;
    background: linear-gradient(135deg, var(--secondary-color, #F15F23), var(--secondary-light, #f5885a));
    color: #fff;
    font-weight: 600;
    font-size: 16px;
    border: none;
    border-radius: 30px;
    box-shadow: 0 8px 20px rgba(241, 95, 35, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
}

.apply-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(241, 95, 35, 0.4);
}

.apply-alert {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 16px 20px;
    border-radius: var(--radius-md, 10px);
    margin-bottom: var(--spacing-md, 1.5rem);
    font-size: 14px;
}

.apply-alert-success {
    background: rgba(39, 174, 96, 0.1);
    color: #1e8449;
    border: 1px solid rgba(39, 174, 96, 0.25);
}

.apply-alert-danger {
    background: rgba(231, 76, 60, 0.08);
    color: #c0392b;
    border: 1px solid rgba(231, 76, 60, 0.25);
}

@media (max-width: 576px) {
    .apply-card {
        padding: var(--spacing-md, 1.5rem);
    }
}
</style>

@endsection
