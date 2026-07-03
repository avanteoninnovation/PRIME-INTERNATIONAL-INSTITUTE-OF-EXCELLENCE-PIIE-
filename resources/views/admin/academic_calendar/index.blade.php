@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Academic Calendar') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Academic') }}</a></li><li><a href="#">{{ get_phrase('Calendar') }}</a></li></ul>
        </div>
        <div class="export-btn-area">
            <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.academic_calendar.open_modal') }}', '{{ get_phrase('Add Event') }}')">{{ get_phrase('Add Event') }}</a>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row">
    <div class="col-lg-8"><div class="eSection-wrap">
        <div id="calendar"></div>
    </div></div>
    <div class="col-lg-4"><div class="eSection-wrap">
        <h6 class="mb-3">{{ get_phrase('Upcoming Events') }}</h6>
        @forelse($events as $ev)
        <div class="d-flex align-items-start mb-3 gap-2">
            <div class="badge bg-{{ $ev->color ?? 'primary' }} mt-1 p-2">{{ $ev->event_date?->format('d') }}<br><small>{{ $ev->event_date?->format('M') }}</small></div>
            <div>
                <strong>{{ $ev->title }}</strong>
                <div><small class="text-muted">{{ ucfirst($ev->event_type) }}</small></div>
                @if($ev->description)<div><small>{{ Str::limit($ev->description,60) }}</small></div>@endif
            </div>
            <a href="{{ route('admin.academic_calendar.destroy', $ev->id) }}" class="ms-auto eBtn eBtn-sm eBtn-danger" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
        </div>
        @empty
        <p class="text-muted">{{ get_phrase('No events') }}</p>
        @endforelse
    </div></div>
</div>
@endsection
@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cal = new FullCalendar.Calendar(document.getElementById('calendar'), {
        initialView: 'dayGridMonth',
        headerToolbar: {left:'prev,next today',center:'title',right:'dayGridMonth,listWeek'},
        events: '{{ route('admin.academic_calendar.events_json') }}',
        eventClick: function(info){ alert(info.event.title + '\n' + info.event.extendedProps.description); }
    });
    cal.render();
});
</script>
@endpush
