<form method="POST" enctype="multipart/form-data" class="d-block ajaxForm" action="{{ route('admin.hostel.store_hostel') }}">
    @csrf
    <div class="form-row">
        <div class="fpb-7">
            <label for="name" class="eForm-label">{{ get_phrase('Hostel Name') }}</label>
            <input type="text" class="form-control eForm-control" id="name" name="name" required>
        </div>
        <div class="fpb-7">
            <label for="type" class="eForm-label">{{ get_phrase('Hostel Type') }}</label>
            <select name="type" id = "type" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Hostel Type') }}</option>
                <option value="1">{{ get_phrase('Boys') }}</option>
                <option value="0">{{ get_phrase('Girls') }}</option>
            </select>
        </div>
        <div class="fpb-7">
            <label for="warden_id" class="eForm-label">{{ get_phrase('Hostel Warden') }}</label>
            <select name="warden_id" id="warden_id" class="form-select eForm-select eChoice-multiple-with-remove" required>
                <option value="">{{ get_phrase('Select Warden') }}</option>
                @foreach ($wardens as $warden)
                    <option value="{{ $warden->id }}">{{ $warden->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fpb-7">
            <label for="address" class="eForm-label">{{ get_phrase('Hostel Address') }}</label>
            <textarea class="form-control eForm-control" id="address" name = "address" required></textarea>

        </div>
        <div class="fpb-7 pt-2">
            <button class="btn-form" type="submit">{{ get_phrase('Create') }}</button>
        </div>
    </div>
</form>
