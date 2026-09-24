@extends('admin.navigation')

@section('content')
<div class="rbac">
    @include('admin.rbac._header', ['title' => get_phrase('Roles & Permissions'), 'tab' => 'roles'])

    <div class="rbac-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ get_phrase('Custom staff roles') }}</h5>
                <p class="rbac-muted mb-0">{{ get_phrase('A custom role is a bundle of permissions for a responsibility, such as Examinations Officer. Assigning it never changes the staff member\'s base role (Teacher, Accountant, …).') }}</p>
            </div>
            <a href="{{ route('admin.rbac.roles.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ get_phrase('Create role') }}</a>
        </div>
    </div>

    <div class="rbac-card">
        @if ($roles->isEmpty())
            <div class="rbac-empty">
                <i class="bi bi-diagram-3"></i>
                {{ get_phrase('No custom roles have been created for this school.') }}<br>
                <span class="small">{{ get_phrase('Start from a template below or create one from scratch.') }}</span>
            </div>
        @else
            <div class="table-responsive">
                <table class="rbac-table">
                    <thead>
                        <tr>
                            <th>{{ get_phrase('Custom role') }}</th>
                            <th>{{ get_phrase('Description') }}</th>
                            <th>{{ get_phrase('Users') }}</th>
                            <th>{{ get_phrase('Permissions') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th class="text-end">{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            @php $active = !$hasStatus || $role->is_active; @endphp
                            <tr>
                                <td><a href="{{ route('admin.rbac.roles.show', $role->id) }}" class="fw-semibold">{{ $role->name }}</a></td>
                                <td class="rbac-muted">{{ \Illuminate\Support\Str::limit($role->description, 90) ?: '—' }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>
                                    <span class="rbac-badge {{ $active ? 'rbac-badge-active' : 'rbac-badge-inactive' }}">{{ $active ? get_phrase('Active') : get_phrase('Inactive') }}</span>
                                </td>
                                <td>
                                    <div class="rbac-actions justify-content-end">
                                        <a href="{{ route('admin.rbac.roles.show', $role->id) }}" class="btn btn-sm btn-outline-secondary">{{ get_phrase('View') }}</a>
                                        <a href="{{ route('admin.rbac.roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary">{{ get_phrase('Edit') }}</a>
                                        <form method="POST" action="{{ route('admin.rbac.roles.duplicate', $role->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ get_phrase('Duplicate') }}</button>
                                        </form>
                                        @if ($hasStatus)
                                            <form method="POST" action="{{ route('admin.rbac.roles.status', $role->id) }}"
                                                  @if ($active) onsubmit="return confirm(@json(get_phrase('Deactivate this role? Staff who hold it immediately lose the permissions it grants.')))" @endif>
                                                @csrf
                                                <input type="hidden" name="active" value="{{ $active ? 0 : 1 }}">
                                                <button type="submit" class="btn btn-sm {{ $active ? 'btn-outline-warning' : 'btn-outline-success' }}">{{ $active ? get_phrase('Deactivate') : get_phrase('Activate') }}</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.rbac.roles.destroy', $role->id) }}" onsubmit="return confirm(@json(get_phrase('Delete this role permanently? This cannot be undone.')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" @if ($role->users_count > 0) disabled title="{{ get_phrase('This role is currently assigned to') }} {{ $role->users_count }} {{ get_phrase('staff members') }}" @endif>{{ get_phrase('Delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="rbac-card">
        <h5>{{ get_phrase('Start from a template') }}</h5>
        <p class="rbac-muted">{{ get_phrase('Templates only pre-fill the form. Review the permissions before saving; sensitive permissions are never pre-selected.') }}</p>
        <div class="rbac-actions">
            @foreach ($templates as $key => $template)
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.rbac.roles.create', ['template' => $key]) }}">{{ get_phrase($template['name']) }}</a>
            @endforeach
        </div>
    </div>
</div>
@endsection
