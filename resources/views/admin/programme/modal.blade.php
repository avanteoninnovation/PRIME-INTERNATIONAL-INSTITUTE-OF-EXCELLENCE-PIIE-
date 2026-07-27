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
                        @if($programme && in_array($programme->level, \App\Models\Programme::LEVELS_LEGACY, true))
                            <option value="{{ $programme->level }}" selected>{{ $programme->level }} ({{ get_phrase('legacy') }})</option>
                        @endif
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
                            <option value="{{ $m }}" {{ (old('mode', $programme->mode ?? 'ODEL') == $m) ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                        @if($programme && in_array($programme->mode, \App\Models\Programme::MODES_LEGACY, true))
                            <option value="{{ $programme->mode }}" selected>{{ ucfirst($programme->mode) }} ({{ get_phrase('legacy') }})</option>
                        @endif
                    </select>
                </div>
                @php
                    $currentDuration = old('duration', $programme->duration ?? '');
                    $isPresetDuration = in_array($currentDuration, \App\Models\Programme::DURATIONS, true);
                @endphp
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Duration') }}</label>
                    <select class="form-control eForm-control" name="duration_preset"
                        onchange="var isOther = this.value === 'Other'; document.getElementById('duration_other_input').style.display = isOther ? 'block' : 'none'; document.getElementById('duration_input').value = isOther ? document.getElementById('duration_other_input').value : this.value;">
                        <option value="">{{ get_phrase('— Select —') }}</option>
                        @foreach(\App\Models\Programme::DURATIONS as $d)
                            <option value="{{ $d }}" {{ $currentDuration == $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                        <option value="Other" {{ (!$isPresetDuration && $currentDuration !== '') ? 'selected' : '' }}>{{ get_phrase('Other') }}</option>
                    </select>
                    <input type="hidden" id="duration_input" name="duration" value="{{ $currentDuration }}">
                    <input type="text" id="duration_other_input"
                        class="form-control eForm-control mt-1"
                        style="display: {{ (!$isPresetDuration && $currentDuration !== '') ? 'block' : 'none' }};"
                        placeholder="{{ get_phrase('e.g. 18 Months') }}"
                        value="{{ (!$isPresetDuration) ? $currentDuration : '' }}"
                        oninput="document.getElementById('duration_input').value = this.value;">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7">
                    <label class="eForm-label">{{ get_phrase('Tuition Fee (per semester, UGX)') }}</label>
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
