@extends('admin.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Programmes') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Academic') }}</a></li>
                        <li><a href="#">{{ get_phrase('Programmes') }}</a></li>
                    </ul>
                </div>
                <div class="export-btn-area">
                    <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.programmes.open_modal') }}', '{{ get_phrase('Add Programme') }}')">{{ get_phrase('Add Programme') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <div class="search-filter-area d-flex justify-content-between align-items-center flex-wrap gr-15 mb-3">
                <form action="{{ route('admin.programmes.index') }}">
                    <div class="search-input d-flex align-items-center">
                        <input type="text" name="search" value="{{ $search }}" placeholder="{{ get_phrase('Search programmes') }}" class="form-control eForm-control">
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table eTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ get_phrase('Code') }}</th>
                            <th>{{ get_phrase('Name') }}</th>
                            <th>{{ get_phrase('Level') }}</th>
                            <th>{{ get_phrase('Mode') }}</th>
                            <th>{{ get_phrase('Duration') }}</th>
                            <th>{{ get_phrase('Tuition Fee') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th>{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programmes as $i => $prog)
                        <tr>
                            <td>{{ $programmes->firstItem() + $i }}</td>
                            <td><strong>{{ $prog->code }}</strong></td>
                            <td>{{ $prog->name }}</td>
                            <td><span class="badge bg-primary">{{ $prog->level }}</span></td>
                            <td>{{ ucfirst($prog->mode) }}</td>
                            <td>{{ $prog->duration ?? '—' }}</td>
                            <td>{{ number_format($prog->tuition_fee, 0) }}</td>
                            <td>
                                @if($prog->is_active)
                                    <span class="badge bg-success">{{ get_phrase('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ get_phrase('Inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.programmes.open_modal', ['id' => $prog->id]) }}', '{{ get_phrase('Edit Programme') }}')"><i class="bi bi-pencil"></i></a>
                                <a href="{{ route('admin.programmes.toggle', $prog->id) }}" class="eBtn eBtn-sm eBtn-warning"><i class="bi bi-toggle-on"></i></a>
                                <a href="{{ route('admin.programmes.destroy', $prog->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('{{ get_phrase('Delete this programme?') }}')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">{{ get_phrase('No programmes found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $programmes->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
