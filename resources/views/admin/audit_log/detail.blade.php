@php($isSuper = auth()->user()->role_id == 1)
@extends($isSuper ? 'superadmin.navigation' : 'admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Audit Log Detail') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route($isSuper ? 'superadmin.audit_log.index' : 'admin.audit_log.index') }}">{{ get_phrase('Audit Logs') }}</a></li>
                <li><a href="#">#{{ $log->id }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <table class="table eTable table-borderless">
        <tr><th style="width:220px">{{ get_phrase('Time') }}</th><td>{{ $log->created_at?->format('d M Y H:i:s') }}</td></tr>
        <tr><th>{{ get_phrase('User') }}</th><td>{{ $log->user_name ?? optional($log->user)->name ?? 'System' }} (#{{ $log->user_id }})</td></tr>
        <tr><th>{{ get_phrase('Role') }}</th><td>{{ $log->role_name ?? '—' }}</td></tr>
        <tr><th>{{ get_phrase('School') }}</th><td>{{ optional($log->school)->title ?? '—' }}</td></tr>
        <tr><th>{{ get_phrase('Module') }}</th><td>{{ $log->module }}</td></tr>
        <tr><th>{{ get_phrase('Page/Route') }}</th><td>{{ $log->route_name ?? '—' }}</td></tr>
        <tr><th>{{ get_phrase('URL') }}</th><td class="text-break">{{ $log->url ?? '—' }}</td></tr>
        <tr><th>{{ get_phrase('Method') }}</th><td>{{ $log->method ?? '—' }}</td></tr>
        <tr><th>{{ get_phrase('Action') }}</th><td><span class="badge bg-info">{{ $log->action }}</span> <span class="text-muted">({{ $log->event_type }})</span></td></tr>
        <tr><th>{{ get_phrase('Description') }}</th><td>{{ $log->description }}</td></tr>
        <tr><th>{{ get_phrase('Record type') }}</th><td>{{ $log->record_type ?? '—' }}</td></tr>
        <tr><th>{{ get_phrase('Record ID') }}</th><td>{{ $log->record_id ?? '—' }}</td></tr>
        <tr><th>{{ get_phrase('IP address') }}</th><td>{{ $log->ip_address }}</td></tr>
        <tr><th>{{ get_phrase('Device') }}</th><td>{{ $log->device_type }}</td></tr>
        <tr><th>{{ get_phrase('Browser') }}</th><td>{{ $log->browser }}</td></tr>
        <tr><th>{{ get_phrase('Operating system') }}</th><td>{{ $log->platform }}</td></tr>
        <tr><th>{{ get_phrase('User agent') }}</th><td class="text-break small text-muted">{{ $log->user_agent }}</td></tr>
        <tr><th>{{ get_phrase('Status') }}</th><td>{{ $log->status ?? '—' }}</td></tr>
    </table>

    @if($log->old_values || $log->new_values)
    <div class="row mt-3">
        <div class="col-md-6">
            <h6>{{ get_phrase('Previous values') }}</h6>
            <pre class="bg-light p-2 rounded small">{{ $log->old_values ? json_encode($log->old_values, JSON_PRETTY_PRINT) : '—' }}</pre>
        </div>
        <div class="col-md-6">
            <h6>{{ get_phrase('New values') }}</h6>
            <pre class="bg-light p-2 rounded small">{{ $log->new_values ? json_encode($log->new_values, JSON_PRETTY_PRINT) : '—' }}</pre>
        </div>
    </div>
    @endif

    <a href="{{ route($isSuper ? 'superadmin.audit_log.index' : 'admin.audit_log.index') }}" class="eBtn eBtn-sm eBtn-secondary mt-3">{{ get_phrase('Back') }}</a>
</div></div></div>
@endsection
