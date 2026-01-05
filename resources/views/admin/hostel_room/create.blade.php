<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.hostel.store_room') }}">
    @csrf
    <input type="hidden" name="occupied" value="0">
    <div class="form-row">
        <div class="fpb-7">
            <label for="hostel_id" class="eForm-label">{{ get_phrase('Hostel') }}</label>
            <select name="hostel_id" id="hostel_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Hostel') }}</option>
                @foreach ($hostels as $hostel)
                    <option value="{{ $hostel->id }}">{{ $hostel->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fpb-7">
            <label for="room_no" class="eForm-label">{{ get_phrase('Room Number') }}</label>
            <input type="text" class="form-control eForm-control" id="room_no" name="room_no" required>
        </div>
        <div class="fpb-7">
            <label for="capacity" class="eForm-label">{{ get_phrase('Capacity') }}</label>
            <input type="number" class="form-control eForm-control" id="capacity" name="capacity" required>
        </div>
        <div class="fpb-7">
            <label for="type" class="eForm-label">{{ get_phrase('Seat Fee') }}</label>
            <input type="number" class="form-control eForm-control" id="seat_fee" name="seat_fee" required>
        </div>
        <div class="fpb-7">
            <label for="status" class="eForm-label">{{ get_phrase('Status') }}</label>
            <select name="status" id="status" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="1">{{ get_phrase('Available') }}</option>
                <option value="0">{{ get_phrase('Unavailable') }}</option>
            </select>
        </div>
        <div class="fpb-7">
            <label for="description" class="eForm-label">{{ get_phrase('Description') }}</label>
            <textarea class="form-control eForm-control" id="description" name="description" required></textarea>
        </div>
        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Create') }}</button>
        </div>
    </div>
</form>
