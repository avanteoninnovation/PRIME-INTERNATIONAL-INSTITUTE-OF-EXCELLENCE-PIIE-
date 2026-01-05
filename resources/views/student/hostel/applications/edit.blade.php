<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('student.hostel.applications.update', $application->id) }}">
    @csrf
    <div class="form-row">
        <div class="fpb-7">
            <label for="hostel_id" class="eForm-label">{{ get_phrase('Select Hostel') }}</label>
            <select name="hostel_id" id="hostel_id" class="form-select eForm-select eChoice-multiple-with-remove" required onchange="getAvailableRooms(this.value)">
                <option value="">{{ get_phrase('Select Hostel') }}</option>
                @foreach ($hostels as $hostel)
                    <option value="{{ $hostel->id }}" {{ $application->hostel_id == $hostel->id ? 'selected' : '' }}>
                        {{ $hostel->name }} ({{ $hostel->type == 1 ? 'Boys' : 'Girls' }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="fpb-7">
            <label for="room_id" class="eForm-label">{{ get_phrase('Select Room') }}</label>
            <select name="room_id" id="room_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Loading rooms...') }}</option>
            </select>
            <small class="text-muted" id="room_info"></small>
        </div>

        <div class="fpb-7">
            <label for="note" class="eForm-label">{{ get_phrase('Additional Notes') }}</label>
            <textarea class="form-control eForm-control" id="note" name="note" rows="3" placeholder="{{ get_phrase('Any special requirements or notes...') }}">{{ $application->note }}</textarea>
        </div>

        <div class="fpb-7">
            <div class="alert alert-info">
                <strong>{{ get_phrase('Note:') }}</strong>
                {{ get_phrase('You can only edit applications that are pending approval.') }}
            </div>
        </div>

        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Update Application') }}</button>
        </div>
    </div>
</form>

<script>
    // Load rooms when page loads
    $(document).ready(function() {
        var hostelId = $('#hostel_id').val();
        if (hostelId) {
            getAvailableRooms(hostelId, {{ $application->room_id }});
        }
    });

    function getAvailableRooms(hostelId, selectedRoomId = null) {
        if (!hostelId) {
            $('#room_id').html('<option value="">{{ get_phrase('First select a hostel') }}</option>');
            return;
        }

        $.ajax({
            url: '{{ url('student/hostel-applications/get-rooms') }}/' + hostelId,
            type: 'GET',
            success: function(response) {
                var rooms = '<option value="">{{ get_phrase('Select Room') }}</option>';
                if (response.length > 0) {
                    response.forEach(function(room) {
                        var available = room.capacity - room.occupied;
                        var selected = (selectedRoomId && room.id == selectedRoomId) ? 'selected' : '';
                        rooms += '<option value="' + room.id + '" ' + selected + ' data-fee="' + room.seat_fee + '" data-available="' + available + '">' +
                            room.room_no + ' - ' + room.capacity + ' beds (' + available + ' available) - Fee: $' + room.seat_fee +
                            '</option>';
                    });
                } else {
                    rooms += '<option value="">{{ get_phrase('No available rooms') }}</option>';
                }
                $('#room_id').html(rooms);

                // Trigger change to show room info
                $('#room_id').trigger('change');
            }
        });
    }

    // Show room info when room is selected
    $(document).on('change', '#room_id', function() {
        var selectedOption = $(this).find('option:selected');
        var fee = selectedOption.data('fee');
        var available = selectedOption.data('available');

        if (fee && available !== undefined) {
            $('#room_info').text('Available beds: ' + available + ' | Seat fee: $' + fee);
        } else {
            $('#room_info').text('');
        }
    });
</script>
