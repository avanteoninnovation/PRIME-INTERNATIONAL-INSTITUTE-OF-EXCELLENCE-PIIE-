<div class="fpb-7">
    <label for="first_name" class="eForm-label">{{ get_phrase('First Name') }}</label>
    <input type="text" class="form-control eForm-control" id="first_name" name="first_name" required>
</div>

<div class="fpb-7">
    <label for="last_name" class="eForm-label">{{ get_phrase('Last Name') }}</label>
    <input type="text" class="form-control eForm-control" id="last_name" name="last_name" required>
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
            <option value="{{ $department->id }}">{{ $department->name }}</option>
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
            <option value="{{ $designation->id }}">{{ $designation->name }}</option>
        @endforeach
    </select>
</div>

<div class="fpb-7">
    <label for="employment_type" class="eForm-label">{{ get_phrase('Employment Type') }}</label>
    <select name="employment_type" id="employment_type" class="form-select eForm-select eChoice-multiple-with-remove">
        <option value="">{{ get_phrase('Select employment type') }}</option>
        <option value="Full Time">{{ get_phrase('Full Time') }}</option>
        <option value="Part Time">{{ get_phrase('Part Time') }}</option>
        <option value="Casual">{{ get_phrase('Casual') }}</option>
    </select>
</div>

<div class="fpb-7">
    <label class="eForm-label d-block">{{ get_phrase('Portal Password') }}</label>
    <div class="d-flex align-items-center gr-15 mb-2">
        <div>
            <input type="radio" name="password_mode" value="auto" id="password_mode_auto" checked>
            <label for="password_mode_auto" class="mb-0">{{ get_phrase('Auto-generate secure password') }}</label>
        </div>
        <div>
            <input type="radio" name="password_mode" value="manual" id="password_mode_manual">
            <label for="password_mode_manual" class="mb-0">{{ get_phrase('Set password manually') }}</label>
        </div>
    </div>
    <input type="password" class="form-control eForm-control" id="password" name="password" placeholder="{{ get_phrase('Leave blank to auto-generate and email a temporary password') }}" disabled>
</div>

<script type="text/javascript">
    "use strict";
    (function () {
        var manual = document.getElementById('password_mode_manual');
        var auto = document.getElementById('password_mode_auto');
        var passwordField = document.getElementById('password');
        if (!manual || !auto || !passwordField) { return; }

        function syncPasswordField() {
            var isManual = manual.checked;
            passwordField.disabled = !isManual;
            if (!isManual) {
                passwordField.value = '';
            }
        }

        manual.addEventListener('change', syncPasswordField);
        auto.addEventListener('change', syncPasswordField);
        syncPasswordField();
    })();
</script>
