@extends('admin.navigation')

@section('content')
@php
    $inactive = $member->account_status === 'disable' || \App\Support\Staff\StaffStatus::blocksPortal($member->staff_status);
    $effectiveByModule = [];
    foreach ($effective as $key => $sources) {
        $effectiveByModule[$registry[$key]['module']][$key] = $sources;
    }
@endphp
<div class="rbac">
    @include('admin.rbac._header', ['title' => get_phrase('Manage access'), 'crumb' => $member->name, 'tab' => 'staff'])

    {{-- Identity (base role is shown, never edited here) --}}
    <div class="rbac-card">
        <h5>{{ get_phrase('Identity') }}</h5>
        <div class="row g-3">
            <div class="col-sm-6 col-lg-3"><div class="rbac-muted small">{{ get_phrase('Name') }}</div><div class="fw-semibold">{{ $member->name }}</div></div>
            <div class="col-sm-6 col-lg-3"><div class="rbac-muted small">{{ get_phrase('Email') }}</div><div class="text-break">{{ $member->email }}</div></div>
            <div class="col-sm-6 col-lg-2">
                <div class="rbac-muted small">{{ get_phrase('Base system role') }}</div>
                <div>{{ $baseRole }} @if ($isLegacyRole)<span class="rbac-badge rbac-badge-legacy">{{ get_phrase('Legacy') }}</span>@endif</div>
            </div>
            <div class="col-sm-6 col-lg-2"><div class="rbac-muted small">{{ get_phrase('School') }}</div><div>{{ $schoolName }}</div></div>
            <div class="col-sm-6 col-lg-2"><div class="rbac-muted small">{{ get_phrase('Status') }}</div><span class="rbac-badge {{ $inactive ? 'rbac-badge-inactive' : 'rbac-badge-active' }}">{{ $inactive ? get_phrase('Inactive') : get_phrase('Active') }}</span></div>
        </div>
        <p class="rbac-muted small mt-3 mb-0">{{ get_phrase('Custom roles and direct permissions add responsibilities on top of the base role. They never change the base role.') }}</p>
    </div>

    @unless ($manageable)
        <div class="rbac-card"><div class="rbac-warning"><i class="bi bi-shield-lock"></i> {{ get_phrase('School Administrators already have full access to this school, and nobody can change their own access.') }}</div></div>
    @endunless

    <div class="row g-3">
        <div class="col-lg-6">
            {{-- Custom roles --}}
            <div class="rbac-card h-100">
                <h5>{{ get_phrase('Custom roles') }}</h5>
                @forelse ($assigned as $role)
                    @php $roleActive = !$hasStatus || $role->is_active; @endphp
                    <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom flex-wrap">
                        <div>
                            <a href="{{ route('admin.rbac.roles.show', $role->id) }}" class="fw-semibold">{{ $role->name }}</a>
                            @unless ($roleActive)<span class="rbac-badge rbac-badge-inactive ms-1">{{ get_phrase('Inactive — grants nothing') }}</span>@endunless
                        </div>
                        @if ($manageable)
                            <form method="POST" action="{{ route('admin.rbac.staff.roles.remove', [$member->id, $role->id]) }}" onsubmit="return confirm(@json(get_phrase('Remove this role from the staff member?')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ get_phrase('Remove') }}</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="rbac-empty py-3"><i class="bi bi-diagram-3"></i>{{ get_phrase('No custom roles are assigned.') }}</div>
                @endforelse

                @if ($manageable)
                    @if ($available->isEmpty())
                        <p class="rbac-muted small mt-3 mb-0">{{ get_phrase('No other active custom roles are available.') }} <a href="{{ route('admin.rbac.roles.create') }}">{{ get_phrase('Create role') }}</a></p>
                    @else
                        <form method="POST" action="{{ route('admin.rbac.staff.roles.assign', $member->id) }}" class="d-flex gap-2 mt-3 flex-wrap"
                              data-confirm-sensitive="{{ get_phrase('This role includes sensitive permissions. Assign it to this staff member?') }}"
                              onsubmit="var o = this.querySelector('select').selectedOptions[0]; return !o || o.getAttribute('data-sensitive') !== '1' || confirm(this.getAttribute('data-confirm-sensitive'));">
                            @csrf
                            <label for="rbac-assign-role" class="visually-hidden">{{ get_phrase('Custom role') }}</label>
                            <select id="rbac-assign-role" name="staff_role_id" class="form-select eForm-select" style="max-width:320px" required>
                                @foreach ($available as $role)<option value="{{ $role->id }}" data-sensitive="{{ in_array((int) $role->id, $sensitiveRoleIds, true) ? 1 : 0 }}">{{ $role->name }}{{ in_array((int) $role->id, $sensitiveRoleIds, true) ? ' ⚠ ' . get_phrase('sensitive') : '' }}</option>@endforeach
                            </select>
                            <button type="submit" class="btn btn-primary">{{ get_phrase('Add role') }}</button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-lg-6">
            {{-- Direct permissions --}}
            <div class="rbac-card h-100">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h5 class="mb-0">{{ get_phrase('Direct permissions') }}</h5>
                    @if ($manageable && $direct)
                        <form method="POST" action="{{ route('admin.rbac.staff.permissions.clear', $member->id) }}" onsubmit="return confirm(@json(get_phrase('Remove ALL direct permissions from this staff member? Their base role and custom roles are not affected.')))">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ get_phrase('Remove all') }}</button>
                        </form>
                    @endif
                </div>
                @forelse ($direct as $key)
                    <div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom flex-wrap">
                        <div>
                            <span class="fw-semibold">{{ get_phrase($registry[$key]['label'] ?? $key) }}</span>
                            @if ($registry[$key]['sensitive'] ?? false)<span class="rbac-badge rbac-badge-sensitive ms-1">{{ get_phrase('Sensitive') }}</span>@endif
                            <div class="rbac-key">{{ $key }}</div>
                        </div>
                        @if ($manageable)
                            <form method="POST" action="{{ route('admin.rbac.staff.permissions.revoke', [$member->id, $key]) }}" onsubmit="return confirm(@json(get_phrase('Remove this permission? Actions that depend on it are removed too.')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ get_phrase('Remove') }}</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="rbac-empty py-3"><i class="bi bi-key"></i>{{ get_phrase('No additional permissions have been assigned.') }}</div>
                @endforelse
            </div>
        </div>
    </div>

    @if ($manageable)
        <details class="rbac-card">
            <summary class="fw-semibold" style="cursor:pointer">{{ get_phrase('Add direct permissions') }}</summary>
            <form method="POST" action="{{ route('admin.rbac.staff.permissions.grant', $member->id) }}" class="mt-3">
                @csrf
                @include('admin.rbac._permission_picker', ['groups' => $groups, 'selected' => [], 'requires' => $requires, 'exclude' => $direct, 'pickerId' => 'grant'])
                <div class="rbac-warning my-3">
                    <label class="d-flex gap-2 align-items-start mb-0" style="cursor:pointer">
                        <input type="checkbox" name="acknowledge_sensitive" value="1" style="width:1.1rem;height:1.1rem;margin-top:.15rem;accent-color:#b42318">
                        <span>{{ get_phrase('I confirm that any sensitive permissions I selected are intended for this staff member.') }}</span>
                    </label>
                </div>
                <div class="text-end"><button type="submit" class="btn btn-primary">{{ get_phrase('Grant selected permissions') }}</button></div>
            </form>
        </details>
    @endif

    {{-- Effective access: read-only explanation, never edited directly --}}
    <div class="rbac-card">
        <h5>{{ get_phrase('Effective access') }} <span class="rbac-muted small fw-normal">({{ count($effective) }} {{ get_phrase('permissions') }})</span></h5>
        <p class="rbac-muted small">{{ get_phrase('What this person can do and why. To change it, use custom roles or direct permissions above. Access is always limited to this school.') }}</p>
        <div class="d-flex flex-wrap gap-2 mb-3 small">
            <span class="rbac-badge rbac-badge-base">{{ get_phrase('Base role') }}</span>
            <span class="rbac-badge rbac-badge-role">{{ get_phrase('Custom role') }}</span>
            <span class="rbac-badge rbac-badge-direct">{{ get_phrase('Direct permission') }}</span>
        </div>
        @if (!$effective)
            <div class="rbac-empty"><i class="bi bi-shield"></i>{{ get_phrase('This staff member holds no delegated administrative permissions.') }}</div>
        @else
            <div class="rbac-grid">
                @foreach ($effectiveByModule as $module => $items)
                    <div class="rbac-module">
                        <div class="rbac-module-head"><strong>{{ get_phrase($modules[$module] ?? $module) }}</strong></div>
                        @foreach ($items as $key => $sources)
                            <div class="rbac-effective-row">
                                <div>
                                    <span class="rbac-label">{{ get_phrase($registry[$key]['label']) }}</span>
                                    @if ($registry[$key]['sensitive'])<span class="rbac-badge rbac-badge-sensitive ms-1">{{ get_phrase('Sensitive') }}</span>@endif
                                    <div class="rbac-key">{{ $key }}</div>
                                </div>
                                <div class="rbac-source-list">
                                    @foreach ($sources as $source)
                                        <span class="rbac-badge rbac-badge-{{ $source['type'] }}">{{ $source['label'] }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
