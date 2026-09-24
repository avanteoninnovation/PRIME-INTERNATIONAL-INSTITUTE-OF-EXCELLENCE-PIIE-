@extends('admin.navigation')

@section('content')
<div class="rbac">
    @include('admin.rbac._header', [
        'title' => $role ? get_phrase('Edit role') : get_phrase('Create role'),
        'crumb' => $role ? $role->name : get_phrase('New role'),
        'tab' => 'roles',
    ])

    <form method="POST" action="{{ $role ? route('admin.rbac.roles.update', $role->id) : route('admin.rbac.roles.store') }}">
        @csrf
        @if ($role) @method('PUT') @endif

        <div class="rbac-card">
            <div class="row g-3">
                <div class="col-md-5">
                    <label for="rbac-name" class="eForm-label">{{ get_phrase('Role name') }} <span class="text-danger">*</span></label>
                    <input type="text" id="rbac-name" name="name" class="form-control eForm-control" maxlength="100" required value="{{ $name }}" placeholder="{{ get_phrase('e.g. Examinations Officer') }}">
                    <small class="rbac-muted">{{ get_phrase('Names a responsibility, not a base role. Staff keep their base role (Teacher, Accountant, …).') }}</small>
                </div>
                <div class="col-md-7">
                    <label for="rbac-description" class="eForm-label">{{ get_phrase('Description') }}</label>
                    <textarea id="rbac-description" name="description" class="form-control eForm-control" rows="2" maxlength="255">{{ $description }}</textarea>
                </div>
            </div>
        </div>

        <div class="rbac-card">
            <h5>{{ get_phrase('Permissions') }}</h5>
            <p class="rbac-muted">{{ get_phrase('Choosing an action also includes the view it needs. Permissions marked School Admin only can never be delegated.') }}</p>
            @include('admin.rbac._permission_picker', ['groups' => $groups, 'selected' => $selected, 'requires' => $requires, 'pickerId' => 'role'])
        </div>

        <div class="rbac-card rbac-danger-zone">
            <div class="rbac-warning mb-3">
                <i class="bi bi-exclamation-triangle"></i>
                {{ get_phrase('Sensitive permissions (payment settings, payroll, publishing results, the website, …) must be chosen deliberately. If you add any, confirm below.') }}
            </div>
            <label class="d-flex gap-2 align-items-start" style="cursor:pointer">
                <input type="checkbox" name="acknowledge_sensitive" value="1" style="width:1.1rem;height:1.1rem;margin-top:.15rem;accent-color:#b42318" @checked(old('acknowledge_sensitive'))>
                <span>{{ get_phrase('I confirm that the sensitive permissions I selected are intended for this role.') }}</span>
            </label>
        </div>

        <div class="rbac-actions justify-content-end mb-4">
            <a href="{{ $role ? route('admin.rbac.roles.show', $role->id) : route('admin.rbac.roles.index') }}" class="btn btn-outline-secondary">{{ get_phrase('Cancel') }}</a>
            <button type="submit" class="btn btn-primary">{{ $role ? get_phrase('Save changes') : get_phrase('Create role') }}</button>
        </div>
    </form>
</div>
@endsection
