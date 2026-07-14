@php
    $isEdit = !empty($exam);
    $targetRoute = $isEdit ? route('teacher.online_exams.update', $exam->id) : route('teacher.online_exams.store');
@endphp

@if(!empty($structureLocked))
    <div class="alert alert-warning">
        {{ get_phrase('This exam structure is locked because attempts already exist. Structural fields are read-only.') }}
    </div>
@endif

@if(!empty($readinessErrors))
    <div class="alert alert-danger">
        <strong>{{ get_phrase('Publication readiness issues') }}:</strong>
        <ul class="mb-0 mt-2">
            @foreach($readinessErrors as $issue)
                <li>{{ $issue }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $targetRoute }}" class="row g-3">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="col-md-8">
        <label class="eForm-label">{{ get_phrase('Title') }}</label>
        <input type="text" class="form-control eForm-control" name="title" value="{{ old('title', $exam->title ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label class="eForm-label">{{ get_phrase('Exam Type') }}</label>
        <select class="form-select eForm-select" name="exam_type" required {{ !empty($structureLocked) ? 'disabled' : '' }}>
            @foreach(['cat' => 'CAT', 'midterm' => 'Midterm', 'final' => 'Final', 'quiz' => 'Quiz', 'assignment' => 'Assignment'] as $k => $v)
                <option value="{{ $k }}" {{ old('exam_type', $exam->exam_type ?? 'quiz') === $k ? 'selected' : '' }}>{{ $v }}</option>
            @endforeach
        </select>
        @if(!empty($structureLocked) && !empty($exam))
            <input type="hidden" name="exam_type" value="{{ $exam->exam_type }}">
        @endif
    </div>

    <div class="col-md-6">
        <label class="eForm-label">{{ get_phrase('Subject') }}</label>
        <select class="form-select eForm-select" name="subject_id" required {{ !empty($structureLocked) ? 'disabled' : '' }}>
            <option value="">{{ get_phrase('Select subject') }}</option>
            @foreach($subjects as $subject)
                <option value="{{ $subject->id }}" {{ (string) old('subject_id', $exam->subject_id ?? '') === (string) $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
            @endforeach
        </select>
        @if(!empty($structureLocked) && !empty($exam))
            <input type="hidden" name="subject_id" value="{{ $exam->subject_id }}">
        @endif
    </div>

    <div class="col-md-6">
        <label class="eForm-label">{{ get_phrase('Class') }}</label>
        <select class="form-select eForm-select" name="class_id" {{ !empty($structureLocked) ? 'disabled' : '' }}>
            <option value="">{{ get_phrase('Select class') }}</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ (string) old('class_id', $exam->class_id ?? '') === (string) $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
            @endforeach
        </select>
        @if(!empty($structureLocked) && !empty($exam))
            <input type="hidden" name="class_id" value="{{ $exam->class_id }}">
        @endif
    </div>

    <div class="col-md-6">
        <label class="eForm-label">{{ get_phrase('Academic Session') }}</label>
        <select class="form-select eForm-select" disabled>
            <option>{{ get_phrase('Using global active session') }}</option>
            @foreach($sessions as $session)
                <option>{{ $session->session_title }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="eForm-label">{{ get_phrase('Programme') }}</label>
        <input type="text" class="form-control eForm-control" value="{{ get_phrase('Not configured for online exams in this installation') }}" disabled>
    </div>

    <div class="col-md-3">
        <label class="eForm-label">{{ get_phrase('Start') }}</label>
        <input type="datetime-local" class="form-control eForm-control" name="start_datetime" value="{{ old('start_datetime', !empty($exam?->start_datetime) ? $exam->start_datetime->format('Y-m-d\\TH:i') : '') }}" required {{ !empty($structureLocked) ? 'disabled' : '' }}>
        @if(!empty($structureLocked) && !empty($exam?->start_datetime))
            <input type="hidden" name="start_datetime" value="{{ $exam->start_datetime->format('Y-m-d H:i:s') }}">
        @endif
    </div>

    <div class="col-md-3">
        <label class="eForm-label">{{ get_phrase('End') }}</label>
        <input type="datetime-local" class="form-control eForm-control" name="end_datetime" value="{{ old('end_datetime', !empty($exam?->end_datetime) ? $exam->end_datetime->format('Y-m-d\\TH:i') : '') }}" required {{ !empty($structureLocked) ? 'disabled' : '' }}>
        @if(!empty($structureLocked) && !empty($exam?->end_datetime))
            <input type="hidden" name="end_datetime" value="{{ $exam->end_datetime->format('Y-m-d H:i:s') }}">
        @endif
    </div>

    <div class="col-md-2">
        <label class="eForm-label">{{ get_phrase('Duration (min)') }}</label>
        <input type="number" min="1" class="form-control eForm-control" name="duration_mins" value="{{ old('duration_mins', $exam->duration_mins ?? 60) }}" required {{ !empty($structureLocked) ? 'readonly' : '' }}>
    </div>

    <div class="col-md-2">
        <label class="eForm-label">{{ get_phrase('Total Marks') }}</label>
        <input type="number" min="1" class="form-control eForm-control" name="total_marks" value="{{ old('total_marks', $exam->total_marks ?? 100) }}" required {{ !empty($structureLocked) ? 'readonly' : '' }}>
    </div>

    <div class="col-md-2">
        <label class="eForm-label">{{ get_phrase('Pass Mark') }}</label>
        <input type="number" min="0" class="form-control eForm-control" name="pass_mark" value="{{ old('pass_mark', $exam->pass_mark ?? 50) }}" required {{ !empty($structureLocked) ? 'readonly' : '' }}>
    </div>

    <div class="col-md-2">
        <label class="eForm-label">{{ get_phrase('Maximum Attempts') }}</label>
        <input type="number" min="1" max="20" class="form-control eForm-control" name="max_attempts" value="{{ old('max_attempts', $exam->max_attempts ?? 1) }}" required {{ !empty($structureLocked) ? 'readonly' : '' }}>
    </div>

    <div class="col-md-4">
        <label class="eForm-label">{{ get_phrase('Result Release Policy') }}</label>
        <select class="form-select eForm-select" name="result_release_policy" {{ !empty($structureLocked) ? 'disabled' : '' }}>
            @foreach(['immediate' => 'Immediate', 'after_exam_end' => 'After Exam End', 'manual' => 'Manual'] as $k => $v)
                <option value="{{ $k }}" {{ old('result_release_policy', $exam->result_release_policy ?? 'immediate') === $k ? 'selected' : '' }}>{{ get_phrase($v) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-12">
        <label class="eForm-label">{{ get_phrase('Instructions') }}</label>
        <textarea class="form-control eForm-control" name="instructions" rows="4">{{ old('instructions', $exam->instructions ?? '') }}</textarea>
    </div>

    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="shuffle_questions" id="shuffle_questions" value="1" {{ old('shuffle_questions', $exam->shuffle_questions ?? false) ? 'checked' : '' }} {{ !empty($structureLocked) ? 'disabled' : '' }}>
                <label class="form-check-label" for="shuffle_questions">{{ get_phrase('Shuffle Questions') }}</label>
                @if(!empty($structureLocked) && !empty($exam))
                    <input type="hidden" name="shuffle_questions" value="{{ (int) ($exam->shuffle_questions ?? false) }}">
                @endif
            </div>
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="shuffle_options" id="shuffle_options" value="1" {{ old('shuffle_options', $exam->shuffle_options ?? false) ? 'checked' : '' }} {{ !empty($structureLocked) ? 'disabled' : '' }}>
                <label class="form-check-label" for="shuffle_options">{{ get_phrase('Shuffle Options') }}</label>
                @if(!empty($structureLocked) && !empty($exam))
                    <input type="hidden" name="shuffle_options" value="{{ (int) ($exam->shuffle_options ?? false) }}">
                @endif
            </div>
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="allow_previous_navigation" id="allow_previous_navigation" value="1" {{ old('allow_previous_navigation', $exam->allow_previous_navigation ?? true) ? 'checked' : '' }} {{ !empty($structureLocked) ? 'disabled' : '' }}>
                <label class="form-check-label" for="allow_previous_navigation">{{ get_phrase('Allow Previous Navigation') }}</label>
                @if(!empty($structureLocked) && !empty($exam))
                    <input type="hidden" name="allow_previous_navigation" value="{{ (int) ($exam->allow_previous_navigation ?? true) }}">
                @endif
            </div>
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="auto_submit" id="auto_submit" value="1" {{ old('auto_submit', $exam->auto_submit ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="auto_submit">{{ get_phrase('Auto Submit') }}</label>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="row">
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="webcam_required" id="webcam_required" value="1" {{ old('webcam_required', $exam->webcam_required ?? false) ? 'checked' : '' }} {{ !empty($structureLocked) ? 'disabled' : '' }}>
                <label class="form-check-label" for="webcam_required">{{ get_phrase('Webcam Required') }}</label>
                @if(!empty($structureLocked) && !empty($exam))
                    <input type="hidden" name="webcam_required" value="{{ (int) ($exam->webcam_required ?? false) }}">
                @endif
            </div>
            <div class="col-md-3 form-check">
                <input class="form-check-input" type="checkbox" name="fullscreen_required" id="fullscreen_required" value="1" {{ old('fullscreen_required', $exam->fullscreen_required ?? false) ? 'checked' : '' }} {{ !empty($structureLocked) ? 'disabled' : '' }}>
                <label class="form-check-label" for="fullscreen_required">{{ get_phrase('Fullscreen Required') }}</label>
                @if(!empty($structureLocked) && !empty($exam))
                    <input type="hidden" name="fullscreen_required" value="{{ (int) ($exam->fullscreen_required ?? false) }}">
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 d-flex gap-2">
        <button class="eBtn eBtn-primary" type="submit">{{ $isEdit ? get_phrase('Update Exam') : get_phrase('Create Exam') }}</button>
        @if($isEdit)
            <a class="eBtn eBtn-secondary" href="{{ route('teacher.online_exams.show', $exam->id) }}">{{ get_phrase('View') }}</a>
            <a class="eBtn eBtn-secondary" href="{{ route('teacher.online_exams.questions.index', $exam->id) }}">{{ get_phrase('Manage Questions') }}</a>
        @endif
    </div>
</form>
