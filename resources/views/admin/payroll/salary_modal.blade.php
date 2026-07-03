<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('admin.salary_structures.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Staff Member') }} *</label>
                <select class="form-control eForm-control" name="user_id" required>
                    <option value="">{{ get_phrase('Select Staff') }}</option>
                    @foreach($staff as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Basic Salary') }} *</label>
                    <input type="number" class="form-control eForm-control" name="basic_salary" min="0" step="0.01" required></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Allowances') }}</label>
                    <input type="number" class="form-control eForm-control" name="allowances" value="0" min="0" step="0.01"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Effective From') }}</label>
                <input type="date" class="form-control eForm-control" name="effective_from" value="{{ date('Y-m-01') }}"></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Notes') }}</label>
                <textarea class="form-control eForm-control" name="notes" rows="2"></textarea></div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Save Salary Structure') }}</button>
            </div>
        </div>
    </form>
</div>
