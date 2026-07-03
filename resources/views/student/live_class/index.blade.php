@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Live Classes') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Academic') }}</a></li><li><a href="#">{{ get_phrase('Live Classes') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="row g-3">
        @forelse($classes as $lc)
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6>{{ $lc->title }}</h6>
                        <span class="badge bg-{{ $lc->status=='live'?'danger':($lc->status=='scheduled'?'warning':'secondary') }}">{{ ucfirst($lc->status) }}</span>
                    </div>
                    <div class="text-muted small">{{ optional($lc->subject)->name }}</div>
                    @if($lc->scheduled_at)
                    <div class="mt-2"><i class="bi bi-calendar-event"></i> {{ $lc->scheduled_at->format('d M Y H:i') }}</div>
                    @endif
                    @if($lc->description)<p class="small mt-2">{{ Str::limit($lc->description,80) }}</p>@endif
                    @if($lc->join_url && in_array($lc->status,['scheduled','live']))
                        <a href="{{ $lc->join_url }}" target="_blank" class="eBtn eBtn-sm eBtn-{{ $lc->status=='live'?'danger':'primary' }} w-100 mt-2">
                            <i class="bi bi-camera-video"></i> {{ $lc->status=='live' ? get_phrase('Join Now') : get_phrase('Join Class') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="col-12 text-center text-muted py-4">{{ get_phrase('No live classes scheduled') }}</div>
        @endforelse
    </div>
</div></div></div>
@endsection
