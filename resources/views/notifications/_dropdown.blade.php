@forelse($notifications as $notification)
    <li>
        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="notification-dropdown-item">
            @csrf
            <button type="submit" class="dropdown-item d-flex align-items-start gap-2 {{ $notification->read_at ? '' : 'fw-semibold' }}" style="white-space:normal;">
                @if(!$notification->read_at)
                    <span style="width:7px;height:7px;min-width:7px;border-radius:50%;background:#3a86ff;margin-top:6px;"></span>
                @else
                    <span style="width:7px;height:7px;min-width:7px;"></span>
                @endif
                <span>
                    <span class="d-block">{{ \Illuminate\Support\Str::limit($notification->title, 60) }}</span>
                    <span class="d-block text-muted small fw-normal">{{ $notification->created_at->diffForHumans() }}</span>
                </span>
            </button>
        </form>
    </li>
@empty
    <li><span class="dropdown-item text-muted">{{ get_phrase('No notifications yet') }}</span></li>
@endforelse
<li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item text-center" href="{{ route('notifications.index') }}">{{ get_phrase('See all notifications') }}</a></li>
