<div class="d-flex align-items-center gap-2 p-2 mb-2" style="border:1px solid #e7e9ee; border-radius:8px;">
    <i class="bi {{ $material->isFile() ? ($material->isRecording() ? 'bi-camera-video' : 'bi-file-earmark-arrow-down') : 'bi-link-45deg' }}" style="font-size:20px;"></i>
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
        <form method="POST" action="{{ route($routePrefix . '.live_classes.materials.destroy', $material->id) }}" onsubmit="return confirm('{{ get_phrase('Remove this item?') }}')">
            @csrf
            @method('DELETE')
            <button type="submit" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Remove') }}</button>
        </form>
    @endif
</div>
