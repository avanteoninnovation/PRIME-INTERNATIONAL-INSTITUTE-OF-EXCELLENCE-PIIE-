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
                        @foreach(\App\Models\Programme::LEVELS as $lvl)
                            <option value="{{ $lvl }}" {{ (old('level', $programme->level ?? '') == $lvl) ? 'selected' : '' }}>{{ $lvl }}</option>
                        @endforeach
                        {{-- Legacy values: kept selectable only so a programme already
                             using one doesn't lose data on its next edit; not offered
                             as a first choice for a brand-new programme. --}}
                        @foreach(\App\Models\Programme::LEVELS_LEGACY as $lvl)
                            <option value="{{ $lvl }}" {{ (old('level', $programme->level ?? '') == $lvl) ? 'selected' : '' }}>{{ $lvl }} ({{ get_phrase('legacy') }})</option>
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
                        @foreach(\App\Models\Programme::MODES as $m)
                            <option value="{{ $m }}" {{ (old('mode', $programme->mode ?? '') == $m) ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                        @foreach(\App\Models\Programme::MODES_LEGACY as $m)
                            <option value="{{ $m }}" {{ (old('mode', $programme->mode ?? '') == $m) ? 'selected' : '' }}>{{ ucfirst($m) }} ({{ get_phrase('legacy') }})</option>
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
                    <label class="eForm-label">{{ get_phrase('Faculty / Department') }}</label>
                    <select class="form-control eForm-control" name="department_id">
                        <option value="">{{ get_phrase('— None —') }}</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (old('department_id', $programme->department_id ?? '') == $dept->id) ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    @if($departments->isEmpty())
                        <small class="text-muted d-block mt-1">
                            {{ get_phrase('No faculties set up yet.') }}
                            <a href="{{ route('admin.department_list') }}" target="_blank">{{ get_phrase('Add one') }}</a>
                        </small>
                    @endif
                </div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ $programme ? get_phrase('Update Programme') : get_phrase('Create Programme') }}</button>
            </div>
        </div>
    </form>
</div>
