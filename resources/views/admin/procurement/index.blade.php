@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Procurement Requests') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Inventory') }}</a></li><li><a href="#">{{ get_phrase('Procurement') }}</a></li></ul>
        </div>
        <div class="export-btn-area d-flex gap-2">
            <a href="{{ route('admin.procurement.export', ['status' => $status]) }}" class="export_btn bg-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.procurement.open_modal') }}', '{{ get_phrase('New Request') }}')">{{ get_phrase('New Request') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Item') }}</th><th>{{ get_phrase('Quantity') }}</th><th>{{ get_phrase('Est. Cost') }}</th><th>{{ get_phrase('Requested By') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($requests as $i => $req)
            <tr>
                <td>{{ $requests->firstItem() + $i }}</td>
                <td><strong>{{ $req->title }}</strong><br><small class="text-muted">{{ $req->description }}</small></td>
                <td>{{ $req->quantity }}</td>
                <td>{{ $req->estimated_cost ? number_format($req->estimated_cost,2) : '—' }}</td>
                <td>{{ optional($req->requester)->name }}</td>
                <td><span class="badge bg-{{ $req->status=='approved'?'success':($req->status=='pending'?'warning':($req->status=='ordered'?'info':'danger')) }}">{{ ucfirst($req->status) }}</span></td>
                <td>
                    @if($req->status=='pending')
                        <a href="{{ route('admin.procurement.status', [$req->id,'approved']) }}" class="eBtn eBtn-sm eBtn-primary" onclick="return confirm('Approve?')"><i class="bi bi-check-circle"></i></a>
                        <a href="{{ route('admin.procurement.status', [$req->id,'rejected']) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Reject?')"><i class="bi bi-x-circle"></i></a>
                    @endif
                    @if($req->status=='approved')
                        <a href="{{ route('admin.procurement.status', [$req->id,'ordered']) }}" class="eBtn eBtn-sm eBtn-warning" onclick="return confirm('Mark as Ordered?')"><i class="bi bi-cart-check"></i></a>
                    @endif
                    <a href="{{ route('admin.procurement.destroy', $req->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('No procurement requests') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $requests->links() }}
</div></div></div>
@endsection
