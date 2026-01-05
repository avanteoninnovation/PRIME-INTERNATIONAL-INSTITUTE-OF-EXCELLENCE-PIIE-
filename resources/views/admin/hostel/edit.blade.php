<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.hostel.update_hostel', $hostel->id) }}">
    @csrf
    <div class="form-row">
        <div class="fpb-7">
            <label for="name" class="eForm-label">{{ get_phrase('Hostel Name') }}</label>
            <input type="text" class="form-control eForm-control" id="name" name="name" value="{{ $hostel->name }}" required>
        </div>

        <div class="fpb-7">
            <label for="type" class="eForm-label">{{ get_phrase('Hostel Type') }}</label>
            <select name="type" id="type" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Hostel Type') }}</option>
                <option value="1" {{ $hostel->type == 1 ? 'selected' : '' }}>{{ get_phrase('Boys') }}</option>
                <option value="0" {{ $hostel->type == 0 ? 'selected' : '' }}>{{ get_phrase('Girls') }}</option>
            </select>
        </div>

        <div class="fpb-7">
            <label for="warden_id" class="eForm-label">{{ get_phrase('Hostel Warden') }}</label>
            <select name="warden_id" id="warden_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Warden') }}</option>
                @foreach ($wardens as $warden)
                    <option value="{{ $warden->id }}" {{ $hostel->warden_id == $warden->id ? 'selected' : '' }}>
                        {{ $warden->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="fpb-7">
            <label for="address" class="eForm-label">{{ get_phrase('Hostel Address') }}</label>
            <textarea class="form-control eForm-control" id="address" name="address" required>{{ $hostel->address }}</textarea>
        </div>

        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Update') }}</button>
        </div>
    </div>
</form>
