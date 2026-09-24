@extends('admin.navigation')
@section('content')

@include('admin.admissions.wizard._layout_top')

<div class="eSection-wrap mb-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">{{ get_phrase('Education History') }}</h5>
        @unless($readOnly)
            <button type="button" class="eBtn eBtn-outline-blackish" id="addRow">
                <i class="bi bi-plus-lg"></i> {{ get_phrase('Add Qualification') }}
            </button>
        @endunless
    </div>

    <form action="{{ route('admin.hei_admissions.wizard.education', $admission->id) }}" method="POST">
        @csrf

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

                if (empty($rows)) {
                    $rows = [[]];
                }
            @endphp

            @foreach($rows as $index => $row)
                <div class="edu-row border rounded p-3 mb-3">
                    <div class="row g-3">
                        <div class="col-md-6 fpb-7">
                            <label class="eForm-label">{{ get_phrase('Institution') }}</label>
                            <input type="text" name="education[{{ $index }}][institution]" class="form-control eForm-control"
                                   value="{{ $row['institution'] ?? '' }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-6 fpb-7">
                            <label class="eForm-label">{{ get_phrase('Award / Certificate') }}</label>
                            <input type="text" name="education[{{ $index }}][award]" class="form-control eForm-control"
                                   value="{{ $row['award'] ?? '' }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-4 fpb-7">
                            <label class="eForm-label">{{ get_phrase('Subject / Field') }}</label>
                            <input type="text" name="education[{{ $index }}][subject]" class="form-control eForm-control"
                                   value="{{ $row['subject'] ?? '' }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-3 fpb-7">
                            <label class="eForm-label">{{ get_phrase('Grade / Result') }}</label>
                            <input type="text" name="education[{{ $index }}][grade]" class="form-control eForm-control"
                                   value="{{ $row['grade'] ?? '' }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-2 fpb-7">
                            <label class="eForm-label">{{ get_phrase('From') }}</label>
                            <input type="number" name="education[{{ $index }}][start_year]" class="form-control eForm-control"
                                   value="{{ $row['start_year'] ?? '' }}" min="1950" max="{{ date('Y') + 1 }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-2 fpb-7">
                            <label class="eForm-label">{{ get_phrase('To') }}</label>
                            <input type="number" name="education[{{ $index }}][end_year]" class="form-control eForm-control"
                                   value="{{ $row['end_year'] ?? '' }}" min="1950" max="{{ date('Y') + 10 }}" {{ $readOnly ? 'readonly' : '' }}>
                        </div>
                        <div class="col-md-1 d-flex align-items-end fpb-7">
                            @unless($readOnly)
                                <button type="button" class="btn btn-outline-danger w-100 removeRow" title="{{ get_phrase('Remove') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endunless
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <label class="eForm-label">{{ get_phrase('Additional notes on academic background') }}</label>
        <textarea name="qualifications" class="form-control eForm-control" rows="4"
                  placeholder="{{ get_phrase('Optional — professional certifications, work experience, awards.') }}" {{ $readOnly ? 'readonly' : '' }}>{{ old('qualifications', $admission->qualifications) }}</textarea>

        @unless($readOnly)
            <div class="d-flex flex-wrap gap-2 justify-content-end mt-4 pt-3">
                <button type="submit" name="action" value="save" class="eBtn eBtn-outline-blackish">{{ get_phrase('Save') }}</button>
                <button type="submit" name="action" value="continue" class="eBtn eBtn-primary">{{ get_phrase('Save & Continue') }}</button>
            </div>
        @endunless
    </form>
</div>

@push('scripts')
<script>
    (function () {
        var container = document.getElementById('eduRows');
        var addButton = document.getElementById('addRow');
        var nextIndex = container ? container.querySelectorAll('.edu-row').length : 0;
        var maxRows = 10;

        function bindRemove(scope) {
            scope.querySelectorAll('.removeRow').forEach(function (button) {
                button.onclick = function () {
                    var rows = container.querySelectorAll('.edu-row');
                    if (rows.length > 1) {
                        button.closest('.edu-row').remove();
                    } else {
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
