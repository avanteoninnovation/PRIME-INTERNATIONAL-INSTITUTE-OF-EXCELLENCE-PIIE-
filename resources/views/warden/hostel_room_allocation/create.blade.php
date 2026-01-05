<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('warden.hostel.store_allocation') }}">
    @csrf
    <div class="form-row">
        <div class="fpb-7">
            <label for="student_id" class="eForm-label">{{ get_phrase('Student') }}</label>
            <select name="student_id" id="student_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Student') }}</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}">{{ $student->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fpb-7">
            <label for="room_id" class="eForm-label">{{ get_phrase('Hostel Room') }}</label>
            <select name="room_id" id="room_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Room') }}</option>
                @foreach ($hostel_rooms as $room)
                    <option value="{{ $room->id }}">
                        {{ $room->hostel->name }} - {{ $room->room_no }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="fpb-7">
            <label for="allocated_on" class="eForm-label">{{ get_phrase('Allocation Date') }}<span class="required">*</span></label>
            <input type="text" class="form-control eForm-control inputDate" id="allocated_on" name="allocated_on" value="{{ date('m/d/Y') }}" />
        </div>
        <div class="fpb-7">
            <label for="vacated_on" class="eForm-label">{{ get_phrase('Vacate Date') }}<span class="required">*</span></label>
            <input type="text" class="form-control eForm-control inputDate" id="vacated_on" name="vacated_on" value="{{ date('m/d/Y') }}" />
        </div>
        <div class="fpb-7">
            <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
            <select name="status" id="status" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="1">{{ get_phrase('Allocated') }}</option>
                <option value="0">{{ get_phrase('Vacated') }}</option>
            </select>
        </div>
        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Create') }}</button>
        </div>
    </div>
</form>
