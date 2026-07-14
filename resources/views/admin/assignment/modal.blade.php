<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $assignment ? route('admin.assignments.update', $assignment->id) : route('admin.assignments.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Title') }} *</label>
                <input type="text" class="form-control eForm-control" name="title" value="{{ $assignment->title ?? '' }}" required></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Description') }}</label>
                <textarea class="form-control eForm-control" name="description" rows="3">{{ $assignment->description ?? '' }}</textarea></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Subject') }}
                        <a href="javascript:;" class="ms-1" title="{{ get_phrase('Add Subject') }}" onclick="rightModal('{{ route('admin.subject.open_modal') }}', '{{ get_phrase('Create Subject') }}')">
                            <i class="bi bi-plus-circle"></i>
                        </a>
                    </label>
                    <select class="form-control eForm-control" name="subject_id">
                        <option value="">{{ get_phrase('Select Subject') }}</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" {{ ($assignment->subject_id ?? '') == $s->id ? 'selected':'' }}>{{ $s->name }}</option>
                        @endforeach
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Due Date') }} *</label>
                    <input type="date" class="form-control eForm-control" name="due_date" value="{{ $assignment ? $assignment->due_date?->format('Y-m-d') : '' }}" required></div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Total Marks') }}</label>
                    <input type="number" class="form-control eForm-control" name="total_marks" value="{{ $assignment->total_marks ?? 100 }}" min="1"></div>
                <div class="col-6 fpb-7 form-check mt-4 ms-3">
                    <input type="checkbox" class="form-check-input" name="allow_file" id="allow_file" {{ ($assignment->allow_file ?? true) ? 'checked':'' }}>
                    <label class="form-check-label" for="allow_file">{{ get_phrase('Allow File Upload') }}</label>
                </div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $assignment ? get_phrase('Update') : get_phrase('Create Assignment') }}</button>
            </div>
        </div>
    </form>
</div>
