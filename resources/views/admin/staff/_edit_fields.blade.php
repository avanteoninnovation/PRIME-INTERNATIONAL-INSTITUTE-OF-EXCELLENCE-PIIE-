<div class="fpb-7">
    <label for="staff_number" class="eForm-label">{{ get_phrase('Staff Number') }}</label>
    <input type="text" class="form-control eForm-control" id="staff_number" value="{{ $user->code }}" readonly disabled>
</div>

<div class="fpb-7">
    <label for="first_name" class="eForm-label">{{ get_phrase('First Name') }}</label>
    <input type="text" class="form-control eForm-control" id="first_name" name="first_name" value="{{ $user->first_name }}" required>
</div>

<div class="fpb-7">
    <label for="last_name" class="eForm-label">{{ get_phrase('Last Name') }}</label>
    <input type="text" class="form-control eForm-control" id="last_name" name="last_name" value="{{ $user->last_name }}" required>
</div>

<div class="fpb-7">
    <label for="department_id" class="eForm-label">
        {{ get_phrase('Department') }}
        <a href="javascript:;" class="ms-1" title="{{ get_phrase('Add Department') }}" onclick="rightModal('{{ route('admin.department.open_modal') }}', '{{ get_phrase('Create Department') }}')">
            <i class="bi bi-plus-circle"></i>
        </a>
    </label>
    <select name="department_id" id="department_id" class="form-select eForm-select eChoice-multiple-with-remove">
        <option value="">{{ get_phrase('Select a department') }}</option>
        @foreach($departments as $department)
            <option value="{{ $department->id }}" {{ $user->department_id == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
        @endforeach
    </select>
</div>

<div class="fpb-7">
    <label for="designation_id" class="eForm-label">
        {{ get_phrase('Designation') }}
        <a href="javascript:;" class="ms-1" title="{{ get_phrase('Add Designation') }}" onclick="rightModal('{{ route('admin.designation.open_modal') }}', '{{ get_phrase('Create Designation') }}')">
            <i class="bi bi-plus-circle"></i>
        </a>
    </label>
    <select name="designation_id" id="designation_id" class="form-select eForm-select eChoice-multiple-with-remove">
        <option value="">{{ get_phrase('Select a designation') }}</option>
        @foreach($designations as $designation)
            <option value="{{ $designation->id }}" {{ $user->designation_id == $designation->id ? 'selected' : '' }}>{{ $designation->name }}</option>
        @endforeach
    </select>
</div>

<div class="fpb-7">
    <label for="employment_type" class="eForm-label">{{ get_phrase('Employment Type') }}</label>
    <select name="employment_type" id="employment_type" class="form-select eForm-select eChoice-multiple-with-remove">
        <option value="">{{ get_phrase('Select employment type') }}</option>
        @foreach(['Full Time', 'Part Time', 'Casual'] as $type)
            <option value="{{ $type }}" {{ $user->employment_type == $type ? 'selected' : '' }}>{{ get_phrase($type) }}</option>
        @endforeach
    </select>
</div>

<div class="fpb-7">
    <label for="staff_status" class="eForm-label">{{ get_phrase('Staff Status') }}</label>
    <select name="staff_status" id="staff_status" class="form-select eForm-select eChoice-multiple-with-remove">
        @foreach(['active' => 'Active', 'suspended' => 'Suspended', 'inactive' => 'Inactive'] as $value => $label)
            <option value="{{ $value }}" {{ ($user->staff_status ?: 'active') == $value ? 'selected' : '' }}>{{ get_phrase($label) }}</option>
        @endforeach
    </select>
</div>
