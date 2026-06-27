<form method="POST" class="d-block ajaxForm" action="{{ route('admin.club.update', $club->id) }}">
    @csrf
    @method('POST')

    <div class="form-row">

        <div class="fpb-7">
            <label for="club_name" class="eForm-label">{{ get_phrase('Club Name') }}</label>
            <input type="text" class="form-control eForm-control" id="club_name" name="club_name"
                value="{{ old('club_name', $club->club_name) }}" required>
        </div>

        <div class="fpb-7">
            <label for="advisor_id" class="eForm-label">{{ get_phrase('Advisor (Teacher)') }}</label>
            <select name="advisor_id" id="advisor_id" class="form-select eForm-control" required>
                <option value="">{{ get_phrase('Select Teacher') }}</option>
                @foreach ($teachers as $teacher)
                    <option value="{{ $teacher->id }}" {{ $club->advisor_id == $teacher->id ? 'selected' : '' }}>
                        {{ $teacher->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="fpb-7">
            <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
            <select name="status" id="status" class="form-select eForm-control">
                <option value="1" {{ $club->status == 1 ? 'selected' : '' }}>{{ get_phrase('Active') }}</option>
                <option value="0" {{ $club->status == 0 ? 'selected' : '' }}>{{ get_phrase('Inactive') }}</option>
            </select>
        </div>

        <div class="fpb-7">
            <label for="description" class="eForm-label">{{ get_phrase('Description') }}</label>
            <textarea name="description" id="description" class="form-control eForm-control" rows="4">{{ old('description', $club->description) }}</textarea>
        </div>

        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Save Club') }}</button>
        </div>

    </div>
</form>
<script type="text/javascript">

    "use strict";

    $(document).ready(function () {
      $(".eChoice-multiple-with-remove").select2();
    });

    $(function () {
      $('.inputDate').daterangepicker(
        {
          singleDatePicker: true,
          showDropdowns: true,
          minYear: 1901,
          maxYear: parseInt(moment().format("YYYY"), 10),
        },
        function (start, end, label) {
          var years = moment().diff(start, "years");
        }
      );
    });

    $(document).ready(function () {
      $(".eChoice-multiple-with-remove").select2();
    });

</script>
