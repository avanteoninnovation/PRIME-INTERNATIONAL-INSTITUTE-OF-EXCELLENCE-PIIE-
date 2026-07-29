<div class="eoff-form">
    <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.create.subject') }}">
         @csrf
        <div class="form-row">
            <div class="fpb-7">
                <label for="class_id_on_create" class="eForm-label">{{ get_phrase('Class') }} <small>({{ get_phrase('for K-12 subjects') }})</small></label>
                <select name="class_id" id="class_id_on_create" class="form-select eForm-select eChoice-multiple-with-remove">
                    <option value="">{{ get_phrase('Select a class') }}</option>
                     @foreach($classes as $class)
                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="fpb-7">
                <label for="programme_id_on_create" class="eForm-label">{{ get_phrase('Programme') }} <small>({{ get_phrase('for HEI courses') }})</small></label>
                <select name="programme_id" id="programme_id_on_create" class="form-select eForm-select eChoice-multiple-with-remove">
                    <option value="">{{ get_phrase('Select a programme') }}</option>
                     @foreach($programmes as $programme)
                    <option value="{{ $programme->id }}">{{ $programme->name }}</option>
                    @endforeach
                </select>
            </div>

            <p class="text-muted" style="font-size: 12px;">{{ get_phrase('Choose a Class OR a Programme for this subject, not both.') }}</p>

            <div class="fpb-7">
                <label for="name" class="eForm-label">{{ get_phrase('Name') }}</label>
                <input type="text" class="form-control eForm-control" id="name" name = "name" placeholder="Provide subject name" required>
            </div>

            <div class="fpb-7">
                <label for="code" class="eForm-label">{{ get_phrase('Code') }}</label>
                <input type="text" class="form-control eForm-control" id="code" name="code" placeholder="e.g. CS101 (optional)">
            </div>

            <div class="fpb-7 pt-2">
                <button class="btn-form" type="submit">{{ get_phrase('Create subject') }}</button>
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
