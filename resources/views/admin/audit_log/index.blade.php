@extends($isSuper ? 'superadmin.navigation' : 'admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Audit Logs') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('System') }}</a></li><li><a href="#">{{ get_phrase('Audit Logs') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <form method="GET" class="row g-2 align-items-end mb-3">
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('Search') }}</label>
            <input type="text" name="search" class="form-control eForm-control" style="width:180px" placeholder="{{ get_phrase('User, action, description...') }}" value="{{ $search }}">
        </div>
        @if($isSuper)
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('School') }}</label>
            <select name="school_id" class="form-control eForm-control" style="width:160px">
                <option value="">{{ get_phrase('All Schools') }}</option>
                @foreach($schools as $s)
                    <option value="{{ $s->id }}" {{ (string) $schoolId === (string) $s->id ? 'selected':'' }}>{{ $s->title }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('Module') }}</label>
            <select name="module" class="form-control eForm-control" style="width:150px">
                <option value="">{{ get_phrase('All Modules') }}</option>
                @foreach($modules as $mod)
                    <option value="{{ $mod }}" {{ $module==$mod ? 'selected':'' }}>{{ $mod }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('Action') }}</label>
            <select name="action" class="form-control eForm-control" style="width:140px">
                <option value="">{{ get_phrase('All Actions') }}</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}" {{ $action==$act ? 'selected':'' }}>{{ $act }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('Device') }}</label>
            <select name="device_type" class="form-control eForm-control" style="width:130px">
                <option value="">{{ get_phrase('All Devices') }}</option>
                @foreach($deviceTypes as $dt)
                    <option value="{{ $dt }}" {{ $deviceType==$dt ? 'selected':'' }}>{{ $dt }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('IP address') }}</label>
            <input type="text" name="ip_address" class="form-control eForm-control" style="width:130px" value="{{ $ipAddress }}">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('From') }}</label>
            <input type="date" name="date_from" class="form-control eForm-control" value="{{ $dateFrom }}">
        </div>
        <div class="col-auto">
            <label class="form-label small mb-1">{{ get_phrase('To') }}</label>
            <input type="date" name="date_to" class="form-control eForm-control" value="{{ $dateTo }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="eBtn eBtn-sm eBtn-primary">{{ get_phrase('Filter') }}</button>
            <a href="{{ route($isSuper ? 'superadmin.audit_log.index' : 'admin.audit_log.index') }}" class="eBtn eBtn-sm eBtn-secondary">{{ get_phrase('Reset') }}</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table eTable">
            <thead>
                <tr>
                    <th>{{ get_phrase('Time') }}</th>
                    <th>{{ get_phrase('User') }}</th>
                    <th>{{ get_phrase('Role') }}</th>
                    @if($isSuper)<th>{{ get_phrase('School') }}</th>@endif
                    <th>{{ get_phrase('Module') }}</th>
                    <th>{{ get_phrase('Action') }}</th>
                    <th>{{ get_phrase('Description') }}</th>
                    <th>{{ get_phrase('IP address') }}</th>
                    <th>{{ get_phrase('Device') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
            <tr>
                <td><small>{{ $log->created_at?->format('d M Y H:i:s') }}</small></td>
                <td>{{ $log->user_name ?? optional($log->user)->name ?? 'System' }}</td>
                <td><span class="badge bg-light text-dark">{{ $log->role_name ?? '—' }}</span></td>
                @if($isSuper)<td>{{ optional($log->school)->title ?? '—' }}</td>@endif
                <td><span class="badge bg-secondary">{{ $log->module }}</span></td>
                <td><span class="badge bg-{{ str_contains(strtolower($log->action),'delete')?'danger':(str_contains(strtolower($log->action),'creat')?'success':(str_contains(strtolower($log->action),'reject')||str_contains(strtolower($log->action),'deny')?'warning':'info')) }}">{{ $log->action }}</span></td>
                <td>{{ \Illuminate\Support\Str::limit($log->description, 80) }}</td>
                <td><small>{{ $log->ip_address }}</small></td>
                <td>
                    <small>
                        {{ $log->device_type }}
                        @if($log->browser)
                            / {{ $log->browser }}
                        @endif
                        @if($log->platform)
                            / {{ $log->platform }}
                        @endif
                    </small>
                </td>
                <td>
                    <a href="{{ route($isSuper ? 'superadmin.audit_log.show' : 'admin.audit_log.show', $log->id) }}" class="eBtn eBtn-xs eBtn-outline-primary">{{ get_phrase('View') }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $isSuper ? 10 : 9 }}" class="text-center text-muted py-4">{{ get_phrase('No audit logs') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div></div></div>
@endsection
