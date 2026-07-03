@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('API Settings') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.settings.school') }}">{{ get_phrase('Settings') }}</a></li>
                <li><a href="#">{{ get_phrase('API') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

@include('admin.settings.partials.settings_nav', ['active' => 'api'])
@if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>@endif

<div class="row">
    <div class="col-md-7 mb-3">
        <div class="eSection-wrap">
            <div class="p-3 border-bottom"><strong>{{ get_phrase('Your API Key') }}</strong></div>
            <div class="p-4">
                <div class="alert alert-info" style="font-size:11px">
                    <i class="bi bi-info-circle"></i>
                    {{ get_phrase('Use this key in the') }} <code>Authorization: Bearer {key}</code> {{ get_phrase('header for all API requests.') }}
                </div>
                <div class="fg">
                    <label class="eForm-label">{{ get_phrase('API Key') }}</label>
                    <div class="d-flex gap-2">
                        <input type="text" id="apiKeyInput" class="form-control eForm-control" value="{{ $api_key }}" readonly style="font-family:monospace;font-size:12px">
                        <button class="eBtn eBtn-secondary" onclick="copyApiKey()" title="{{ get_phrase('Copy') }}"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="fg">
                    <label class="eForm-label">{{ get_phrase('Base URL') }}</label>
                    <input type="text" class="form-control eForm-control" value="{{ $api_base }}" readonly style="font-family:monospace;font-size:12px">
                </div>
                <form action="{{ route('admin.settings.api.regenerate') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="eBtn eBtn-danger" onclick="return confirm('{{ get_phrase('Regenerating will invalidate the current key. Continue?') }}')">
                        <i class="bi bi-arrow-clockwise"></i> {{ get_phrase('Regenerate API Key') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5 mb-3">
        <div class="eSection-wrap">
            <div class="p-3 border-bottom"><strong>{{ get_phrase('API Endpoints') }}</strong></div>
            <div class="p-3">
                @php $endpoints = [
                    ['GET',  '/api/students',    'List all students'],
                    ['GET',  '/api/staff',        'List all staff'],
                    ['GET',  '/api/programmes',   'List programmes'],
                    ['GET',  '/api/attendance',   'Attendance records'],
                    ['GET',  '/api/finance',      'Fee & payment data'],
                    ['POST', '/api/marks',        'Submit marks'],
                ]; @endphp
                @foreach($endpoints as $ep)
                <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="font-size:11px">
                    <span class="badge bg-{{ $ep[0]==='GET'?'info':'warning' }}" style="font-size:9px;min-width:36px">{{ $ep[0] }}</span>
                    <code style="flex:1;font-size:10px">{{ $ep[1] }}</code>
                    <span style="color:#6c757d">{{ get_phrase($ep[2]) }}</span>
                </div>
                @endforeach
                <div class="alert alert-warning mt-3" style="font-size:10px">
                    <i class="bi bi-cone"></i> {{ get_phrase('Full REST API documentation coming soon.') }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyApiKey() {
    const input = document.getElementById('apiKeyInput');
    input.select();
    document.execCommand('copy');
    alert('{{ get_phrase("API key copied to clipboard") }}');
}
</script>
@endsection
