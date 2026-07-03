@extends('admin.navigation')
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
    <div class="row mb-3">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <input type="text" name="search" class="form-control eForm-control" style="width:200px" placeholder="{{ get_phrase('Search logs...') }}" value="{{ request('search') }}">
            <select name="module" class="form-control eForm-control" style="width:150px">
                <option value="">{{ get_phrase('All Modules') }}</option>
                @foreach($modules as $mod)
                    <option value="{{ $mod }}" {{ request('module')==$mod ? 'selected':'' }}>{{ $mod }}</option>
                @endforeach
            </select>
            <button type="submit" class="eBtn eBtn-sm eBtn-primary">{{ get_phrase('Filter') }}</button>
            <a href="{{ route('admin.audit_log.index') }}" class="eBtn eBtn-sm eBtn-secondary">{{ get_phrase('Reset') }}</a>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>{{ get_phrase('Time') }}</th><th>{{ get_phrase('User') }}</th><th>{{ get_phrase('School') }}</th><th>{{ get_phrase('Module') }}</th><th>{{ get_phrase('Action') }}</th><th>{{ get_phrase('Description') }}</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
            <tr>
                <td><small>{{ $log->created_at?->format('d M Y H:i:s') }}</small></td>
                <td>{{ optional($log->user)->name ?? 'System' }}</td>
                <td>{{ optional($log->school)->school_name ?? '—' }}</td>
                <td><span class="badge bg-secondary">{{ $log->module }}</span></td>
                <td><span class="badge bg-{{ str_contains(strtolower($log->action),'delete')?'danger':(str_contains(strtolower($log->action),'creat')?'success':'info') }}">{{ $log->action }}</span></td>
                <td>{{ $log->description }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No audit logs') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</div></div></div>
@endsection
