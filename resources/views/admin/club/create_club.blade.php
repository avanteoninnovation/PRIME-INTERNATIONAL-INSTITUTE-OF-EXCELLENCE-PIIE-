<form method="POST" action="{{ route('admin.club.store') }}">
    @csrf

    <div class="mb-3">
        <label>Club Name</label>
        <input type="text" name="club_name" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Advisor (Teacher)</label>
        <select name="advisor_id" class="form-control">
            <option value="">Select Teacher</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" {{ old('advisor_id') == $teacher->id ? 'selected' : '' }}>
                    {{ $teacher->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>


    <div class="mb-3">
        <label>Description</label>
        <textarea name="description" class="form-control"></textarea>
    </div>

    <div class="fpb-7 pt-2">
        <button class="btn-form" type="submit">{{ get_phrase('Save Club') }}</button>
    </div>
</form>
