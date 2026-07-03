<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('admin.graduation.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Student') }} *</label>
                <select class="form-control eForm-control" name="student_id" required>
                    <option value="">{{ get_phrase('Select Student') }}</option>
                    @foreach($students as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Programme') }}</label>
                <select class="form-control eForm-control" name="programme_id">
                    <option value="">{{ get_phrase('Select Programme') }}</option>
                    @foreach($programmes as $p)
                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('GPA') }}</label>
                    <input type="number" class="form-control eForm-control" name="gpa" step="0.01" min="0" max="4" placeholder="0.00–4.00"></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Graduation Year') }}</label>
                    <input type="number" class="form-control eForm-control" name="graduation_year" value="{{ date('Y') }}" min="2000"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Notes') }}</label>
                <textarea class="form-control eForm-control" name="notes" rows="2"></textarea></div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Submit Application') }}</button>
            </div>
        </div>
    </form>
</div>
