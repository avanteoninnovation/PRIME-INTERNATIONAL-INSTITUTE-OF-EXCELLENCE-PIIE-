<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.hostel.update_allocation', $hostel_room_allocation->id) }}">
    @csrf
    <div class="form-row">

        <div class="fpb-7">
            <label for="student_id" class="eForm-label">{{ get_phrase('Student') }}</label>
            <select name="student_id" id="student_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Student') }}</option>

                @foreach ($students as $student)
                    <option value="{{ $student->id }}" {{ $hostel_room_allocation->student_id == $student->id ? 'selected' : '' }}>
                        {{ $student->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="fpb-7">
            <label for="room_id" class="eForm-label">{{ get_phrase('Hostel Room') }}</label>
            <select name="room_id" id="room_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Room') }}</option>

                @foreach ($hostel_rooms as $room)
                    <option value="{{ $room->id }}" {{ $hostel_room_allocation->room_id == $room->id ? 'selected' : '' }}>
                        {{ $room->hostel->name }} - {{ $room->room_no }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="fpb-7">
            <label for="allocated_on" class="eForm-label">{{ get_phrase('Allocation Date') }}</label>
            <input type="text" class="form-control eForm-control inputDate" id="allocated_on" name="allocated_on" value="{{ date('m/d/Y', strtotime($hostel_room_allocation->allocated_on)) }}" required>
        </div>

        <div class="fpb-7">
            <label for="vacated_on" class="eForm-label">{{ get_phrase('Vacate Date') }}</label>
            <input type="text" class="form-control eForm-control inputDate" id="vacated_on" name="vacated_on" value="{{ date('m/d/Y', strtotime($hostel_room_allocation->vacated_on)) }}" required>
        </div>

        <div class="fpb-7">
            <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
            <select name="status" id="status" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="1" {{ $hostel_room_allocation->status == 1 ? 'selected' : '' }}>
                    {{ get_phrase('Allocated') }}
                </option>
                <option value="0" {{ $hostel_room_allocation->status == 0 ? 'selected' : '' }}>
                    {{ get_phrase('Vacated') }}
                </option>
            </select>
        </div>

        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Update') }}</button>
        </div>

    </div>
</form>
