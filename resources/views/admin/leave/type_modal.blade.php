<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('admin.leave_types.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Type Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" placeholder="{{ get_phrase('e.g. Annual Leave') }}" required></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Max Days Per Year') }}</label>
                <input type="number" class="form-control eForm-control" name="max_days" value="21" min="1"></div>
            <div class="fpb-7 mt-2 form-check">
                <input type="checkbox" class="form-check-input" name="is_paid" id="is_paid_type" checked>
                <label class="form-check-label" for="is_paid_type">{{ get_phrase('Paid Leave') }}</label>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Save Leave Type') }}</button>
            </div>
        </div>
    </form>
</div>
