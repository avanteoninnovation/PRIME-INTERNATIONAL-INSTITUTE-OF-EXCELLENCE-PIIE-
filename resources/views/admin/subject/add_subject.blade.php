<div class="eoff-form">
    <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.create.subject') }}">
        @csrf
        <div class="form-row">
            @if($isHigherEducation)
                <div class="mb-3">
                    <label for="programme_id_on_create" class="eForm-label">{{ get_phrase('Programme') }} <span class="text-danger" aria-hidden="true">*</span></label>
                    <select name="programme_id" id="programme_id_on_create" class="form-select eForm-select eChoice-multiple-with-remove" required>
                        <option value="">{{ get_phrase('Select a Programme') }}</option>
                        @foreach($programmes as $programme)
                            <option value="{{ $programme->id }}">{{ $programme->code }} — {{ $programme->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">{{ get_phrase('This is the catalogue association used by existing Programme workflows. It does not add the Course Unit to a Programme Study Plan.') }}</div>
                </div>
            @elseif($isMixed)
                <div class="mb-3" role="group" aria-label="{{ get_phrase('Catalogue association') }}">
                    <p class="form-text mb-0">{{ get_phrase('Choose either a Class for a school Subject or a Programme for a higher-education Course Unit.') }} <span class="text-danger">{{ get_phrase('Required') }}</span></p>
                </div>
                <div class="mb-3">
                    <label for="class_id_on_create" class="eForm-label">{{ get_phrase('Class') }}</label>
                    <select name="class_id" id="class_id_on_create" class="form-select eForm-select eChoice-multiple-with-remove">
                        <option value="">{{ get_phrase('No Class association') }}</option>
                        @foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="programme_id_on_create" class="eForm-label">{{ get_phrase('Programme') }}</label>
                    <select name="programme_id" id="programme_id_on_create" class="form-select eForm-select eChoice-multiple-with-remove">
                        <option value="">{{ get_phrase('No Programme association') }}</option>
                        @foreach($programmes as $programme)<option value="{{ $programme->id }}">{{ $programme->code }} — {{ $programme->name }}</option>@endforeach
                    </select>
                </div>
            @else
                <div class="mb-3">
                    <label for="class_id_on_create" class="eForm-label">{{ get_phrase('Class') }} <span class="text-danger" aria-hidden="true">*</span></label>
                    <select name="class_id" id="class_id_on_create" class="form-select eForm-select eChoice-multiple-with-remove" required>
                        <option value="">{{ get_phrase('Select a Class') }}</option>
                        @foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
                    </select>
                </div>
            @endif

            <div class="mb-3">
                <label for="course-unit-name" class="eForm-label">{{ get_phrase($isHigherEducation ? 'Course Unit Name' : ($isMixed ? 'Subject / Course Unit Name' : 'Subject Name')) }} <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" class="form-control eForm-control" id="course-unit-name" name="name" maxlength="255" placeholder="{{ get_phrase($isHigherEducation ? 'Enter the Course Unit name' : 'Enter the Subject name') }}" required>
            </div>

            <div class="mb-3">
                <label for="course-unit-code" class="eForm-label">{{ get_phrase($isHigherEducation ? 'Course Unit Code' : 'Subject / Course Unit Code') }} <span class="text-danger" aria-hidden="true">*</span></label>
                <input type="text" class="form-control eForm-control" id="course-unit-code" name="code" maxlength="30" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" placeholder="{{ get_phrase('For example, BBIT1101') }}" aria-describedby="course-unit-code-help" required>
                <div id="course-unit-code-help" class="form-text">{{ get_phrase('Use a unique code within this institution. Letters, numbers, dots, underscores and hyphens are allowed.') }}</div>
            </div>

            @if($isHigherEducation)
                <div class="alert alert-info" role="note">
                    {{ get_phrase('After creating the Course Unit, add it to the appropriate Programme Study Plan stage and define its credits/classification there.') }}
                </div>
            @endif
            <div class="pt-2">
                <button class="btn-form" type="submit">{{ get_phrase($isHigherEducation ? 'Create Course Unit' : ($isMixed ? 'Create Subject / Course Unit' : 'Create Subject')) }}</button>
            </div>
        </div>
    </form>
</div>

<script type="text/javascript">
    "use strict";
    $(document).ready(function () { $(".eChoice-multiple-with-remove").select2(); });
</script>
