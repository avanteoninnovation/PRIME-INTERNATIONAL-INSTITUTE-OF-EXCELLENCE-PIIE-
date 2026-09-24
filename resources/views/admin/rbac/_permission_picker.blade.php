{{--
    RBAC Phase 3B — grouped permission selector (never one flat list).
    Inputs: $groups (module => label/permissions from the registry), $selected (checked keys),
    $requires (dependency map), optional $exclude (keys not offered, e.g. already granted).
    - Non-delegable permissions are shown locked and are never submitted (the backend rejects them anyway).
    - Sensitive permissions are marked, confirmed when ticked, and never ticked by "Select all".
    - Ticking an action also ticks the view it requires; unticking a view unticks what depends on it.
--}}
@php $exclude = $exclude ?? []; @endphp
<div class="rbac-grid" data-rbac-picker data-requires='@json($requires)'>
    @foreach ($groups as $module => $group)
        @php $offered = array_diff_key($group['permissions'], array_flip($exclude)); @endphp
        @continue(!$offered)
        <fieldset class="rbac-module">
            <div class="rbac-module-head">
                <strong>{{ get_phrase($group['label']) }}</strong>
                @if (array_filter($offered, fn ($d) => $d['delegable'] && !$d['sensitive']))
                    <label class="rbac-muted small mb-0" style="cursor:pointer">
                        <input type="checkbox" data-select-module="{{ $module }}" style="accent-color:#1d4ed8"> {{ get_phrase('Select all') }}
                    </label>
                @endif
            </div>
            @foreach ($offered as $key => $definition)
                @php
                    $locked = !$definition['delegable'];
                    $id = 'perm-' . str_replace('.', '-', $key) . '-' . ($pickerId ?? 'p');
                @endphp
                <div class="rbac-perm {{ $definition['sensitive'] ? 'is-sensitive' : '' }} {{ $locked ? 'is-disabled' : '' }}">
                    <input type="checkbox" id="{{ $id }}" name="permissions[]" value="{{ $key }}"
                           data-module="{{ $module }}" data-sensitive="{{ $definition['sensitive'] ? 1 : 0 }}"
                           @checked(!$locked && in_array($key, $selected, true)) @disabled($locked)>
                    <label for="{{ $id }}">
                        <span class="rbac-label">{{ get_phrase($definition['label']) }}</span>
                        @if ($definition['sensitive'])<span class="rbac-badge rbac-badge-sensitive ms-1"><i class="bi bi-exclamation-triangle"></i> {{ get_phrase('Sensitive') }}</span>@endif
                        @if ($locked)<span class="rbac-badge rbac-badge-locked ms-1"><i class="bi bi-lock"></i> {{ get_phrase('School Admin only') }}</span>@endif
                        <span class="rbac-desc">{{ get_phrase($definition['description']) }} <span class="rbac-key">{{ $key }}</span></span>
                    </label>
                </div>
            @endforeach
        </fieldset>
    @endforeach
</div>

@once
<script>
    (function () {
        document.querySelectorAll('[data-rbac-picker]').forEach(function (picker) {
            var requires = JSON.parse(picker.getAttribute('data-requires') || '{}');
            var box = function (key) { return picker.querySelector('input[name="permissions[]"][value="' + key + '"]'); };

            picker.addEventListener('change', function (event) {
                var input = event.target;
                if (input.matches('[data-select-module]')) {
                    // "Select all" never ticks sensitive or locked permissions.
                    picker.querySelectorAll('input[name="permissions[]"][data-module="' + input.getAttribute('data-select-module') + '"]').forEach(function (cb) {
                        if (!cb.disabled && cb.getAttribute('data-sensitive') !== '1') { cb.checked = input.checked; cb.dispatchEvent(new Event('change', { bubbles: true })); }
                    });
                    return;
                }
                if (!input.matches('input[name="permissions[]"]')) { return; }
                var key = input.value;
                if (input.checked) {
                    if (input.getAttribute('data-sensitive') === '1' && event.isTrusted && !window.confirm(@json(get_phrase('This is a sensitive permission. Grant it only to someone who must have it. Continue?')))) {
                        input.checked = false; return;
                    }
                    (requires[key] || []).forEach(function (req) { var cb = box(req); if (cb && !cb.checked) { cb.checked = true; } });
                } else {
                    Object.keys(requires).forEach(function (dependent) {
                        if ((requires[dependent] || []).indexOf(key) !== -1) { var cb = box(dependent); if (cb && cb.checked) { cb.checked = false; } }
                    });
                }
            });
        });
    })();
</script>
@endonce
