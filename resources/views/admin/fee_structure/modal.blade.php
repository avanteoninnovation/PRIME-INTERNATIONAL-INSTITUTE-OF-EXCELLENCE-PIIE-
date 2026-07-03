<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $structure ? route('admin.fee_structures.update', $structure->id) : route('admin.fee_structures.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Fee Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" value="{{ $structure->name ?? '' }}" required></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Fee Type') }}</label>
                    <select class="form-control eForm-control" name="fee_type">
                        @foreach(['tuition','registration','library','ict','medical','exam','hostel','other'] as $t)
                            <option value="{{ $t }}" {{ ($structure->fee_type ?? '') == $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
                        @endforeach
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Amount') }} *</label>
                    <input type="number" class="form-control eForm-control" name="amount" value="{{ $structure->amount ?? '' }}" min="0" step="0.01" required></div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Class (optional)') }}</label>
                    <select class="form-control eForm-control" name="class_id">
                        <option value="">{{ get_phrase('All Classes') }}</option>
                        @foreach($classes as $c)
                            <option value="{{ $c->id }}" {{ ($structure->class_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Programme (optional)') }}</label>
                    <select class="form-control eForm-control" name="programme_id">
                        <option value="">{{ get_phrase('All Programmes') }}</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ ($structure->programme_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select></div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7 form-check ms-3">
                    <input type="checkbox" class="form-check-input" name="is_mandatory" id="is_mandatory" {{ ($structure->is_mandatory ?? 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_mandatory">{{ get_phrase('Mandatory Fee') }}</label>
                </div>
                <div class="col-6 fpb-7 form-check ms-3">
                    <input type="checkbox" class="form-check-input" name="per_semester" id="per_semester" {{ ($structure->per_semester ?? 1) ? 'checked' : '' }}>
                    <label class="form-check-label" for="per_semester">{{ get_phrase('Charged Per Semester') }}</label>
                </div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $structure ? get_phrase('Update Fee') : get_phrase('Add Fee Structure') }}</button>
            </div>
        </div>
    </form>
</div>
