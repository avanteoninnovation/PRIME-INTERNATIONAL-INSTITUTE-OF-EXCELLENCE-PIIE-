<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $liveClass->id ? route('admin.live_classes.update_legacy', $liveClass->id) : route('admin.live_classes.store_legacy') }}">
        @csrf
        @include('admin.live_class._form')
        <div class="fpb-7 pt-3">
            <button class="btn-form" type="submit">{{ $liveClass->id ? get_phrase('Update') : get_phrase('Schedule Class') }}</button>
        </div>
    </form>
</div>
