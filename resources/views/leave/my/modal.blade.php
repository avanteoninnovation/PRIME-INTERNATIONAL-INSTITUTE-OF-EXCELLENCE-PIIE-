<div class="eoff-form">
    <form method="POST" class="d-block" action="{{ route('staff.leave.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Leave Type') }}</label>
                <select class="form-control eForm-control" name="leave_type_id">
                    <option value="">{{ get_phrase('Select Type') }}</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}">{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('From Date') }} *</label>
                    <input type="date" class="form-control eForm-control" name="from_date" required></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('To Date') }} *</label>
                    <input type="date" class="form-control eForm-control" name="to_date" required></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Reason') }} *</label>
                <textarea class="form-control eForm-control" name="reason" rows="3" required></textarea></div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Submit Leave') }}</button>
            </div>
        </div>
    </form>
</div>
