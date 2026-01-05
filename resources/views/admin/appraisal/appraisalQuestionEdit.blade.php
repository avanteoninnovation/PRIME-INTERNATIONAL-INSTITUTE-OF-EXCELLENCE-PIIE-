<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.appraisal.appraisalQuestionUpdate', ['id' => $appraisal->id]) }}">
    @csrf
    <div class="fpb-7">
        <label for="class_id" class="eForm-label">{{ get_phrase('Class') }}</label>
        <select name="class_id" id="class_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
            <option value="">{{ get_phrase('Select a class') }}</option>
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" {{ $class->id == $appraisal->class_id ? 'selected' : '' }}>{{ $class->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="fpb-7">
        <label for="teacher_id[]" class="eForm-label">{{ get_phrase('Teachers') }}</label>
        <select name="teacher_id[]" id="teacher_id" class="form-select eForm-select eChoice-multiple-with-remove multiple_teacher" multiple required>
            <option value="">{{ get_phrase('Select Teachers') }}</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" {{ in_array($teacher->id, json_decode($appraisal->teacher_id)) ? 'selected' : '' }}>{{ $teacher->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="fpb-7">
        <label class="eForm-label">{{ get_phrase('Ans Type') }}</label>
        <!-- Answer Type Options -->
        <div class="form-check">
            <input class="form-check-input" type="radio" name="ans_type" id="mcq" value="mcq" {{ $appraisal->ans_type == 'mcq' ? 'checked' : '' }} required>
            <label class="form-check-label" for="mcq">
                {{ get_phrase('MCQ') }} <span class="apprisal_ans_type">({{ get_phrase('Excellent') }}, {{ get_phrase('Good') }}, {{ get_phrase('Average') }}, {{ get_phrase('Poor') }})</span>
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="radio" name="ans_type" id="rating" value="rating" {{ $appraisal->ans_type == 'rating' ? 'checked' : '' }}>
            <label class="form-check-label" for="rating">
                {{ get_phrase('Rating') }} <span class="apprisal_ans_type">(⭐⭐⭐⭐⭐)</span>
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="radio" name="ans_type" id="binary" value="binary" {{ $appraisal->ans_type == 'binary' ? 'checked' : '' }}>
            <label class="form-check-label" for="binary">
                {{ get_phrase('Binary') }} <span class="apprisal_ans_type">(✅ {{ get_phrase('Yes') }} / ❌ {{ get_phrase('No') }})</span>
            </label>
        </div>

        <div class="form-check">
            <input class="form-check-input" type="radio" name="ans_type" id="text" value="text" {{ $appraisal->ans_type == 'text' ? 'checked' : '' }}>
            <label class="form-check-label" for="text">
                {{ get_phrase('Text') }}
            </label>
        </div>
    </div>

    <div class="fpb-7">
        <label for="title" class="eForm-label">{{ get_phrase('Title') }}</label>
        <input type="text" class="form-control eForm-control" id="title" name="title" value="{{ $appraisal->title }}" required>
    </div>

    <div class="fpb-7">
        <label for="question" class="eForm-label">{{ get_phrase('Question') }}</label>
        <div class="new_div">
            <div class="row">
                <div class="col-sm-9" id="inputContainer">
                    @foreach (json_decode($appraisal->question) as $question)
                        <input type="text" name="question[]" class="eForm-control mt-2" value="{{ $question }}" placeholder="{{ get_phrase('Write Question') }}">
                    @endforeach
                </div>
                <div class="col-sm-3 p-0">
                    <button type="button" onclick="appendInput()" class="btn btn-icon feature_btn btn-success"><i class="bi bi-plus"></i></button>
                    <button type="button" onclick="removeInput()" class="btn btn-icon feature_btn btn-danger"> <i class="bi bi-dash"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="fpb-7">
        <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
        <select name="status" id="status" class="form-select eForm-select eChoice-multiple-with-remove">
            <option value="">{{ get_phrase('Select a status') }}</option>
            <option value="1" {{ $appraisal->status == 1 ? 'selected' : '' }}>{{ get_phrase('Active') }}</option>
            <option value="0" {{ $appraisal->status == 0 ? 'selected' : '' }}>{{ get_phrase('Archive') }}</option>
        </select>
    </div>

    <div class="fpb-7 pt-2">
        <button type="submit" class="btn-form">{{ get_phrase('Update') }}</button>
    </div>
</form>


<script type="text/javascript">
    "use strict";

    $(document).ready(function() {
        $('.multiple_teacher').select2({
            placeholder: "{{ get_phrase('Select Teachers') }}",
            allowClear: true
        });
    });

    function appendInput() {
        var container = document.getElementById('inputContainer');
        var newInput = document.createElement('input');
        newInput.setAttribute('type', 'text');
        newInput.setAttribute('placeholder', '{{ get_phrase('Write Question') }}');
        newInput.setAttribute('class', 'eForm-control mt-2');
        newInput.setAttribute('name', 'question[]');
        container.appendChild(newInput);
    }

    function removeInput() {
        var container = document.getElementById('inputContainer');
        var inputs = container.getElementsByTagName('input');
        if (inputs.length > 1) {
            container.removeChild(inputs[inputs.length - 1]);
        }
    }
</script>
