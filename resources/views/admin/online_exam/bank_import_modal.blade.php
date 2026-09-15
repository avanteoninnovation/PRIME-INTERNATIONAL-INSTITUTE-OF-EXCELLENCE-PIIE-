<div class="eoff-form">
    <p class="mb-3">
        {{ get_phrase('Upload a CSV or Excel file to add many questions to the bank at once.') }}
        <a href="{{ route('admin.question_bank.import_template') }}">{{ get_phrase('Download a template') }}</a>
    </p>

    <form method="POST" class="d-block" action="{{ route('admin.question_bank.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="fpb-7">
            <label class="eForm-label">{{ get_phrase('File (CSV or Excel)') }} *</label>
            <input type="file" class="form-control eForm-control" name="file" accept=".csv,.txt,.xlsx,.xls" required>
            <small class="text-muted d-block mt-1">
                {{ get_phrase('Columns: question, type, option_a, option_b, option_c, option_d, correct_ans, marks, difficulty, subject.') }}
                {{ get_phrase('type: mcq, true_false, short or essay. difficulty defaults to medium if left blank. subject is optional and matched by name.') }}
            </small>
        </div>
        <div class="fpb-7 pt-3">
            <button class="btn-form" type="submit">{{ get_phrase('Import Questions') }}</button>
        </div>
    </form>
</div>
