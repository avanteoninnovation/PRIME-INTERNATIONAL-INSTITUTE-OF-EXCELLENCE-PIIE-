{{--
    Document checklist + upload controls.

    Shared by the wizard's Documents step and the standalone Documents page so
    the two can never disagree about what is required or what has been
    accepted. Expects: $admission, $checklist, $maxMb, $readOnly.
--}}

<div class="ap-card">
    <div class="ap-card-head">
        <h2 class="ap-card-title"><i class="bi bi-folder2-open"></i> {{ get_phrase('Supporting Documents') }}</h2>
        <span style="color:var(--ap-muted); font-size:13.5px;">
            {{ get_phrase('PDF, JPG or PNG · max') }} {{ $maxMb }}MB
        </span>
    </div>

    @foreach($checklist as $row)
        @php
            $requirement = $row['requirement'];
            $files       = $row['files'];
            $state       = $row['state'];

            $stateMeta = [
                'missing'           => ['label' => get_phrase('Required'),        'class' => 'text-warning',   'icon' => 'bi-exclamation-circle'],
                'rejected'          => ['label' => get_phrase('Not accepted'),    'class' => 'text-danger',    'icon' => 'bi-x-circle'],
                'pending'           => ['label' => get_phrase('Awaiting review'), 'class' => 'text-primary',   'icon' => 'bi-hourglass-split'],
                'verified'          => ['label' => get_phrase('Verified'),        'class' => 'text-success',   'icon' => 'bi-check-circle-fill'],
                'optional'          => ['label' => get_phrase('Optional'),        'class' => 'text-secondary', 'icon' => 'bi-dash-circle'],
                'optional-supplied' => ['label' => get_phrase('Supplied'),        'class' => 'text-secondary', 'icon' => 'bi-paperclip'],
            ][$state];
        @endphp

        <div class="ap-doc-row state-{{ $state }}">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <strong style="font-size:15px;">{{ $requirement->label }}</strong>
                        @if($requirement->is_required)
                            <span style="color:#d92d20; font-size:13px;">*</span>
                        @endif
                    </div>
                    @if($requirement->description)
                        <div class="ap-hint">{{ $requirement->description }}</div>
                    @endif
                </div>

                <span class="ap-pill {{ $stateMeta['class'] }}">
                    <i class="bi {{ $stateMeta['icon'] }}"></i> {{ $stateMeta['label'] }}
                </span>
            </div>

            @foreach($files as $file)
                <div class="ap-file-chip">
                    <i class="bi {{ $file->isImage() ? 'bi-file-image' : 'bi-file-earmark-pdf' }}" style="color:var(--ap-primary); font-size:18px;"></i>
                    <div class="flex-grow-1">
                        <div class="name">{{ $file->original_name }}</div>
                        <div class="meta">
                            {{ $file->human_size }} · {{ get_phrase('Uploaded') }} {{ $file->created_at->format('d M Y') }}
                            @if($file->status === 'rejected' && $file->review_note)
                                <br><span class="text-danger">{{ get_phrase('Reviewer note') }}: {{ $file->review_note }}</span>
                            @endif
                        </div>
                    </div>

                    <a href="{{ route('applicant.document.view', $file->id) }}" target="_blank"
                       class="ap-btn ap-btn-ghost py-1 px-2" title="{{ get_phrase('View') }}">
                        <i class="bi bi-eye"></i>
                    </a>

                    @if(! $readOnly && $file->status !== 'verified')
                        <form action="{{ route('applicant.documents.delete', $file->id) }}" method="POST"
                              onsubmit="return confirm('{{ get_phrase('Remove this document?') }}');">
                            @csrf
                            <button type="submit" class="ap-btn ap-btn-danger-ghost py-1 px-2" title="{{ get_phrase('Remove') }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endif
                </div>
            @endforeach

            @unless($readOnly)
                @if($requirement->key)
                    <form action="{{ route('applicant.documents.upload') }}" method="POST" enctype="multipart/form-data" class="mt-3">
                        @csrf
                        <input type="hidden" name="requirement_key" value="{{ $requirement->key }}">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="file" name="files[]" class="form-control" style="max-width:340px;"
                                   accept=".pdf,.jpg,.jpeg,.png" {{ $requirement->allow_multiple ? 'multiple' : '' }} required>
                            <button type="submit" class="ap-btn ap-btn-primary">
                                <i class="bi bi-upload"></i>
                                {{ $files->isEmpty() ? get_phrase('Upload') : ($requirement->allow_multiple ? get_phrase('Add Another') : get_phrase('Replace')) }}
                            </button>
                        </div>
                        @unless($requirement->allow_multiple)
                            @if($files->isNotEmpty())
                                <div class="ap-hint">{{ get_phrase('Uploading a new file replaces the one already on record.') }}</div>
                            @endif
                        @endunless
                    </form>
                @endif
            @endunless
        </div>
    @endforeach

    @if(empty($checklist))
        <div class="ap-empty">
            <i class="bi bi-folder2"></i>
            <p class="mb-0">{{ get_phrase('No documents are required for your application.') }}</p>
        </div>
    @endif
</div>
