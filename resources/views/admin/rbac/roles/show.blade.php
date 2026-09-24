@extends('admin.navigation')

@section('content')
@php $active = !$hasStatus || $role->is_active; @endphp
<div class="rbac">
    @include('admin.rbac._header', ['title' => $role->name, 'crumb' => $role->name, 'tab' => 'roles'])

    <div class="rbac-card">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ $role->name }}
                    <span class="rbac-badge {{ $active ? 'rbac-badge-active' : 'rbac-badge-inactive' }} ms-1">{{ $active ? get_phrase('Active') : get_phrase('Inactive') }}</span>
                </h5>
                <p class="rbac-muted mb-0">{{ $role->description ?: get_phrase('No description.') }}</p>
                @unless ($active)
                    <p class="rbac-warning mt-2 mb-0"><i class="bi bi-pause-circle"></i> {{ get_phrase('This role is deactivated: it grants no permissions and cannot be newly assigned. Existing assignments are kept and take effect again if the role is activated.') }}</p>
                @endunless
            </div>
            <div class="rbac-actions">
                <a href="{{ route('admin.rbac.roles.edit', $role->id) }}" class="btn btn-outline-primary">{{ get_phrase('Edit') }}</a>
                <form method="POST" action="{{ route('admin.rbac.roles.duplicate', $role->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">{{ get_phrase('Duplicate') }}</button>
                </form>
            </div>
        </div>
    </div>

    <div class="rbac-card">
        <h5>{{ get_phrase('Permissions in this role') }}</h5>
        @if (!$groups)
            <div class="rbac-empty"><i class="bi bi-shield"></i>{{ get_phrase('This role has no permissions yet.') }}</div>
        @else
            <div class="rbac-grid">
                @foreach ($groups as $group)
                    <div class="rbac-module">
                        <div class="rbac-module-head"><strong>{{ get_phrase($group['label']) }}</strong></div>
                        @foreach ($group['permissions'] as $key => $definition)
                            <div class="rbac-perm">
                                <i class="bi bi-check2-circle text-success"></i>
                                <span>
                                    <span class="rbac-label">{{ get_phrase($definition['label']) }}</span>
                                    @if ($definition['sensitive'])<span class="rbac-badge rbac-badge-sensitive ms-1">{{ get_phrase('Sensitive') }}</span>@endif
                                    <span class="rbac-desc"><span class="rbac-key">{{ $key }}</span></span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rbac-card">
        <h5>{{ get_phrase('Staff with this role') }}</h5>
        @if ($staff->isEmpty())
            <div class="rbac-empty"><i class="bi bi-person"></i>{{ get_phrase('No staff members are assigned to this role.') }}</div>
        @else
            <div class="table-responsive">
                <table class="rbac-table">
                    <thead><tr><th>{{ get_phrase('Name') }}</th><th>{{ get_phrase('Email') }}</th><th>{{ get_phrase('Base role') }}</th><th class="text-end">{{ get_phrase('Actions') }}</th></tr></thead>
                    <tbody>
                        @foreach ($staff as $member)
                            <tr>
                                <td>{{ $member->name }}</td>
                                <td class="rbac-muted">{{ $member->email }}</td>
                                <td>{{ \App\Support\Roles\SystemRole::name((int) $member->role_id) }}</td>
                                <td class="text-end"><a href="{{ route('admin.rbac.staff.show', $member->id) }}" class="btn btn-sm btn-outline-primary">{{ get_phrase('Manage access') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
