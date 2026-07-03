<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $lc ? route('admin.live_classes.update', $lc->id) : route('admin.live_classes.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Title') }} *</label>
                <input type="text" class="form-control eForm-control" name="title" value="{{ $lc->title ?? '' }}" required></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Description') }}</label>
                <textarea class="form-control eForm-control" name="description" rows="2">{{ $lc->description ?? '' }}</textarea></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Subject') }}</label>
                    <select class="form-control eForm-control" name="subject_id">
                        <option value="">{{ get_phrase('Select Subject') }}</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ ($lc->subject_id ?? '') == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Platform') }}</label>
                    <select class="form-control eForm-control" name="platform">
                        @foreach(['jitsi','zoom','google_meet','teams','other'] as $p)
                            <option value="{{ $p }}" {{ ($lc->platform ?? 'jitsi') == $p ? 'selected':'' }}>{{ ucwords(str_replace('_',' ',$p)) }}</option>
                        @endforeach
                    </select></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Join URL') }} <small class="text-muted">({{ get_phrase('auto-generated for Jitsi') }})</small></label>
                <input type="url" class="form-control eForm-control" name="join_url" value="{{ $lc->join_url ?? '' }}" placeholder="https://..."></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Scheduled At') }} *</label>
                    <input type="datetime-local" class="form-control eForm-control" name="scheduled_at" value="{{ $lc ? $lc->scheduled_at?->format('Y-m-d\TH:i') : '' }}" required></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Ends At') }}</label>
                    <input type="datetime-local" class="form-control eForm-control" name="ends_at" value="{{ $lc ? $lc->ends_at?->format('Y-m-d\TH:i') : '' }}"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Status') }}</label>
                <select class="form-control eForm-control" name="status">
                    @foreach(['scheduled','live','ended','cancelled'] as $st)
                        <option value="{{ $st }}" {{ ($lc->status ?? 'scheduled') == $st ? 'selected':'' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select></div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $lc ? get_phrase('Update') : get_phrase('Schedule Class') }}</button>
            </div>
        </div>
    </form>
</div>
