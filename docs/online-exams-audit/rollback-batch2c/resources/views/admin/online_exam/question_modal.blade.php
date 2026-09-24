<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('admin.online_exams.questions.store', $exam_id) }}">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><label class="eForm-label">{{ get_phrase('Question') }} *</label>
                <textarea class="form-control eForm-control" name="question" rows="3" required></textarea></div>
            <div class="row mt-2">
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Type') }}</label>
                    <select class="form-control eForm-control" name="type" id="q_type" onchange="toggleOptions(this.value)">
                        <option value="mcq">MCQ</option>
                        <option value="true_false">True / False</option>
                        <option value="short_answer">Short Answer</option>
                    </select></div>
                <div class="col-6 fpb-7"><label class="eForm-label">{{ get_phrase('Marks') }}</label>
                    <input type="number" class="form-control eForm-control" name="marks" value="1" min="1"></div>
            </div>
            <div id="mcq_options">
                @foreach(['a','b','c','d'] as $opt)
                <div class="fpb-7 mt-1"><label class="eForm-label">{{ get_phrase('Option') }} {{ strtoupper($opt) }}</label>
                    <input type="text" class="form-control eForm-control" name="option_{{ $opt }}"></div>
                @endforeach
                <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Correct Answer') }}</label>
                    <select class="form-control eForm-control" name="correct_answer">
                        <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
                    </select></div>
            </div>
            <div id="tf_options" style="display:none;">
                <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Correct Answer') }}</label>
                    <select class="form-control eForm-control" name="correct_answer_tf">
                        <option value="true">True</option><option value="false">False</option>
                    </select></div>
            </div>
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Add Question') }}</button>
            </div>
        </div>
    </form>
</div>
<script>
function toggleOptions(type){
    document.getElementById('mcq_options').style.display = type==='mcq' ? '' : 'none';
    document.getElementById('tf_options').style.display = type==='true_false' ? '' : 'none';
}
</script>
