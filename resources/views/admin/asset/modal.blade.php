<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $asset ? route('admin.assets.update', $asset->id) : route('admin.assets.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Asset Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" value="{{ $asset->name ?? '' }}" required></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Category') }}</label>
                    <select class="form-control eForm-control" name="category_id">
                        <option value="">{{ get_phrase('Uncategorized') }}</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" {{ ($asset->category_id ?? '') == $c->id ? 'selected':'' }}>{{ $c->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Serial Number') }}</label>
                    <input type="text" class="form-control eForm-control" name="serial_number" value="{{ $asset->serial_number ?? '' }}"></div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Purchase Date') }}</label>
                    <input type="date" class="form-control eForm-control" name="purchase_date" value="{{ $asset ? $asset->purchase_date?->format('Y-m-d') : '' }}"></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Purchase Cost') }}</label>
                    <input type="number" class="form-control eForm-control" name="purchase_cost" value="{{ $asset->purchase_cost ?? '' }}" step="0.01"></div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Condition') }}</label>
                    <select class="form-control eForm-control" name="condition">
                        @foreach(['good','fair','poor','damaged'] as $cond)
                            <option value="{{ $cond }}" {{ ($asset->condition ?? 'good') == $cond ? 'selected':'' }}>{{ ucfirst($cond) }}</option>
                        @endforeach
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Status') }}</label>
                    <select class="form-control eForm-control" name="status">
                        @foreach(['available','assigned','maintenance','disposed'] as $st)
                            <option value="{{ $st }}" {{ ($asset->status ?? 'available') == $st ? 'selected':'' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Assign To') }}</label>
                <select class="form-control eForm-control" name="assigned_to">
                    <option value="">{{ get_phrase('Unassigned') }}</option>
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}" {{ ($asset->assigned_to ?? '') == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Notes') }}</label>
                <textarea class="form-control eForm-control" name="notes" rows="2">{{ $asset->notes ?? '' }}</textarea></div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $asset ? get_phrase('Update Asset') : get_phrase('Add Asset') }}</button>
            </div>
        </div>
    </form>
</div>
