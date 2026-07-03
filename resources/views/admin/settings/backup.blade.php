@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Database Backup') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.settings.school') }}">{{ get_phrase('Settings') }}</a></li>
                <li><a href="#">{{ get_phrase('Backup') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

@include('admin.settings.partials.settings_nav', ['active' => 'backup'])
@if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> {{ session('error') }}</div>@endif

<div class="row">
    <div class="col-md-4 mb-3">
        <div class="eSection-wrap">
            <div class="p-3 border-bottom"><strong>{{ get_phrase('Create Backup') }}</strong></div>
            <div class="p-4 text-center">
                <div style="font-size:48px;color:#1a3a6b;margin-bottom:16px"><i class="bi bi-cloud-arrow-down"></i></div>
                <p style="font-size:12px;color:#6c757d;margin-bottom:20px">
                    {{ get_phrase('Creates a full SQL dump of the database and saves it to') }} <code>storage/backups/</code>
                </p>
                <form action="{{ route('admin.settings.backup.run') }}" method="POST">
                    @csrf
                    <button type="submit" class="eBtn eBtn-primary w-100" onclick="return confirm('{{ get_phrase('Create a database backup now?') }}')">
                        <i class="bi bi-cloud-arrow-down"></i> {{ get_phrase('Run Backup Now') }}
                    </button>
                </form>
                <div class="alert alert-warning mt-3" style="font-size:11px;text-align:left">
                    <i class="bi bi-exclamation-triangle"></i>
                    {{ get_phrase('Requires mysqldump to be installed on the server. If not available, a schema-only backup will be generated.') }}
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-8 mb-3">
        <div class="eSection-wrap">
            <div class="p-3 border-bottom"><strong>{{ get_phrase('Backup History') }}</strong></div>
            <div class="p-3">
            @if(empty($backups))
                <div class="text-center text-muted py-4">
                    <i class="bi bi-cloud-slash" style="font-size:2rem"></i>
                    <div class="mt-2" style="font-size:12px">{{ get_phrase('No backups found. Run your first backup.') }}</div>
                </div>
            @else
            <div class="table-responsive">
                <table class="table eTable" style="font-size:12px">
                    <thead><tr><th>{{ get_phrase('Filename') }}</th><th>{{ get_phrase('Size') }}</th><th>{{ get_phrase('Created') }}</th></tr></thead>
                    <tbody>
                    @foreach($backups as $b)
                    <tr>
                        <td><i class="bi bi-file-earmark-zip text-success"></i> {{ $b['name'] }}</td>
                        <td>{{ $b['size'] }}</td>
                        <td>{{ $b['date'] }}</td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            </div>
        </div>
    </div>
</div>
@endsection
