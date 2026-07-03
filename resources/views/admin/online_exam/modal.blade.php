<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm"
          action="{{ $exam ? route('admin.online_exams.update', $exam->id) : route('admin.online_exams.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Exam Title') }} *</label>
                <input type="text" class="form-control eForm-control" name="title" value="{{ $exam->title ?? '' }}" required></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Subject') }}</label>
                <select class="form-control eForm-control" name="subject_id">
                    <option value="">{{ get_phrase('All Subjects') }}</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}" {{ ($exam->subject_id ?? '') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Duration (minutes)') }}</label>
                    <input type="number" class="form-control eForm-control" name="duration_minutes" value="{{ $exam->duration_minutes ?? 60 }}" min="5"></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Pass Mark (%)') }}</label>
                    <input type="number" class="form-control eForm-control" name="pass_mark" value="{{ $exam->pass_mark ?? 50 }}" min="0" max="100"></div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Start Date/Time') }}</label>
                    <input type="datetime-local" class="form-control eForm-control" name="start_datetime" value="{{ $exam ? $exam->start_datetime?->format('Y-m-d\TH:i') : '' }}"></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('End Date/Time') }}</label>
                    <input type="datetime-local" class="form-control eForm-control" name="end_datetime" value="{{ $exam ? $exam->end_datetime?->format('Y-m-d\TH:i') : '' }}"></div>
            </div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Instructions') }}</label>
                <textarea class="form-control eForm-control" name="instructions" rows="3">{{ $exam->instructions ?? '' }}</textarea></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7 form-check ms-3">
                    <input type="checkbox" class="form-check-input" name="shuffle_questions" id="shuffle_q" {{ ($exam->shuffle_questions ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="shuffle_q">{{ get_phrase('Shuffle Questions') }}</label>
                </div>
                <div class="col-6 fpb-7 form-check ms-3">
                    <input type="checkbox" class="form-check-input" name="show_result" id="show_r" {{ ($exam->show_result ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="show_r">{{ get_phrase('Show Result After') }}</label>
                </div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $exam ? get_phrase('Update Exam') : get_phrase('Create Exam') }}</button>
            </div>
        </div>
    </form>
</div>
