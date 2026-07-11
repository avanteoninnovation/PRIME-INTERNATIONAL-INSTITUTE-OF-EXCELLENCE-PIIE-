<div class="eoff-form">
    @php
        $routePrefix = request()->routeIs('teacher.*') ? 'teacher' : 'admin';
    @endphp
    <form method="POST" class="d-block ajaxForm"
          action="{{ $liveClass->id ? route($routePrefix . '.live_classes.update_legacy', $liveClass->id) : route($routePrefix . '.live_classes.store_legacy') }}">
        @csrf
        @include('admin.live_class._form')
        <div class="fpb-7 pt-3">
            <button class="btn-form" type="submit">{{ $liveClass->id ? get_phrase('Update') : get_phrase('Schedule Class') }}</button>
        </div>
    </form>
</div>
