<div class="eoff-form">
    <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.subject.update', ['id' => $subject->id]) }}">
        @csrf
        <div class="form-row">
            @if($isHigherEducation)
                <div class="mb-3">
                    <label for="programme_id_on_edit" class="eForm-label">{{ get_phrase('Programme') }} <span class="text-danger" aria-hidden="true">*</span></label>
                    <select name="programme_id" id="programme_id_on_edit" class="form-select eForm-select eChoice-multiple-with-remove" required>
                        <option value="">{{ get_phrase('Select a Programme') }}</option>
                        @foreach($programmes as $programme)<option value="{{ $programme->id }}" @selected((string)$programme->id === (string)$subject->programme_id)>{{ $programme->code }} — {{ $programme->name }}</option>@endforeach
                    </select>
                    <div class="form-text">{{ get_phrase('This catalogue association does not change any Programme Study Plan placements.') }}</div>
                </div>
            @elseif($isMixed)
                <p class="form-text">{{ get_phrase('Choose either a Class for a school Subject or a Programme for a higher-education Course Unit.') }}</p>
                <div class="mb-3">
                    <label for="class_id_on_edit" class="eForm-label">{{ get_phrase('Class') }}</label>
                    <select name="class_id" id="class_id_on_edit" class="form-select eForm-select eChoice-multiple-with-remove">
                        <option value="">{{ get_phrase('No Class association') }}</option>
                        @foreach($classes as $class)<option value="{{ $class->id }}" @selected((string)$class->id === (string)$subject->class_id)>{{ $class->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="programme_id_on_edit" class="eForm-label">{{ get_phrase('Programme') }}</label>
                    <select name="programme_id" id="programme_id_on_edit" class="form-select eForm-select eChoice-multiple-with-remove">
                        <option value="">{{ get_phrase('No Programme association') }}</option>
                        @foreach($programmes as $programme)<option value="{{ $programme->id }}" @selected((string)$programme->id === (string)$subject->programme_id)>{{ $programme->code }} — {{ $programme->name }}</option>@endforeach
                    </select>
                </div>
            @else
                <div class="mb-3">
                    <label for="class_id_on_edit" class="eForm-label">{{ get_phrase('Class') }} <span class="text-danger" aria-hidden="true">*</span></label>
                    <select name="class_id" id="class_id_on_edit" class="form-select eForm-select eChoice-multiple-with-remove" required>
                        <option value="">{{ get_phrase('Select a Class') }}</option>
                        @foreach($classes as $class)<option value="{{ $class->id }}" @selected((string)$class->id === (string)$subject->class_id)>{{ $class->name }}</option>@endforeach
                    </select>
                </div>
            @endif

            <div class="mb-3">
                <label for="course-unit-name-edit" class="eForm-label">{{ get_phrase($isHigherEducation ? 'Course Unit Name' : ($isMixed ? 'Subject / Course Unit Name' : 'Subject Name')) }} <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" class="form-control eForm-control" value="{{ $subject->name }}" id="course-unit-name-edit" name="name" maxlength="255" required>
            </div>
            <div class="mb-3">
                <label for="course-unit-code-edit" class="eForm-label">{{ get_phrase($isHigherEducation ? 'Course Unit Code' : 'Subject / Course Unit Code') }} <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" class="form-control eForm-control" value="{{ $subject->code }}" id="course-unit-code-edit" name="code" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" aria-describedby="course-unit-code-edit-help" required>
                <div id="course-unit-code-edit-help" class="form-text">{{ get_phrase('Use a unique code within this institution.') }}</div>
            </div>
            <div class="pt-2">
                <button class="btn-form" type="submit">{{ get_phrase($isHigherEducation ? 'Update Course Unit' : ($isMixed ? 'Update Subject / Course Unit' : 'Update Subject')) }}</button>
            </div>
        </div>
    </form>
</div>

<script type="text/javascript">
    "use strict";
    $(document).ready(function () { $(".eChoice-multiple-with-remove").select2(); });
</script>
