<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $agent ? route('admin.admissions_agents.update', $agent->id) : route('admin.admissions_agents.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Agent Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" value="{{ $agent->name ?? '' }}" required></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Email') }}</label>
                    <input type="email" class="form-control eForm-control" name="email" value="{{ $agent->email ?? '' }}"></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Phone') }}</label>
                    <input type="text" class="form-control eForm-control" name="phone" value="{{ $agent->phone ?? '' }}"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Commission (%)') }}</label>
                <input type="number" class="form-control eForm-control" name="commission_pct" value="{{ $agent->commission_pct ?? 0 }}" min="0" max="100" step="0.01"></div>
            @if($agent)
            <div class="fpb-7 mt-2 form-check">
                <input type="checkbox" class="form-check-input" name="is_active" id="is_active" {{ $agent->is_active ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">{{ get_phrase('Active') }}</label>
            </div>
            @endif
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $agent ? get_phrase('Update Agent') : get_phrase('Add Agent') }}</button>
            </div>
        </div>
    </form>
</div>
