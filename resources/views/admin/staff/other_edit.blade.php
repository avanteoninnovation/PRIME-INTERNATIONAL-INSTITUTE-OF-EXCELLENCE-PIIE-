@extends('admin.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <h4>{{ get_phrase('Edit Other Staff Profile') }}</h4>
        <p>{{ get_phrase('Update the existing staff account and employment details. Access and password setup are managed separately.') }}</p>
        <p class="text-muted mb-0">{{ get_phrase('* Required fields') }}</p>
    </div></div>
</div>

<div class="eSection-wrap">
    @if (session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif

    <form method="POST" action="{{ route('admin.staff.other.update', $member->id) }}">
        @csrf
        @method('PUT')
        <h5>{{ get_phrase('Personal and contact details') }}</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="first-name">{{ get_phrase('First Name') }} *</label>
                <input id="first-name" class="form-control" name="first_name" value="{{ old('first_name', $member->first_name) }}" required>
                @error('first_name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="last-name">{{ get_phrase('Last Name') }} *</label>
                <input id="last-name" class="form-control" name="last_name" value="{{ old('last_name', $member->last_name) }}" required>
                @error('last_name')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="email">{{ get_phrase('Email') }} *</label>
                <input id="email" class="form-control" type="email" name="email" value="{{ old('email', $member->email) }}" required autocomplete="email">
                @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="phone">{{ get_phrase('Phone') }} *</label>
                <input id="phone" class="form-control" name="phone" value="{{ old('phone', $information['phone'] ?? '') }}" required>
                @error('phone')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="gender">{{ get_phrase('Gender') }} *</label>
                <select id="gender" class="form-control" name="gender" required>
                    <option value="">{{ get_phrase('Select') }}</option>
                    @foreach (['Male', 'Female', 'Other'] as $value)<option value="{{ $value }}" @selected(old('gender', $information['gender'] ?? '') === $value)>{{ $value }}</option>@endforeach
                </select>
                @error('gender')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="birthday">{{ get_phrase('Date of Birth') }} *</label>
                <input id="birthday" class="form-control" type="date" name="birthday" value="{{ old('birthday', $birthday) }}" required>
                @error('birthday')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 mb-3">
                <label for="nin">{{ get_phrase('NIN / Identity Number') }} *</label>
                <input id="nin" class="form-control" name="nin" value="{{ old('nin', $nin) }}" required autocomplete="off">
                @error('nin')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 mb-3">
                <label for="address">{{ get_phrase('Address') }} *</label>
                <textarea id="address" class="form-control" name="address" required>{{ old('address', $information['address'] ?? '') }}</textarea>
                @error('address')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <h5>{{ get_phrase('Employment details') }}</h5>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label for="department">{{ get_phrase('Department') }} *</label>
                <select id="department" class="form-control" name="department_id" required>
                    <option value="">{{ get_phrase('Select department') }}</option>
                    @foreach ($departments as $item)<option value="{{ $item->id }}" @selected((string) old('department_id', $member->department_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach
                </select>
                @error('department_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="designation">{{ get_phrase('Designation / Job Title') }} *</label>
                <select id="designation" class="form-control" name="designation_id" required>
                    <option value="">{{ get_phrase('Select designation') }}</option>
                    @foreach ($designations as $item)<option value="{{ $item->id }}" @selected((string) old('designation_id', $member->designation_id) === (string) $item->id)>{{ $item->name }}</option>@endforeach
                </select>
                @error('designation_id')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="employment-type">{{ get_phrase('Employment Type') }} *</label>
                <select id="employment-type" class="form-control" name="employment_type" required>
                    @foreach (['Full Time', 'Part Time', 'Casual'] as $value)<option value="{{ $value }}" @selected(old('employment_type', $member->employment_type) === $value)>{{ $value }}</option>@endforeach
                </select>
                @error('employment_type')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-3 mb-3">
                <label for="staff-status">{{ get_phrase('Staff Status') }} *</label>
                <select id="staff-status" class="form-control" name="staff_status" required>
                    @foreach (\App\Support\Staff\StaffStatus::ALL as $value)<option value="{{ $value }}" @selected(old('staff_status', $member->staff_status ?: 'active') === $value)>{{ ucwords(str_replace('_', ' ', $value)) }}</option>@endforeach
                </select>
                @error('staff_status')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="alert alert-info">
            {{ get_phrase('Qualification, professional registration, and experience records are retained in the staff profile system and are not changed by this form.') }}
            <span>{{ $qualifications->count() }} {{ get_phrase('qualifications') }}, {{ $registrations->count() }} {{ get_phrase('professional registrations') }}, {{ $experiences->count() }} {{ get_phrase('experience records') }}.</span>
        </div>
        @if ($qualifications->isNotEmpty() || $registrations->isNotEmpty() || $experiences->isNotEmpty())
            <div class="card mb-3">
                <div class="card-body">
                    <h6>{{ get_phrase('Existing professional information') }}</h6>
                    @if ($qualifications->isNotEmpty())
                        <strong>{{ get_phrase('Qualifications') }}</strong>
                        <ul class="mb-3">@foreach ($qualifications as $qualification)<li>{{ $qualification->qualification_name }} — {{ $qualification->institution }} ({{ $qualification->qualification_level }})</li>@endforeach</ul>
                    @endif
                    @if ($registrations->isNotEmpty())
                        <strong>{{ get_phrase('Professional registrations') }}</strong>
                        <ul class="mb-3">@foreach ($registrations as $registration)<li>{{ $registration->professional_body }}@if($registration->registration_number) — {{ $registration->registration_number }}@endif</li>@endforeach</ul>
                    @endif
                    @if ($experiences->isNotEmpty())
                        <strong>{{ get_phrase('Experience') }}</strong>
                        <ul class="mb-0">@foreach ($experiences as $experience)<li>{{ $experience->position }} — {{ $experience->employer }}</li>@endforeach</ul>
                    @endif
                </div>
            </div>
        @endif
        <div class="alert alert-secondary">
            {{ get_phrase('Custom roles and direct permissions are not changed here. Email changes invalidate password setup links sent to the previous address; use Account Access separately to issue a new link.') }}
        </div>
        <button class="btn btn-primary" type="submit">{{ get_phrase('Save Profile') }}</button>
        <a class="btn btn-light" href="{{ route('admin.rbac.staff.index') }}">{{ get_phrase('Cancel') }}</a>
    </form>
</div>
@endsection
