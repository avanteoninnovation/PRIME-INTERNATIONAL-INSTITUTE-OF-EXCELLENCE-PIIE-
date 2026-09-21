@php
    $notificationUser = auth()->user();
    $notificationItems = $notificationUser
        ? \App\Models\OnlineExamUserNotification::forUser((int) $notificationUser->school_id, (int) $notificationUser->id)->latest()->limit(8)->get()
        : collect();
    $notificationUnread = $notificationItems->whereNull('read_at')->count();
@endphp
<div class="online-exam-notification-menu dropdown ms-2">
    <button class="btn btn-link position-relative p-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ get_phrase('Notifications') }}">
        <i class="bi bi-bell fs-5"></i>
        @if($notificationUnread > 0)<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $notificationUnread }}</span>@endif
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0" style="min-width:320px;max-width:380px">
        <div class="px-3 py-2 border-bottom fw-semibold">{{ get_phrase('Online Exam Notifications') }}</div>
        @forelse($notificationItems as $notification)
            <a class="dropdown-item text-wrap py-2 {{ $notification->read_at ? '' : 'bg-light' }}" href="{{ route('online_exam.notifications.read', $notification->id) }}">
                <div class="fw-semibold">{{ $notification->title }}</div>
                <small>{{ $notification->message }}</small>
                <div class="text-muted small">{{ optional($notification->created_at)->diffForHumans() }}</div>
            </a>
        @empty
            <div class="px-3 py-3 text-muted">{{ get_phrase('No notifications') }}</div>
        @endforelse
    </div>
</div>
