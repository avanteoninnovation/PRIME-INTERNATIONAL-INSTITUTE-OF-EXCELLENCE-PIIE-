<form method="POST" class="d-block ajaxForm" action="{{ route('admin.club.notice.update', $notice->id) }}"
    enctype="multipart/form-data">
    @csrf
    @method('POST')

    <div class="form-row">

        <div class="fpb-7">
            <label for="club_name" class="eForm-label">{{ get_phrase('Title') }}</label>
            <input type="text" class="form-control eForm-control" name="title" value="{{ $notice->title }}" required>
        </div>


        <div class="fpb-7">
            <label for="description" class="eForm-label">{{ get_phrase('Description') }}</label>
            <textarea name="description" id="description" class="form-control eForm-control" rows="4">{{ $notice->description }}</textarea>
        </div>

        <div class="fpb-7">
            <label for="school_name" class="eForm-label">{{ get_phrase('Date') }}</label>
            <input type="date" class="form-control eForm-control" name="notice_date"
                value="{{ $notice->notice_date }}" required>
        </div>

        <div class="fpb-7">
            <label for="Photo" class="eForm-label">{{ get_phrase('Change Photo') }}</label>
            <input type="file" name="image" class="form-control eForm-control">
        </div>

        <div class="fpb-7">
            <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
            <select name="status" id="status" class="form-select eForm-control">
                <option value="1" {{ $notice->status ? 'selected' : '' }}>Active</option>
                <option value="0" {{ !$notice->status ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>

        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Update') }}</button>
        </div>

    </div>
</form>
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

