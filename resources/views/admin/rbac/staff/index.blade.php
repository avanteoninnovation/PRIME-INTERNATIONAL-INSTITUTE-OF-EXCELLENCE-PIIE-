@extends('admin.navigation')

@section('content')
<div class="rbac">
    @include('admin.rbac._header', ['title' => get_phrase('Staff Access'), 'tab' => 'staff'])

    <div class="rbac-card">
        <form method="GET" action="{{ route('admin.rbac.staff.index') }}" class="rbac-filters" role="search">
            <div>
                <label for="rbac-q" class="eForm-label">{{ get_phrase('Search') }}</label>
                <input type="search" id="rbac-q" name="q" class="form-control eForm-control" value="{{ $filters['q'] ?? '' }}" placeholder="{{ get_phrase('Name, email or staff ID') }}">
            </div>
            <div>
                <label for="rbac-base" class="eForm-label">{{ get_phrase('Base role') }}</label>
                <select id="rbac-base" name="base_role" class="form-select eForm-select">
                    <option value="">{{ get_phrase('All') }}</option>
                    @foreach ($baseRoles as $id => $label)
                        <option value="{{ $id }}" @selected((string) ($filters['base_role'] ?? '') === (string) $id)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="rbac-custom" class="eForm-label">{{ get_phrase('Custom role') }}</label>
                <select id="rbac-custom" name="custom_role" class="form-select eForm-select">
                    <option value="">{{ get_phrase('All') }}</option>
                    @foreach ($customRoles as $role)
                        <option value="{{ $role->id }}" @selected((string) ($filters['custom_role'] ?? '') === (string) $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="rbac-status" class="eForm-label">{{ get_phrase('Status') }}</label>
                <select id="rbac-status" name="status" class="form-select eForm-select">
                    <option value="">{{ get_phrase('All') }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ get_phrase('Active') }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ get_phrase('Inactive') }}</option>
                </select>
            </div>
            <div class="rbac-actions">
                <button type="submit" class="btn btn-primary">{{ get_phrase('Filter') }}</button>
                <a href="{{ route('admin.rbac.staff.index') }}" class="btn btn-outline-secondary">{{ get_phrase('Reset') }}</a>
            </div>
        </form>
    </div>

    <div class="rbac-card">
        @if ($staff->isEmpty())
            <div class="rbac-empty"><i class="bi bi-people"></i>{{ get_phrase('No staff members match these filters.') }}</div>
        @else
            <div class="table-responsive">
                <table class="rbac-table">
                    <thead>
                        <tr>
                            <th>{{ get_phrase('Name') }}</th>
                            <th>{{ get_phrase('Staff ID') }}</th>
                            <th>{{ get_phrase('Base role') }}</th>
                            <th>{{ get_phrase('Custom roles') }}</th>
                            <th>{{ get_phrase('Direct permissions') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th class="text-end">{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($staff as $member)
                            @php
                                $inactive = $member->account_status === 'disable' || \App\Support\Staff\StaffStatus::blocksPortal($member->staff_status);
                                $isAdmin = (int) $member->role_id === 2;
                            @endphp
                            <tr>
                                <td>
                                    <span class="fw-semibold">{{ $member->name }}</span><br>
                                    <span class="rbac-muted small">{{ $member->email }}</span>
                                </td>
                                <td class="rbac-key">{{ $member->code ?: '—' }}</td>
                                <td>
                                    {{ \App\Support\Roles\SystemRole::name((int) $member->role_id) ?? get_phrase('Role') . ' ' . $member->role_id }}
                                    @if (in_array((int) $member->role_id, $legacyRoles, true))<span class="rbac-badge rbac-badge-legacy ms-1" title="{{ get_phrase('Legacy base role with historical meanings; unchanged.') }}">{{ get_phrase('Legacy') }}</span>@endif
                                </td>
                                <td>
                                    @forelse ($rolesByUser[$member->id] ?? [] as $assigned)
                                        <span class="rbac-badge {{ $assigned->is_active ? 'rbac-badge-role' : 'rbac-badge-inactive' }} mb-1">{{ $assigned->name }}</span>
                                    @empty
                                        <span class="rbac-muted">—</span>
                                    @endforelse
                                </td>
                                <td>{{ $directCounts[$member->id] ?? 0 }}</td>
                                <td><span class="rbac-badge {{ $inactive ? 'rbac-badge-inactive' : 'rbac-badge-active' }}">{{ $inactive ? get_phrase('Inactive') : get_phrase('Active') }}</span></td>
                                <td class="text-end">
                                    @if ($isAdmin)
                                        <span class="rbac-badge rbac-badge-admin">{{ get_phrase('Full access') }}</span>
                                    @else
                                        <a href="{{ route('admin.rbac.staff.show', $member->id) }}" class="btn btn-sm btn-outline-primary">{{ get_phrase('Manage access') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $staff->links() }}</div>
        @endif
    </div>
</div>
@endsection
