@extends('frontend.index')
@section('content')

<div class="container" style="max-width:900px; margin-top:60px; margin-bottom:80px;">
    <div class="text-center mb-4">
        <h2>{{ get_phrase('Apply Now') }}</h2>
        <p class="text-muted">{{ get_phrase('Submit your application below. Our admissions team will review it and get back to you.') }}</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="contact-form-card" style="padding:30px; border-radius:10px; box-shadow: 0 2px 12px rgba(0,0,0,0.08);">
        <form action="{{ route('apply.submit') }}" method="POST" enctype="multipart/form-data">
            @csrf

            {{-- Honeypot: hidden from real visitors via CSS, left blank by humans --}}
            <div style="position:absolute; left:-9999px;" aria-hidden="true">
                <label>{{ get_phrase('Leave this field blank') }}</label>
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

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
                <div class="col-12">
                    <label class="form-label">{{ get_phrase('Supporting Documents') }}</label>
                    <input type="file" name="documents[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png">
                    <small class="text-muted">{{ get_phrase('PDF, JPG or PNG, max 5MB each, up to 5 files.') }}</small>
                </div>
                <div class="col-12 text-center mt-3">
                    <button type="submit" class="eBtn" style="padding:12px 40px;">{{ get_phrase('Submit Application') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
