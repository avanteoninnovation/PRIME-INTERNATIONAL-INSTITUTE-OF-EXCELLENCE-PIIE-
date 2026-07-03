@extends('admin.navigation')
@section('content')
<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
            <div class="d-flex flex-column">
                <h4>{{ get_phrase('HEI Admissions') }}</h4>
                <ul class="d-flex align-items-center eBreadcrumb-2">
                    <li><a href="#">{{ get_phrase('Home') }}</a></li>
                    <li><a href="#">{{ get_phrase('Admissions') }}</a></li>
                </ul>
            </div>
            <div class="export-btn-area d-flex gap-2">
                <a href="{{ route('admin.intake_sessions.index') }}" class="export_btn export_btn-outline">{{ get_phrase('Intake Sessions') }}</a>
                <a href="{{ route('admin.admissions_agents.index') }}" class="export_btn export_btn-outline">{{ get_phrase('Agents') }}</a>
                <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.hei_admissions.open_modal') }}', '{{ get_phrase('New Application') }}')">{{ get_phrase('Add Application') }}</a>
            </div>
        </div>
    </div></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="row mb-3">
    <div class="col-md-3"><div class="eCard text-center p-3"><h4 class="text-primary">{{ $admissions->total() }}</h4><small>{{ get_phrase('Total Applications') }}</small></div></div>
    <div class="col-md-3"><div class="eCard text-center p-3"><h4 class="text-success">{{ $admissions->where('status','enrolled')->count() }}</h4><small>{{ get_phrase('Enrolled') }}</small></div></div>
    <div class="col-md-3"><div class="eCard text-center p-3"><h4 class="text-warning">{{ $admissions->where('status','under_review')->count() }}</h4><small>{{ get_phrase('Under Review') }}</small></div></div>
    <div class="col-md-3"><div class="eCard text-center p-3"><h4 class="text-danger">{{ $admissions->where('status','rejected')->count() }}</h4><small>{{ get_phrase('Rejected') }}</small></div></div>
</div>

<div class="row">
    <div class="col-12"><div class="eSection-wrap">
        <form action="{{ route('admin.hei_admissions.index') }}" class="d-flex gap-2 mb-3 flex-wrap align-items-center">
            <input type="text" name="search" value="{{ $search }}" placeholder="{{ get_phrase('Search name, email, app#') }}" class="form-control eForm-control" style="max-width:240px">
            <select name="status" class="form-control eForm-control" style="max-width:160px">
                <option value="">{{ get_phrase('All Statuses') }}</option>
                @foreach(['submitted','under_review','accepted','rejected','enrolled','withdrawn'] as $s)
                    <option value="{{ $s }}" {{ $status == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select name="session_id" class="form-control eForm-control" style="max-width:180px">
                <option value="">{{ get_phrase('All Sessions') }}</option>
                @foreach($sessions as $sess)
                    <option value="{{ $sess->id }}" {{ $session_id == $sess->id ? 'selected' : '' }}>{{ $sess->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="eBtn">{{ get_phrase('Filter') }}</button>
        </form>

        <div class="table-responsive">
            <table class="table eTable">
                <thead><tr>
                    <th>#</th><th>{{ get_phrase('App No.') }}</th><th>{{ get_phrase('Applicant') }}</th>
                    <th>{{ get_phrase('Programme') }}</th><th>{{ get_phrase('Session') }}</th>
                    <th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Date') }}</th><th>{{ get_phrase('Actions') }}</th>
                </tr></thead>
                <tbody>
                @forelse($admissions as $i => $app)
                <tr>
                    <td>{{ $admissions->firstItem() + $i }}</td>
                    <td><small class="text-muted">{{ $app->app_number }}</small></td>
                    <td>
                        <strong>{{ $app->first_name }} {{ $app->last_name }}</strong><br>
                        <small class="text-muted">{{ $app->email }}</small>
                    </td>
                    <td>{{ $app->programme?->name ?? '—' }}</td>
                    <td>{{ $app->intakeSession?->name ?? '—' }}</td>
                    <td>
                        @php $colors = ['submitted'=>'info','under_review'=>'warning','accepted'=>'success','rejected'=>'danger','enrolled'=>'primary','withdrawn'=>'secondary']; @endphp
                        <span class="badge bg-{{ $colors[$app->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ',$app->status)) }}</span>
                    </td>
                    <td><small>{{ $app->created_at->format('d M Y') }}</small></td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.hei_admissions.open_modal', ['id' => $app->id]) }}', '{{ get_phrase('Edit Application') }}')"><i class="bi bi-pencil"></i></a>
                            @if($app->status === 'accepted')
                            <a href="{{ route('admin.hei_admissions.offer_letter', $app->id) }}" class="eBtn eBtn-sm eBtn-success" title="{{ get_phrase('Offer Letter') }}"><i class="bi bi-file-pdf"></i></a>
                            @endif
                            <form action="{{ route('admin.hei_admissions.status', $app->id) }}" method="POST" class="d-inline">
                                @csrf
                                <select name="status" class="form-select form-select-sm" style="width:130px" onchange="this.form.submit()">
                                    @foreach(['submitted','under_review','accepted','rejected','enrolled','withdrawn'] as $s)
                                        <option value="{{ $s }}" {{ $app->status == $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                    @endforeach
                                </select>
                            </form>
                            <a href="{{ route('admin.hei_admissions.destroy', $app->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No applications found') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $admissions->withQueryString()->links() }}</div>
    </div></div>
</div>
@endsection
