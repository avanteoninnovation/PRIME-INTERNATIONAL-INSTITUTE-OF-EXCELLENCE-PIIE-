<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $session ? route('admin.intake_sessions.update', $session->id) : route('admin.intake_sessions.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Session Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" value="{{ $session->name ?? '' }}" placeholder="e.g. August Intake 2026" required></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Open Date') }}</label>
                    <input type="date" class="form-control eForm-control" name="open_date" value="{{ $session->open_date ?? '' }}"></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Close Date') }}</label>
                    <input type="date" class="form-control eForm-control" name="close_date" value="{{ $session->close_date ?? '' }}"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Application Fee') }}</label>
                <input type="number" class="form-control eForm-control" name="application_fee" value="{{ $session->application_fee ?? 0 }}" min="0" step="0.01"></div>
            <div class="fpb-7 mt-2 form-check">
                <input type="checkbox" class="form-check-input" name="is_open" id="is_open" {{ ($session->is_open ?? 1) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_open">{{ get_phrase('Session is Open for Applications') }}</label>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $session ? get_phrase('Update Session') : get_phrase('Create Session') }}</button>
            </div>
        </div>
    </form>
</div>
