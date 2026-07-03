<div class="eoff-form">
    <form method="POST" enctype="multipart/form-data" class="d-block ajaxForm"
          action="{{ $programme ? route('admin.programmes.update', $programme->id) : route('admin.programmes.store') }}">
        @csrf
        <div class="form-row">
            <div class="row">
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Programme Code') }} *</label>
                    <input type="text" class="form-control eForm-control" name="code" value="{{ $programme->code ?? '' }}" placeholder="e.g. BSC-CS" required maxlength="20">
                </div>
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Level') }} *</label>
                    <select class="form-control eForm-control" name="level" required>
                        @foreach(['Certificate','Diploma','Degree','Masters','PhD','Short Course'] as $lvl)
                            <option value="{{ $lvl }}" {{ (old('level', $programme->level ?? '') == $lvl) ? 'selected' : '' }}>{{ $lvl }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="fpb-7 mt-2">
                <label class="eForm-label">{{ get_phrase('Programme Name') }} *</label>
                <input type="text" class="form-control eForm-control" name="name" value="{{ $programme->name ?? '' }}" placeholder="e.g. Bachelor of Science in Computer Science" required>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Mode') }}</label>
                    <select class="form-control eForm-control" name="mode">
                        @foreach(['fulltime','parttime','online','blended'] as $m)
                            <option value="{{ $m }}" {{ (old('mode', $programme->mode ?? 'fulltime') == $m) ? 'selected' : '' }}>{{ ucfirst($m) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Duration') }}</label>
                    <input type="text" class="form-control eForm-control" name="duration" value="{{ $programme->duration ?? '' }}" placeholder="e.g. 3 years">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Tuition Fee') }}</label>
                    <input type="number" class="form-control eForm-control" name="tuition_fee" value="{{ $programme->tuition_fee ?? 0 }}" min="0" step="0.01">
                </div>
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Department') }}</label>
                    <select class="form-control eForm-control" name="department_id">
                        <option value="">{{ get_phrase('— None —') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (old('department_id', $programme->department_id ?? '') == $dept->id) ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $programme ? get_phrase('Update Programme') : get_phrase('Create Programme') }}</button>
            </div>
        </div>
    </form>
</div>
