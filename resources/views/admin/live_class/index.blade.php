@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Live Classes') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Academic') }}</a></li><li><a href="#">{{ get_phrase('Live Classes') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.live_classes.open_modal') }}', '{{ get_phrase('Schedule Class') }}')">{{ get_phrase('Schedule Class') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Title') }}</th><th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Teacher') }}</th><th>{{ get_phrase('Scheduled') }}</th><th>{{ get_phrase('Platform') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($classes as $i => $lc)
            <tr>
                <td>{{ $classes->firstItem() + $i }}</td>
                <td><strong>{{ $lc->title }}</strong></td>
                <td>{{ optional($lc->subject)->name ?? '—' }}</td>
                <td>{{ optional($lc->teacher)->name ?? '—' }}</td>
                <td>{{ $lc->scheduled_at?->format('d M Y H:i') }}</td>
                <td><span class="badge bg-info">{{ ucfirst($lc->platform) }}</span></td>
                <td><span class="badge bg-{{ $lc->status=='live'?'danger':($lc->status=='scheduled'?'warning':'secondary') }}">{{ ucfirst($lc->status) }}</span></td>
                <td>
                    @if($lc->join_url)
                    <a href="{{ $lc->join_url }}" target="_blank" class="eBtn eBtn-sm eBtn-primary"><i class="bi bi-camera-video"></i> {{ get_phrase('Join') }}</a>
                    @endif
                    <a href="javascript:;" class="eBtn eBtn-sm eBtn-warning" onclick="rightModal('{{ route('admin.live_classes.open_modal', ['id'=>$lc->id]) }}', '{{ get_phrase('Edit') }}')"><i class="bi bi-pencil"></i></a>
                    <a href="{{ route('admin.live_classes.destroy', $lc->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No live classes scheduled') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $classes->links() }}
</div></div></div>
@endsection
