{{-- <form method="POST" class="d-block ajaxForm" action="{{ route('teacher.club.notice.store') }}, enctype="multipart/form-data">
    @csrf
    @method('POST')

    <input type="hidden" name="club_id" value="{{ $club->id }}">

    <div class="form-row">

        <div class="fpb-7">
            <label for="club_name" class="eForm-label">{{ get_phrase('Title') }}</label>
            <input type="text" class="form-control eForm-control" name="title" required>
        </div>

        <div class="fpb-7">
            <label for="description" class="eForm-label">{{ get_phrase('Description') }}</label>
            <textarea name="description" id="description" class="form-control ol-form-control text_editor " rows="4"></textarea>
        </div>

        <div class="fpb-7">
            <label for="school_name" class="eForm-label">{{ get_phrase('Date') }}</label>
            <input type="date" class="form-control eForm-control" name="notice_date" required>
        </div>
        <div class="fpb-7">
            <label for="Photo" class="eForm-label">{{ get_phrase('Photo') }}</label>

            <input type="file" name="image" class="form-control eForm-control">
        </div>

        <div class="fpb-7">
            <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
            <select name="status" id="status" class="form-select eForm-control">
                <option value="1">{{ get_phrase('Active') }}</option>
                <option value="0">{{ get_phrase('Inactive') }}</option>
            </select>
        </div>



        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Update') }}</button>
        </div>

    </div>
</form> --}}
<script type="text/javascript">
    "use strict";

    $(document).ready(function() {
        $(".eChoice-multiple-with-remove").select2();
    });

    $(function() {
        $('.inputDate').daterangepicker({
                singleDatePicker: true,
                showDropdowns: true,
                minYear: 1901,
                maxYear: parseInt(moment().format("YYYY"), 10),
            },
            function(start, end, label) {
                var years = moment().diff(start, "years");
            }
        );
    });

    $(document).ready(function() {
        $(".eChoice-multiple-with-remove").select2();
    });
</script>


<form method="POST" action="{{ route('teacher.club.notice.store') }}" enctype="multipart/form-data">
    @csrf

    <input type="hidden" name="club_id" value="{{ $club->id }}">

    <div class="form-row">

        <div class="fpb-7">
            <label for="club_name" class="eForm-label">{{ get_phrase('Title') }}</label>
            <input type="text" class="form-control eForm-control" name="title" required>
        </div>

        <div class="fpb-7">
            <label for="description" class="eForm-label">{{ get_phrase('Description') }}</label>
            <textarea name="description" id="description" class="form-control ol-form-control text_editor " rows="4"></textarea>
        </div>

        <div class="fpb-7">
            <label for="school_name" class="eForm-label">{{ get_phrase('Date') }}</label>
            <input type="date" class="form-control eForm-control" name="notice_date" required>
        </div>
        <div class="fpb-7">
            <label for="Photo" class="eForm-label">{{ get_phrase('Photo') }}</label>

            <input type="file" name="image" class="form-control eForm-control">
        </div>

        <div class="fpb-7">
            <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
            <select name="status" id="status" class="form-select eForm-control">
                <option value="1">{{ get_phrase('Active') }}</option>
                <option value="0">{{ get_phrase('Inactive') }}</option>
            </select>
        </div>
    </div>
    <div class="fpb-7 pt-2">
        <button class="btn-form" type="submit">{{ get_phrase('Create Notice') }}</button>
    </div>
</form>
