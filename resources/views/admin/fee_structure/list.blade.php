@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Fee Structures') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Home') }}</a></li><li><a href="#">{{ get_phrase('Accounting') }}</a></li><li><a href="#">{{ get_phrase('Fee Structures') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.fee_structures.open_modal') }}', '{{ get_phrase('Add Fee Structure') }}')">{{ get_phrase('Add Fee') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Name') }}</th><th>{{ get_phrase('Type') }}</th><th>{{ get_phrase('Amount') }}</th><th>{{ get_phrase('Mandatory') }}</th><th>{{ get_phrase('Per Semester') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($structures as $i => $fs)
            <tr>
                <td>{{ $structures->firstItem() + $i }}</td>
                <td><strong>{{ $fs->name }}</strong></td>
                <td><span class="badge bg-info">{{ ucfirst($fs->fee_type) }}</span></td>
                <td>{{ number_format($fs->amount, 2) }}</td>
                <td><span class="badge bg-{{ $fs->is_mandatory ? 'danger' : 'secondary' }}">{{ $fs->is_mandatory ? get_phrase('Yes') : get_phrase('No') }}</span></td>
                <td><span class="badge bg-{{ $fs->per_semester ? 'primary' : 'secondary' }}">{{ $fs->per_semester ? get_phrase('Yes') : get_phrase('No') }}</span></td>
                <td>
                    <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.fee_structures.open_modal', ['id' => $fs->id]) }}', '{{ get_phrase('Edit Fee') }}')"><i class="bi bi-pencil"></i></a>
                    <a href="{{ route('admin.fee_structures.destroy', $fs->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('No fee structures found') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $structures->links() }}
</div></div></div>
@endsection
