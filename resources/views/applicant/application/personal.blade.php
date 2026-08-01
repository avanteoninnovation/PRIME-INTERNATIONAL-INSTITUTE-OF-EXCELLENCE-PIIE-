@extends('applicant.layout')

@section('title', get_phrase('Personal Information'))
@section('subtitle', get_phrase('Tell us who you are and how we can reach you.'))

@section('content')

@include('applicant.application._stepper')

<form action="{{ route('applicant.application.personal') }}" method="POST">
    @csrf

    <div class="ap-card">
        <div class="ap-fieldset-title">{{ get_phrase('Your Details') }}</div>

        <div class="row g-3">
            <div class="col-md-2">
                <label class="form-label">{{ get_phrase('Title') }}</label>
                <select name="title" class="form-select" {{ $readOnly ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach(['Mr', 'Mrs', 'Ms', 'Dr', 'Prof'] as $t)
                        <option value="{{ $t }}" {{ old('title', $admission->title) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('First Name') }} <span class="req">*</span></label>
                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $admission->first_name) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ get_phrase('Middle Name') }}</label>
                <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $admission->middle_name) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ get_phrase('Last Name') }} <span class="req">*</span></label>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $admission->last_name) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>

            <div class="col-md-6">
                <label class="form-label">{{ get_phrase('Email Address') }} <span class="req">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $admission->email) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ get_phrase('Phone Contact') }} <span class="req">*</span></label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $admission->phone) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>

            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('Date of Birth') }} <span class="req">*</span></label>
                <input type="date" name="dob" class="form-control"
                       value="{{ old('dob', optional($admission->dob)->format('Y-m-d')) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('Gender') }} <span class="req">*</span></label>
                <select name="gender" class="form-select" {{ $readOnly ? 'disabled' : 'required' }}>
                    <option value="">{{ get_phrase('Select gender') }}</option>
                    @foreach($genders as $option)
                        <option value="{{ $option }}" {{ old('gender', $admission->gender) === $option ? 'selected' : '' }}>{{ get_phrase($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('Marital Status') }}</label>
                <select name="marital_status" class="form-select" {{ $readOnly ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach($maritalStatus as $option)
                        <option value="{{ $option }}" {{ old('marital_status', $admission->marital_status) === $option ? 'selected' : '' }}>{{ get_phrase($option) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('Nationality') }} <span class="req">*</span></label>
                <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $admission->nationality) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('Country of Residence') }}</label>
                <input type="text" name="country_of_residence" class="form-control" value="{{ old('country_of_residence', $admission->country_of_residence) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('Religion') }}</label>
                <input type="text" name="religion" class="form-control" value="{{ old('religion', $admission->religion) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>

            <div class="col-md-6">
                <label class="form-label">{{ get_phrase('National ID Number') }}</label>
                <input type="text" name="national_id_no" class="form-control" value="{{ old('national_id_no', $admission->national_id_no) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ get_phrase('Passport Number') }}</label>
                <input type="text" name="passport_no" class="form-control" value="{{ old('passport_no', $admission->passport_no) }}" {{ $readOnly ? 'readonly' : '' }}>
                <div class="ap-hint">{{ get_phrase('Required for international applicants.') }}</div>
            </div>
        </div>

        <div class="ap-fieldset-title">{{ get_phrase('Where You Live') }}</div>

        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">{{ get_phrase('Physical Address') }} <span class="req">*</span></label>
                <textarea name="physical_address" class="form-control" rows="2" {{ $readOnly ? 'readonly' : 'required' }}>{{ old('physical_address', $admission->physical_address) }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ get_phrase('City / Town') }}</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $admission->city) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
        </div>

        <div class="ap-fieldset-title">{{ get_phrase('Accessibility') }}</div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="has_disability" id="hasDisability" value="1"
                   {{ old('has_disability', $admission->has_disability) ? 'checked' : '' }} {{ $readOnly ? 'disabled' : '' }}>
            <label class="form-check-label" for="hasDisability">
                {{ get_phrase('I have a disability or condition the institution should know about') }}
            </label>
        </div>

        <div id="disabilityDetailsWrap" class="{{ old('has_disability', $admission->has_disability) ? '' : 'd-none' }}">
            <label class="form-label">{{ get_phrase('Please give brief details') }}</label>
            <textarea name="disability_details" class="form-control" rows="2" {{ $readOnly ? 'readonly' : '' }}>{{ old('disability_details', $admission->disability_details) }}</textarea>
            <div class="ap-hint">{{ get_phrase('This helps us arrange any support you may need. It does not affect the outcome of your application.') }}</div>
        </div>

        <div class="ap-fieldset-title">{{ get_phrase('Next of Kin / Emergency Contact') }}</div>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ get_phrase('Full Name') }} <span class="req">*</span></label>
                <input type="text" name="nok_name" class="form-control" value="{{ old('nok_name', $admission->nok_name) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ get_phrase('Relationship') }} <span class="req">*</span></label>
                <input type="text" name="nok_relationship" class="form-control" value="{{ old('nok_relationship', $admission->nok_relationship) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-3">
                <label class="form-label">{{ get_phrase('Phone') }} <span class="req">*</span></label>
                <input type="text" name="nok_phone" class="form-control" value="{{ old('nok_phone', $admission->nok_phone) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ get_phrase('Email') }}</label>
                <input type="email" name="nok_email" class="form-control" value="{{ old('nok_email', $admission->nok_email) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ get_phrase('Address') }}</label>
                <input type="text" name="nok_address" class="form-control" value="{{ old('nok_address', $admission->nok_address) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
        </div>

        @unless($readOnly)
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-4" style="border-top:1px solid var(--ap-line);">
                <button type="submit" name="action" value="save" class="ap-btn ap-btn-ghost">
                    <i class="bi bi-save"></i> {{ get_phrase('Save') }}
                </button>
                <button type="submit" name="action" value="continue" class="ap-btn ap-btn-primary">
                    {{ get_phrase('Save & Continue') }} <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        @endunless
    </div>
</form>

@push('scripts')
<script>
    document.getElementById('hasDisability')?.addEventListener('change', function () {
        document.getElementById('disabilityDetailsWrap').classList.toggle('d-none', !this.checked);
    });
</script>
@endpush

@endsection
