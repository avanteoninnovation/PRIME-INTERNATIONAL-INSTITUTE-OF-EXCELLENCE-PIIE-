<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('admin.question_bank.store') }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Question') }} *</label>
                <textarea class="form-control eForm-control" name="question" rows="3" required></textarea></div>
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Subject') }}</label>
                <select class="form-control eForm-control" name="subject_id">
                    <option value="">{{ get_phrase('General') }}</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Type') }}</label>
                    <select class="form-control eForm-control" name="type">
                        <option value="mcq">MCQ</option>
                        <option value="true_false">True / False</option>
                        <option value="short_answer">Short Answer</option>
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Difficulty') }}</label>
                    <select class="form-control eForm-control" name="difficulty">
                        <option value="easy">Easy</option>
                        <option value="medium" selected>Medium</option>
                        <option value="hard">Hard</option>
                    </select></div>
            </div>
            @foreach(['a','b','c','d'] as $opt)
            <div class="fpb-7 mt-1"><label class="eForm-label">{{ get_phrase('Option') }} {{ strtoupper($opt) }}</label>
                <input type="text" class="form-control eForm-control" name="option_{{ $opt }}"></div>
            @endforeach
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Correct Answer') }}</label>
                    <input type="text" class="form-control eForm-control" name="correct_ans" placeholder="a / b / c / d / true / false"></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Marks') }}</label>
                    <input type="number" class="form-control eForm-control" name="marks" value="1" min="1"></div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Save to Bank') }}</button>
            </div>
        </div>
    </form>
</div>
