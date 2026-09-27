@extends('admin.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12"><h4>{{ get_phrase('Add Other Staff') }}</h4><p>{{ get_phrase('Add institutional staff whose main responsibility is not listed above.') }}</p><p class="text-muted mb-0">{{ get_phrase('* Required fields') }}</p></div></div></div>
<div class="eSection-wrap"><form method="POST" action="{{ route('admin.staff.other.store') }}">@csrf
    <h5>{{ get_phrase('Personal and contact details') }}</h5><div class="row">
        <div class="col-md-6 mb-3"><label>{{ get_phrase('First Name') }} *</label><input class="form-control" name="first_name" value="{{ old('first_name') }}" required></div>
        <div class="col-md-6 mb-3"><label>{{ get_phrase('Last Name') }} *</label><input class="form-control" name="last_name" value="{{ old('last_name') }}" required></div>
        <div class="col-md-6 mb-3"><label>{{ get_phrase('Email') }} *</label><input class="form-control" type="email" name="email" value="{{ old('email') }}" required></div>
        <div class="col-md-6 mb-3"><label>{{ get_phrase('Phone') }} *</label><input class="form-control" name="phone" value="{{ old('phone') }}" required></div>
        <div class="col-md-4 mb-3"><label>{{ get_phrase('Gender') }} *</label><select class="form-control" name="gender" required><option value="">{{ get_phrase('Select') }}</option>@foreach(['Male','Female','Other'] as $v)<option @selected(old('gender')===$v)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-4 mb-3"><label>{{ get_phrase('Date of birth (Optional)') }}</label><input class="form-control" type="date" name="birthday" value="{{ old('birthday') }}"></div>
        <div class="col-md-4 mb-3"><label>{{ get_phrase('NIN / Identity number') }} *</label><input class="form-control" name="nin" value="{{ old('nin') }}" required></div>
        <div class="col-12 mb-3"><label>{{ get_phrase('Address (Optional)') }}</label><textarea class="form-control" name="address">{{ old('address') }}</textarea></div>
    </div>
    <h5>{{ get_phrase('Employment details') }}</h5><div class="row">
        <div class="col-md-4 mb-3"><label>{{ get_phrase('Department (Optional)') }}</label><select class="form-control" name="department_id"><option value="">{{ get_phrase('Select department') }}</option>@foreach($departments as $item)<option value="{{ $item->id }}" @selected(old('department_id')==$item->id)>{{ $item->name }}</option>@endforeach</select></div>
        <div class="col-md-4 mb-3"><label>{{ get_phrase('Designation / Job Title') }} *</label><select class="form-control" name="designation_id" required><option value="">{{ get_phrase('Select designation') }}</option>@foreach($designations as $item)<option value="{{ $item->id }}" @selected(old('designation_id')==$item->id)>{{ $item->name }}</option>@endforeach</select><a href="{{ url('admin/designation') }}">{{ get_phrase('Manage Designations') }}</a></div>
        <div class="col-md-4 mb-3"><label>{{ get_phrase('Employment Type') }} *</label><select class="form-control" name="employment_type" required>@foreach(['Full Time','Part Time','Casual'] as $v)<option @selected(old('employment_type','Full Time')===$v)>{{ $v }}</option>@endforeach</select></div>
        <div class="col-md-4 mb-3"><label>{{ get_phrase('Staff Status') }} *</label><select class="form-control" name="staff_status" required>@foreach(['active','on_leave','suspended','inactive','terminated'] as $v)<option value="{{ $v }}" @selected(old('staff_status','active')===$v)>{{ ucwords(str_replace('_',' ',$v)) }}</option>@endforeach</select></div>
    </div>
    <h5>{{ get_phrase('Qualification (optional)') }}</h5>
    <p class="text-muted">{{ get_phrase('Qualification details are optional. If you add a qualification, complete the qualification level, qualification/award, and institution. Otherwise, leave all three fields blank.') }}</p>
    <div class="row">
        <div class="col-md-4 mb-3">
            <label for="qualification-level">{{ get_phrase('Qualification Level') }}</label>
            <input id="qualification-level" class="form-control" name="qualifications[0][qualification_level]" value="{{ old('qualifications.0.qualification_level') }}" placeholder="{{ get_phrase("Bachelor's Degree") }}">
            @error('qualifications.0.qualification_level')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="qualification-name">{{ get_phrase('Qualification / Award') }}</label>
            <input id="qualification-name" class="form-control" name="qualifications[0][qualification_name]" value="{{ old('qualifications.0.qualification_name') }}" placeholder="{{ get_phrase('Bachelor of Business Administration') }}">
            @error('qualifications.0.qualification_name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4 mb-3">
            <label for="qualification-institution">{{ get_phrase('Institution') }}</label>
            <input id="qualification-institution" class="form-control" name="qualifications[0][institution]" value="{{ old('qualifications.0.institution') }}" placeholder="{{ get_phrase('Makerere University') }}">
            @error('qualifications.0.institution')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
    </div>
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
    <button class="btn btn-primary" type="submit">{{ get_phrase('Create Other Staff') }}</button> <a class="btn btn-light" href="{{ route('admin.staff.add') }}">{{ get_phrase('Cancel') }}</a>
</form></div>
@endsection
