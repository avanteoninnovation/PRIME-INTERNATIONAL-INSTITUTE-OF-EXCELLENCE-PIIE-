<form method="POST" enctype="multipart/form-data" class="d-block" action="{{ route('student.hostel.applications.store') }}">
    @csrf
    <div class="form-row">
        <div class="fpb-7">
            <label for="hostel_id" class="eForm-label">{{ get_phrase('Select Hostel') }}</label>
            <select name="hostel_id" id="hostel_id" class="form-select eForm-select" required>
                <option value="">{{ get_phrase('Select Hostel') }}</option>
                @foreach ($hostels as $hostel)
                    <option value="{{ $hostel->id }}">
                        {{ $hostel->name }} ({{ $hostel->type == 1 ? 'Boys' : 'Girls' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="fpb-7">
            <label for="room_id" class="eForm-label">{{ get_phrase('Select Room') }}</label>
            <select name="room_id" id="room_id" class="form-select eForm-select" required>
                <option value="">{{ get_phrase('Select Room') }}</option>
                @foreach ($hostel_rooms as $room)
                    @php
                        $available_beds = $room->capacity - $room->occupied;
                    @endphp
                    @if ($available_beds > 0)
                        <option value="{{ $room->id }}">
                            {{ $room->hostel->name }} - {{ get_phrase('Room') }} {{ $room->room_no }} ({{ $available_beds }} {{ get_phrase('of') }} {{ $room->capacity }} {{ get_phrase('beds available') }}) - ${{ $room->seat_fee }}
                        </option>
                    @endif
                @endforeach
            </select>

            @if (count($hostel_rooms) == 0)
                <div class="alert alert-warning mt-2">
                    {{ get_phrase('No rooms available at the moment') }}
                </div>
            @endif
        </div>

        <div class="fpb-7">
            <label for="note" class="eForm-label">{{ get_phrase('Additional Notes') }}</label>
            <textarea class="form-control eForm-control" id="note" name="note" rows="3" placeholder="{{ get_phrase('Any special requirements or notes...') }}"></textarea>
        </div>

        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Submit') }}</button>
        </div>
    </div>
</form>
