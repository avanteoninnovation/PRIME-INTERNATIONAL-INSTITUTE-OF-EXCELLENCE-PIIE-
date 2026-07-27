@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Asset Management') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Inventory') }}</a></li><li><a href="#">{{ get_phrase('Assets') }}</a></li></ul>
        </div>
        <div class="export-btn-area d-flex gap-2">
            <a href="{{ route('admin.assets.export', ['search' => $search, 'category' => $category]) }}" class="export_btn bg-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
            <a href="{{ route('admin.asset_categories.index') }}" class="export_btn bg-secondary">{{ get_phrase('Categories') }}</a>
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.assets.open_modal') }}', '{{ get_phrase('Add Asset') }}')">{{ get_phrase('Add Asset') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Asset Name') }}</th><th>{{ get_phrase('Category') }}</th><th>{{ get_phrase('Serial No.') }}</th><th>{{ get_phrase('Purchase Date') }}</th><th>{{ get_phrase('Cost') }}</th><th>{{ get_phrase('Condition') }}</th><th>{{ get_phrase('Assigned To') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($assets as $i => $a)
            <tr>
                <td>{{ $assets->firstItem() + $i }}</td>
                <td><strong>{{ $a->name }}</strong></td>
                <td>{{ optional($a->category)->name ?? '—' }}</td>
                <td>{{ $a->serial_number ?? '—' }}</td>
                <td>{{ $a->purchase_date?->format('d M Y') }}</td>
                <td>{{ $a->purchase_cost ? number_format($a->purchase_cost,2) : '—' }}</td>
                <td><span class="badge bg-{{ $a->condition=='good'?'success':($a->condition=='fair'?'warning':'danger') }}">{{ ucfirst($a->condition ?? '—') }}</span></td>
                <td>{{ optional($a->assignedUser)->name ?? '—' }}</td>
                <td>
                    <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.assets.open_modal', ['id'=>$a->id]) }}', '{{ get_phrase('Edit Asset') }}')"><i class="bi bi-pencil"></i></a>
                    <a href="{{ route('admin.assets.destroy', $a->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-4">{{ get_phrase('No assets found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $assets->links() }}
</div></div></div>
@endsection
