@extends(\App\Support\Permissions\RoleNavigationLayout::name(auth()->user()))
@section('content')

<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Notifications') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Notifications') }}</a></li>
                    </ul>
                </div>
                <form method="POST" action="{{ route('notifications.read_all') }}">
                    @csrf
                    <button type="submit" class="eBtn eBtn-secondary">{{ get_phrase('Mark All Read') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            @forelse($notifications as $notification)
                <div class="d-flex justify-content-between align-items-start py-3 {{ !$loop->last ? 'border-bottom' : '' }}" style="{{ $notification->read_at ? '' : 'background:#f5f9ff;' }}">
                    <div class="pe-3">
                        <div class="d-flex align-items-center gap-2">
                            @if(!$notification->read_at)
                                <span class="badge bg-primary" style="width:8px;height:8px;padding:0;border-radius:50%;"></span>
                            @endif
                            <strong>{{ $notification->title }}</strong>
                        </div>
                        @if($notification->body)
                            <p class="mb-1 text-muted">{{ $notification->body }}</p>
                        @endif
                        <span class="text-muted small">{{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="flex-shrink-0">
                        @csrf
                        <button type="submit" class="eBtn eBtn-sm eBtn-primary">
                            {{ $notification->url ? get_phrase('View') : get_phrase('Mark Read') }}
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-muted text-center py-4 mb-0">{{ get_phrase('No notifications yet') }}</p>
            @endforelse
        </div>
        {{ $notifications->links() }}
    </div>
</div>
@endsection
