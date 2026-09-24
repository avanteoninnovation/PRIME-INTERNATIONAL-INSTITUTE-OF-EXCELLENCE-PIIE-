@extends('admin.navigation')
@section('content')

@include('admin.admissions.wizard._layout_top')

<div class="eSection-wrap mb-3">
    <h5 class="mb-3">{{ get_phrase('Personal Information') }}</h5>

    <form action="{{ route('admin.hei_admissions.wizard.personal', $admission->id) }}" method="POST">
        @csrf
        <div class="row g-3">
            <div class="col-md-2 fpb-7">
                <label class="eForm-label">{{ get_phrase('Title') }}</label>
                <select name="title" class="form-select eForm-control" {{ $readOnly ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach(['Mr', 'Mrs', 'Ms', 'Dr', 'Prof'] as $t)
                        <option value="{{ $t }}" {{ old('title', $admission->title) === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('First Name') }} *</label>
                <input type="text" name="first_name" class="form-control eForm-control" value="{{ old('first_name', $admission->first_name) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-3 fpb-7">
                <label class="eForm-label">{{ get_phrase('Middle Name') }}</label>
                <input type="text" name="middle_name" class="form-control eForm-control" value="{{ old('middle_name', $admission->middle_name) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
            <div class="col-md-3 fpb-7">
                <label class="eForm-label">{{ get_phrase('Last Name') }} *</label>
                <input type="text" name="last_name" class="form-control eForm-control" value="{{ old('last_name', $admission->last_name) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>

            <div class="col-md-6 fpb-7">
                <label class="eForm-label">{{ get_phrase('Email Address') }} *</label>
                <input type="email" name="email" class="form-control eForm-control" value="{{ old('email', $admission->email) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-6 fpb-7">
                <label class="eForm-label">{{ get_phrase('Phone Contact') }} *</label>
                <div class="input-group">
                    <select name="phone_code" class="form-select eForm-control" style="max-width:140px;" {{ $readOnly ? 'disabled' : 'required' }}>
                        <option value="">{{ get_phrase('Code') }}</option>
                        @foreach($countries as $c)
                            @if($c['dial_code'])
                                <option value="{{ $c['dial_code'] }}" {{ old('phone_code', $phoneCode) === $c['dial_code'] ? 'selected' : '' }}>{{ $c['dial_code'] }} {{ $c['name'] }}</option>
                            @endif
                        @endforeach
                    </select>
                    <input type="text" name="phone_number" class="form-control eForm-control" placeholder="{{ get_phrase('e.g. 700000000') }}" value="{{ old('phone_number', $phoneNumber) }}" {{ $readOnly ? 'readonly' : 'required' }}>
                </div>
            </div>

            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('Date of Birth') }} *</label>
                <input type="date" name="dob" class="form-control eForm-control" value="{{ old('dob', optional($admission->dob)->format('Y-m-d')) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('Gender') }} *</label>
                <select name="gender" class="form-select eForm-control" {{ $readOnly ? 'disabled' : 'required' }}>
                    <option value="">{{ get_phrase('— Select —') }}</option>
                    @foreach($genders as $option)
                        <option value="{{ $option }}" {{ old('gender', $admission->gender) === $option ? 'selected' : '' }}>{{ get_phrase($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('Marital Status') }}</label>
                <select name="marital_status" class="form-select eForm-control" {{ $readOnly ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach($maritalStatus as $option)
                        <option value="{{ $option }}" {{ old('marital_status', $admission->marital_status) === $option ? 'selected' : '' }}>{{ get_phrase($option) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('Nationality') }} *</label>
                <select name="nationality" class="form-select eForm-control" {{ $readOnly ? 'disabled' : 'required' }}>
                    <option value="">{{ get_phrase('— Select —') }}</option>
                    @foreach($countries as $c)
                        <option value="{{ $c['nationality'] }}" {{ old('nationality', $admission->nationality) === $c['nationality'] ? 'selected' : '' }}>{{ $c['nationality'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('Country of Residence') }}</label>
                <select name="country_of_residence" class="form-select eForm-control" {{ $readOnly ? 'disabled' : '' }}>
                    <option value="">{{ get_phrase('— Select —') }}</option>
                    @foreach($countries as $c)
                        <option value="{{ $c['name'] }}" {{ old('country_of_residence', $admission->country_of_residence) === $c['name'] ? 'selected' : '' }}>{{ $c['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('Religion') }}</label>
                @php
                    $currentReligion = old('religion', $admission->religion);
                    $religionIsOther = filled($currentReligion) && !in_array($currentReligion, $religions, true);
                @endphp
                <select name="religion" id="religionSelect" class="form-select eForm-control" {{ $readOnly ? 'disabled' : '' }}>
                    <option value="">—</option>
                    @foreach($religions as $option)
                        <option value="{{ $option }}" {{ ($religionIsOther ? 'Other' : $currentReligion) === $option ? 'selected' : '' }}>{{ get_phrase($option) }}</option>
                    @endforeach
                </select>
                <input type="text" name="religion_other" id="religionOther"
                       class="form-control eForm-control mt-2 {{ $religionIsOther ? '' : 'd-none' }}"
                       placeholder="{{ get_phrase('Please specify') }}"
                       value="{{ old('religion_other', $religionIsOther ? $currentReligion : '') }}"
                       {{ $readOnly ? 'readonly' : '' }}>
            </div>

            <div class="col-md-6 fpb-7">
                <label class="eForm-label">{{ get_phrase('National ID Number') }}</label>
                <input type="text" name="national_id_no" class="form-control eForm-control" value="{{ old('national_id_no', $admission->national_id_no) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
            <div class="col-md-6 fpb-7">
                <label class="eForm-label">{{ get_phrase('Passport Number') }}</label>
                <input type="text" name="passport_no" class="form-control eForm-control" value="{{ old('passport_no', $admission->passport_no) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>

            <div class="col-md-8 fpb-7">
                <label class="eForm-label">{{ get_phrase('Physical Address') }} *</label>
                <textarea name="physical_address" class="form-control eForm-control" rows="2" {{ $readOnly ? 'readonly' : 'required' }}>{{ old('physical_address', $admission->physical_address) }}</textarea>
            </div>
            <div class="col-md-4 fpb-7">
                <label class="eForm-label">{{ get_phrase('City / Town') }}</label>
                <input type="text" name="city" class="form-control eForm-control" value="{{ old('city', $admission->city) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>

            <div class="col-12 fpb-7">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="has_disability" id="hasDisability" value="1"
                           {{ old('has_disability', $admission->has_disability) ? 'checked' : '' }} {{ $readOnly ? 'disabled' : '' }}>
                    <label class="form-check-label" for="hasDisability">
                        {{ get_phrase('Candidate has a disability or condition the institution should know about') }}
                    </label>
                </div>
                <div id="disabilityDetailsWrap" class="mt-2 {{ old('has_disability', $admission->has_disability) ? '' : 'd-none' }}">
                    <textarea name="disability_details" class="form-control eForm-control" rows="2" placeholder="{{ get_phrase('Brief details') }}" {{ $readOnly ? 'readonly' : '' }}>{{ old('disability_details', $admission->disability_details) }}</textarea>
                </div>
            </div>
        </div>

        <h6 class="mt-4 mb-3">{{ get_phrase('Next of Kin / Emergency Contact') }}</h6>
        <div class="row g-3">
            <div class="col-md-6 fpb-7">
                <label class="eForm-label">{{ get_phrase('Full Name') }} *</label>
                <input type="text" name="nok_name" class="form-control eForm-control" value="{{ old('nok_name', $admission->nok_name) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-3 fpb-7">
                <label class="eForm-label">{{ get_phrase('Relationship') }} *</label>
                <input type="text" name="nok_relationship" class="form-control eForm-control" value="{{ old('nok_relationship', $admission->nok_relationship) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-3 fpb-7">
                <label class="eForm-label">{{ get_phrase('Phone') }} *</label>
                <input type="text" name="nok_phone" class="form-control eForm-control" value="{{ old('nok_phone', $admission->nok_phone) }}" {{ $readOnly ? 'readonly' : 'required' }}>
            </div>
            <div class="col-md-6 fpb-7">
                <label class="eForm-label">{{ get_phrase('Email') }}</label>
                <input type="email" name="nok_email" class="form-control eForm-control" value="{{ old('nok_email', $admission->nok_email) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
            <div class="col-md-6 fpb-7">
                <label class="eForm-label">{{ get_phrase('Address') }}</label>
                <input type="text" name="nok_address" class="form-control eForm-control" value="{{ old('nok_address', $admission->nok_address) }}" {{ $readOnly ? 'readonly' : '' }}>
            </div>
        </div>

        @unless($readOnly)
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-3">
                <button type="submit" name="action" value="save" class="eBtn eBtn-outline-blackish">{{ get_phrase('Save') }}</button>
                <button type="submit" name="action" value="continue" class="eBtn eBtn-primary">{{ get_phrase('Save & Continue') }}</button>
            </div>
        @endunless
    </form>
</div>

@push('scripts')
<script>
    document.getElementById('hasDisability')?.addEventListener('change', function () {
        document.getElementById('disabilityDetailsWrap').classList.toggle('d-none', !this.checked);
    });
    document.getElementById('religionSelect')?.addEventListener('change', function () {
        document.getElementById('religionOther').classList.toggle('d-none', this.value !== 'Other');
    });
</script>
@endpush
@endsection
