<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $event ? route('admin.academic_calendar.update', $event->id) : route('admin.academic_calendar.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Event Title') }} *</label>
                <input type="text" class="form-control eForm-control" name="title" value="{{ $event->title ?? '' }}" required></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Event Type') }}</label>
                <select class="form-control eForm-control" name="event_type">
                    @foreach(['holiday','exam','lecture','meeting','sports','cultural','other'] as $t)
                        <option value="{{ $t }}" {{ ($event->event_type ?? '') == $t ? 'selected':'' }}>{{ ucfirst($t) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Start Date') }} *</label>
                    <input type="date" class="form-control eForm-control" name="event_date" value="{{ $event ? $event->event_date?->format('Y-m-d') : '' }}" required></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('End Date') }}</label>
                    <input type="date" class="form-control eForm-control" name="end_date" value="{{ $event ? $event->end_date?->format('Y-m-d') : '' }}"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Description') }}</label>
                <textarea class="form-control eForm-control" name="description" rows="3">{{ $event->description ?? '' }}</textarea></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Color') }}</label>
                    <select class="form-control eForm-control" name="color">
                        @foreach(['primary'=>'Blue','success'=>'Green','danger'=>'Red','warning'=>'Yellow','info'=>'Teal','secondary'=>'Grey'] as $val=>$label)
                            <option value="{{ $val }}" {{ ($event->color ?? 'primary') == $val ? 'selected':'' }}>{{ $label }}</option>
                        @endforeach
                    </select></div>
                <div class="col-6 fpb-7 form-check mt-4 ms-3">
                    <input type="checkbox" class="form-check-input" name="is_public" id="is_public" {{ ($event->is_public ?? true) ? 'checked':'' }}>
                    <label class="form-check-label" for="is_public">{{ get_phrase('Show to Students') }}</label>
                </div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $event ? get_phrase('Update Event') : get_phrase('Add Event') }}</button>
            </div>
        </div>
    </form>
</div>
