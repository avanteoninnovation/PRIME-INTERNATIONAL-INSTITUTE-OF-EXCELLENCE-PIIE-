@extends('applicant.layout')

@section('title', get_phrase('Education History'))
@section('subtitle', get_phrase('List the schools you attended and the qualifications you obtained.'))

@section('content')

@include('applicant.application._stepper')

<form action="{{ route('applicant.application.education') }}" method="POST">
    @csrf

    <div class="ap-card">
        <div class="ap-card-head">
            <h2 class="ap-card-title"><i class="bi bi-journal-text"></i> {{ get_phrase('Qualifications') }}</h2>
            @unless($readOnly)
                <button type="button" class="ap-btn ap-btn-ghost" id="addRow">
                    <i class="bi bi-plus-lg"></i> {{ get_phrase('Add Qualification') }}
                </button>
            @endunless
        </div>

        <div id="eduRows">
            @php
                $rows = old('education', $qualifications->map(fn ($q) => [
                    'institution' => $q->institution,
                    'award'       => $q->award,
                    'subject'     => $q->subject,
                    'grade'       => $q->grade,
                    'start_year'  => $q->start_year,
                    'end_year'    => $q->end_year,
                    'country'     => $q->country,
                ])->all());

                // Always render at least one blank row so the first-time
                // applicant has somewhere to type without hunting for a button.
                if (empty($rows)) {
                    $rows = [[]];
                }
            @endphp

            @foreach($rows as $index => $row)
                <div class="ap-doc-row edu-row">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ get_phrase('Institution') }}</label>
                            <input type="text" name="education[{{ $index }}][institution]" class="form-control"
                                   value="{{ $row['institution'] ?? '' }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ get_phrase('Award / Certificate') }}</label>
                            <input type="text" name="education[{{ $index }}][award]" class="form-control"
                                   value="{{ $row['award'] ?? '' }}" placeholder="{{ get_phrase('e.g. UACE, Diploma in Business') }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ get_phrase('Subject / Field') }}</label>
                            <input type="text" name="education[{{ $index }}][subject]" class="form-control"
                                   value="{{ $row['subject'] ?? '' }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ get_phrase('Grade / Result') }}</label>
                            <input type="text" name="education[{{ $index }}][grade]" class="form-control"
                                   value="{{ $row['grade'] ?? '' }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ get_phrase('From') }}</label>
                            <input type="number" name="education[{{ $index }}][start_year]" class="form-control"
                                   value="{{ $row['start_year'] ?? '' }}" min="1950" max="{{ date('Y') + 1 }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ get_phrase('To') }}</label>
                            <input type="number" name="education[{{ $index }}][end_year]" class="form-control"
                                   value="{{ $row['end_year'] ?? '' }}" min="1950" max="{{ date('Y') + 10 }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            @unless($readOnly)
                                <button type="button" class="ap-btn ap-btn-danger-ghost w-100 removeRow" title="{{ get_phrase('Remove') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endunless
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="ap-fieldset-title">{{ get_phrase('Anything Else') }}</div>

        <label class="form-label">{{ get_phrase('Additional notes on your academic background') }}</label>
        <textarea name="qualifications" class="form-control" rows="4"
                  placeholder="{{ get_phrase('Optional — professional certifications, work experience, awards.') }}" {{ $readOnly ? 'readonly' : '' }}>{{ old('qualifications', $admission->qualifications) }}</textarea>

        @unless($readOnly)
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-4" style="border-top:1px solid var(--ap-line);">
                <button type="submit" name="action" value="save" class="ap-btn ap-btn-ghost">
                    <i class="bi bi-save"></i> {{ get_phrase('Save') }}
                </button>
                <button type="submit" name="action" value="continue" class="ap-btn ap-btn-primary">
                    {{ get_phrase('Save & Continue') }} <i class="bi bi-arrow-right"></i>
                </button>
            </div>
        @endunless
    </div>
</form>

@push('scripts')
<script>
    (function () {
        var container = document.getElementById('eduRows');
        var addButton = document.getElementById('addRow');
        // Seeded from the rendered rows so a re-displayed form with validation
        // errors keeps appending at the right index instead of overwriting.
        var nextIndex = container ? container.querySelectorAll('.edu-row').length : 0;
        var maxRows = 10;

        function bindRemove(scope) {
            scope.querySelectorAll('.removeRow').forEach(function (button) {
                button.onclick = function () {
                    var rows = container.querySelectorAll('.edu-row');
                    if (rows.length > 1) {
                        button.closest('.edu-row').remove();
                    } else {
                        // Never leave the applicant with nothing to type into.
                        button.closest('.edu-row').querySelectorAll('input').forEach(function (input) {
                            input.value = '';
                        });
                    }
                };
            });
        }

        if (addButton) {
            addButton.addEventListener('click', function () {
                if (container.querySelectorAll('.edu-row').length >= maxRows) {
                    return;
                }

                var template = container.querySelector('.edu-row').cloneNode(true);

                template.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                    input.name = input.name.replace(/education\[\d+\]/, 'education[' + nextIndex + ']');
                });

                container.appendChild(template);
                nextIndex++;
                bindRemove(template);
            });
        }

        if (container) bindRemove(container);
    })();
</script>
@endpush

@endsection
