@php
    $routePrefix = request()->routeIs('teacher.*') ? 'teacher' : (request()->routeIs('student.*') ? 'student' : 'admin');
@endphp

<div class="eoff-form">
    <h6 class="mb-3">{{ $liveClass->title }}</h6>

    @if($canManage)
        <form method="POST" action="{{ route($routePrefix . '.live_classes.materials.store', $liveClass->id) }}" enctype="multipart/form-data" class="mb-4" id="materialForm">
            @csrf
            <div class="row g-2">
                <div class="col-6">
                    <label class="eForm-label">{{ get_phrase('Type') }}</label>
                    <select name="type" class="form-control eForm-control" id="materialType">
                        <option value="file">{{ get_phrase('File') }}</option>
                        <option value="link">{{ get_phrase('Link') }}</option>
                    </select>
                </div>
                <div class="col-6">
                    <label class="eForm-label">{{ get_phrase('Title') }} *</label>
                    <input type="text" name="title" class="form-control eForm-control" required maxlength="200">
                </div>
                <div class="col-12" id="materialFileWrap">
                    <label class="eForm-label">{{ get_phrase('File') }} *</label>
                    <input type="file" name="file" class="form-control eForm-control" accept=".{{ implode(',.', \App\Models\LiveClassMaterial::ALLOWED_EXTENSIONS) }}">
                    <small class="text-muted">{{ get_phrase('PDF, PowerPoint, Word, Excel or image · max') }} {{ \App\Models\LiveClassMaterial::MAX_FILE_MB }}MB</small>
                </div>
                <div class="col-12 d-none" id="materialLinkWrap">
                    <label class="eForm-label">{{ get_phrase('Link URL') }}</label>
                    <input type="url" name="link_url" class="form-control eForm-control" placeholder="https://...">
                </div>
                <div class="col-12">
                    <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Add Material') }}</button>
                </div>
            </div>
        </form>
    @endif

    @forelse($materials as $material)
        <div class="d-flex align-items-center gap-2 p-2 mb-2" style="border:1px solid #e7e9ee; border-radius:8px;">
            <i class="bi {{ $material->isFile() ? 'bi-file-earmark-arrow-down' : 'bi-link-45deg' }}" style="font-size:20px;"></i>
            <div class="flex-grow-1">
                <div style="font-weight:600; font-size:14px;">{{ $material->title }}</div>
                <small class="text-muted">
                    @if($material->isFile())
                        {{ $material->original_name }} · {{ $material->human_size }}
                    @else
                        {{ get_phrase('External link') }}
                    @endif
                </small>
            </div>
            <a href="{{ $material->url }}" target="_blank" class="eBtn eBtn-sm eBtn-dark">{{ get_phrase('Open') }}</a>
            @if($canManage)
                <form method="POST" action="{{ route($routePrefix . '.live_classes.materials.destroy', $material->id) }}" onsubmit="return confirm('{{ get_phrase('Remove this material?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Remove') }}</button>
                </form>
            @endif
        </div>
    @empty
        <p class="text-muted text-center py-3">{{ get_phrase('No materials have been added yet.') }}</p>
    @endforelse
</div>

<script>
(function () {
    var typeSelect = document.getElementById('materialType');
    var fileWrap = document.getElementById('materialFileWrap');
    var linkWrap = document.getElementById('materialLinkWrap');

    if (typeSelect) {
        typeSelect.addEventListener('change', function () {
            var isLink = typeSelect.value === 'link';
            fileWrap.classList.toggle('d-none', isLink);
            linkWrap.classList.toggle('d-none', !isLink);
        });
    }
})();
</script>
