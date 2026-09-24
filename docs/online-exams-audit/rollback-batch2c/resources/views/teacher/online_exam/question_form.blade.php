<form method="POST" action="{{ route('teacher.online_exams.questions.store', $exam->id) }}" class="row g-2">
    @csrf
    <div class="col-md-12">
        <label class="eForm-label">{{ get_phrase('Question') }}</label>
        <textarea class="form-control eForm-control" name="question" required></textarea>
    </div>
    <div class="col-md-3">
        <label class="eForm-label">{{ get_phrase('Type') }}</label>
        <select class="form-select eForm-select" name="type" id="question_type" onchange="toggleQuestionTypeFields(this.value)">
            <option value="multiple_choice">MCQ</option>
            <option value="true_false">True / False</option>
            <option value="fill_blank">Fill Blank</option>
            <option value="short_answer">Short Answer</option>
            <option value="essay">Essay</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="eForm-label">{{ get_phrase('Marks') }}</label>
        <input type="number" min="0.1" step="0.1" class="form-control eForm-control" name="marks" required>
    </div>

    <div class="col-md-7" id="mcq_fields">
        <div class="row g-2">
            <div class="col-md-6"><input class="form-control eForm-control" name="option_a" placeholder="Option A"></div>
            <div class="col-md-6"><input class="form-control eForm-control" name="option_b" placeholder="Option B"></div>
            <div class="col-md-6"><input class="form-control eForm-control" name="option_c" placeholder="Option C"></div>
            <div class="col-md-6"><input class="form-control eForm-control" name="option_d" placeholder="Option D"></div>
        </div>
    </div>

    <div class="col-md-4" id="correct_answer_field">
        <label class="eForm-label">{{ get_phrase('Correct Answer') }}</label>
        <input type="text" class="form-control eForm-control" name="correct_ans" placeholder="A / B / true / keyword">
    </div>

    <div class="col-12">
        <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Add Question') }}</button>
    </div>
</form>

<script>
function toggleQuestionTypeFields(type) {
    var showMcq = type === 'multiple_choice';
    var showCorrect = type === 'multiple_choice' || type === 'true_false' || type === 'fill_blank';
    document.getElementById('mcq_fields').style.display = showMcq ? 'block' : 'none';
    document.getElementById('correct_answer_field').style.display = showCorrect ? 'block' : 'none';
}
toggleQuestionTypeFields(document.getElementById('question_type').value);
</script>
