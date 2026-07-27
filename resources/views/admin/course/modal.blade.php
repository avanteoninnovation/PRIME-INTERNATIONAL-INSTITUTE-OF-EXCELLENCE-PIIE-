<div class="eoff-form">
    <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm"
          action="{{ $course ? route('admin.courses.update', $course->id) : route('admin.courses.store') }}">
        @csrf
        <div class="form-row">
            <div class="row">
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Course Code') }} *</label>
                    <input type="text" class="form-control eForm-control" name="code" value="{{ old('code', $course->code ?? '') }}" placeholder="e.g. BBA 1102" required maxlength="30">
                </div>
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Programme') }} *</label>
                    <select class="form-control eForm-control" name="programme_id" required>
                        <option value="">{{ get_phrase('— Select —') }}</option>
                        @foreach($programmes as $programme)
                            <option value="{{ $programme->id }}" {{ (old('programme_id', $course->programme_id ?? '') == $programme->id) ? 'selected' : '' }}>{{ $programme->code }} — {{ $programme->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="fpb-7 mt-2">
                <label class="eForm-label">{{ get_phrase('Course Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" value="{{ old('name', $course->name ?? '') }}" placeholder="e.g. Communication Skills" required>
            </div>
            <div class="row mt-2">
                <div class="col-4 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Credit') }} *</label>
                    <input type="number" class="form-control eForm-control" name="credits" value="{{ old('credits', $course->credits ?? 3) }}" min="1" max="10" required>
                </div>
                <div class="col-4 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Type') }} *</label>
                    <select class="form-control eForm-control" name="course_type" required>
                        @foreach(\App\Models\Subject::TYPES as $type)
                            <option value="{{ $type }}" {{ (old('course_type', $course->course_type ?? 'compulsory') == $type) ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Level') }} *</label>
                    <select class="form-control eForm-control" name="level" required>
                        @foreach(\App\Models\Programme::LEVELS as $lvl)
                            <option value="{{ $lvl }}" {{ (old('level', $course->level ?? '') == $lvl) ? 'selected' : '' }}>{{ $lvl }}</option>
                        @endforeach
                        @if($course && in_array($course->level, \App\Models\Programme::LEVELS_LEGACY, true))
                            <option value="{{ $course->level }}" selected>{{ $course->level }} ({{ get_phrase('legacy') }})</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-4 fpb-7">
                    <label class="eForm-label">{{ get_phrase('CATS Marks (max)') }} *</label>
                    <input type="number" class="form-control eForm-control" name="cats_marks" value="{{ old('cats_marks', $course->cats_marks ?? 30) }}" min="0" max="100" required>
                </div>
                <div class="col-4 fpb-7">
                    <label class="eForm-label">{{ get_phrase('EXAM Marks (max)') }} *</label>
                    <input type="number" class="form-control eForm-control" name="exam_marks" value="{{ old('exam_marks', $course->exam_marks ?? 70) }}" min="0" max="100" required>
                </div>
                <div class="col-4 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Pass Mark') }} *</label>
                    <input type="number" class="form-control eForm-control" name="pass_mark" value="{{ old('pass_mark', $course->pass_mark ?? 50) }}" min="0" max="100" required>
                </div>
            </div>
            <p class="text-muted mt-1" style="font-size: 12px;">{{ get_phrase('CATS Marks + EXAM Marks must add up to 100. Pass Mark is the minimum total mark required to pass.') }}</p>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $course ? get_phrase('Update Course') : get_phrase('Create Course') }}</button>
            </div>
        </div>
    </form>
</div>
