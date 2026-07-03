@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Asset Categories') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.assets.index') }}">{{ get_phrase('Assets') }}</a></li><li><a href="#">{{ get_phrase('Categories') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.asset_categories.modal') }}', '{{ get_phrase('Add Category') }}')">{{ get_phrase('Add Category') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Category Name') }}</th><th>{{ get_phrase('Description') }}</th><th>{{ get_phrase('Assets Count') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($categories as $i => $c)
            <tr>
                <td>{{ $i+1 }}</td>
                <td><strong>{{ $c->name }}</strong></td>
                <td>{{ $c->description ?? '—' }}</td>
                <td><span class="badge bg-info">{{ $c->assets_count }}</span></td>
                <td>
                    <a href="{{ route('admin.asset_categories.destroy', $c->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">{{ get_phrase('No categories') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
