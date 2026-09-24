@extends('admin.navigation')
@section('content')

@include('admin.admissions.wizard._layout_top')

<div class="eSection-wrap mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ get_phrase('Supporting Documents') }}</h5>
        <span class="text-muted" style="font-size:13px;">{{ get_phrase('PDF, JPG or PNG · max') }} {{ $maxMb }}MB</span>
    </div>

    @forelse($checklist as $row)
        @php
            $requirement = $row['requirement'];
            $files       = $row['files'];
            $state       = $row['state'];
            $tone = ['verified' => 'success', 'pending' => 'primary', 'rejected' => 'danger', 'missing' => 'warning'][$state] ?? 'secondary';
        @endphp

        <div class="p-3 mb-3" style="border:1px solid #e7e9ee; border-radius:8px;">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <strong>{{ $requirement->label }} @if($requirement->is_required)<span class="text-danger">*</span>@endif</strong>
                <span class="badge bg-{{ $tone }}">{{ ucfirst(str_replace('-', ' ', $state)) }}</span>
            </div>
            @if($requirement->description)
                <div class="text-muted mt-1" style="font-size:13px;">{{ $requirement->description }}</div>
            @endif

            @foreach($files as $file)
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2 p-2" style="background:#f8f9fb; border-radius:6px;">
                    <i class="bi {{ $file->isImage() ? 'bi-file-image' : 'bi-file-earmark-pdf' }}"></i>
                    <div class="flex-grow-1">
                        <div style="font-size:13.5px; font-weight:600; word-break:break-all;">{{ $file->original_name }}</div>
                        <small class="text-muted">
                            {{ $file->human_size }} · {{ $file->created_at->format('d M Y') }}
                            @if($file->uploaded_by_user_id)
                                · {{ get_phrase('Uploaded by staff') }}
                            @elseif($file->uploaded_by_applicant_id)
                                · {{ get_phrase('Uploaded by candidate') }}
                            @endif
                            @if($file->review_note)
                                <br><span class="text-danger">{{ $file->review_note }}</span>
                            @endif
                        </small>
                    </div>

                    <a href="{{ $file->url }}" target="_blank" class="eBtn eBtn-sm eBtn-primary" title="{{ get_phrase('Open') }}">
                        <i class="bi bi-eye"></i>
                    </a>

                    @if(! $readOnly && $file->status !== 'verified')
                        <form action="{{ route('admin.hei_admissions.wizard.documents.destroy', [$admission->id, $file->id]) }}" method="POST"
                              onsubmit="return confirm('{{ get_phrase('Remove this document?') }}');">
                            @csrf
                            <button type="submit" class="eBtn eBtn-sm eBtn-danger" title="{{ get_phrase('Remove') }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach

            @unless($readOnly)
                @if($requirement->key)
                    <form action="{{ route('admin.hei_admissions.wizard.documents.store', $admission->id) }}" method="POST" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <input type="hidden" name="requirement_key" value="{{ $requirement->key }}">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="file" name="files[]" class="form-control eForm-control" style="max-width:340px;"
                                   accept=".pdf,.jpg,.jpeg,.png" {{ $requirement->allow_multiple ? 'multiple' : '' }} required>
                            <button type="submit" class="eBtn eBtn-primary">
                                {{ $files->isEmpty() ? get_phrase('Upload') : ($requirement->allow_multiple ? get_phrase('Add Another') : get_phrase('Replace')) }}
                            </button>
                        </div>
                    </form>
                @endif
            @endunless
        </div>
    @empty
        <p class="text-muted">{{ get_phrase('No documents are required for this application.') }}</p>
    @endforelse

    @unless($readOnly)
        <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-3">
            <a href="{{ route('admin.hei_admissions.wizard.step', [$admission->id, 'review']) }}" class="eBtn eBtn-primary">
                {{ get_phrase('Continue to Review') }}
            </a>
        </div>
    @endunless
</div>
@endsection
