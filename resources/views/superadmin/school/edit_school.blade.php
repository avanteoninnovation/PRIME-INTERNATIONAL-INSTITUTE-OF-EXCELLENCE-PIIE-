<div class="eForm-layouts">
    <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('superadmin.school.update', ['id' => $school->id]) }}">
         @csrf 
        <div class="form-row">
            <div class="fpb-7">
                <label for="title" class="eForm-label">{{ get_phrase('Title') }}</label>
                <input type="text" class="form-control eForm-control" value="{{ $school->title }}" id="title" name = "title" required>
            </div>
            <div class="fpb-7">
                <label for="address" class="eForm-label">{{ get_phrase('School address') }}</label>
                <textarea class="form-control eForm-control" id="address" name = "address" rows="2" required>{{ $school->address }}</textarea>
            </div>
            <div class="fpb-7">
                <label for="title" class="eForm-label">{{ get_phrase('School phone') }}</label>
                <input type="number" min="0" class="form-control eForm-control" value="{{ $school->phone }}" id="phone" name = "phone" required>
            </div>
            <div class="fpb-7">
                <label for="school_info" class="eForm-label">{{ get_phrase('School info') }}</label>
                <textarea class="form-control eForm-control" id="school_info" name = "school_info" rows="2" required>{{ $school->school_info }}</textarea>
            </div>
            <div class="fpb-7">
                <label for="education_level" class="eForm-label">{{ get_phrase('Education Level') }}</label>
                <select name="education_level" id="education_level" class="form-select eForm-select eChoice-multiple-with-remove">
                    <option value="">{{ get_phrase('Select an education level') }}</option>
                    <option value="primary" {{ $school->education_level == 'primary' ? 'selected' : '' }}>{{ get_phrase('Primary School') }}</option>
                    <option value="secondary" {{ $school->education_level == 'secondary' ? 'selected' : '' }}>{{ get_phrase('Secondary School') }}</option>
                    <option value="tertiary" {{ $school->education_level == 'tertiary' ? 'selected' : '' }}>{{ get_phrase('Tertiary Institution / University') }}</option>
                    <option value="vocational" {{ $school->education_level == 'vocational' ? 'selected' : '' }}>{{ get_phrase('Vocational / Technical Institution') }}</option>
                    <option value="mixed" {{ $school->education_level == 'mixed' ? 'selected' : '' }}>{{ get_phrase('Mixed / Multi-Level Institution') }}</option>
                </select>
                <small class="text-muted">{{ get_phrase('Descriptive only — does not change application behavior.') }}</small>
            </div>
            <div class="fpb-7">
                <label for="school_type" class="eForm-label">{{ get_phrase('Academic Structure') }}</label>
                <select name="school_type" id="school_type" class="form-select eForm-select eChoice-multiple-with-remove">
                    <option value="k12" {{ $school->school_type == 'k12' ? 'selected' : '' }}>{{ get_phrase('Class-Based (K-12)') }}</option>
                    <option value="higher_ed" {{ $school->school_type == 'higher_ed' ? 'selected' : '' }}>{{ get_phrase('Programme-Based (Higher Education)') }}</option>
                    <option value="mixed" {{ $school->school_type == 'mixed' ? 'selected' : '' }}>{{ get_phrase('Mixed (both structures)') }}</option>
                </select>
                <small class="text-muted">{{ get_phrase('Controls which academic modules (Classes vs Programmes/Courses) this school sees.') }}</small>
            </div>
            <div class="fpb-7 pt-2">
                <button class="btn-form" type="submit">{{ get_phrase('Update school') }}</button>
            </div>
        </div>
    </form>
</div>

<script type="text/javascript">

    "use strict";
    
    $(document).ready(function () {
      $(".eChoice-multiple-with-remove").select2();
    });
</script>