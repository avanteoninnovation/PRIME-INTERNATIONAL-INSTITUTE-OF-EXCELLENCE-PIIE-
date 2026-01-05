@extends('student.navigation')
@section('content')
<style>
    .rating-stars i {
    color: #d3d3d3; /* Default color for inactive stars */
}

.rating-stars i.active {
    color: #FFD700; /* Active color (gold) */
}
</style>
<!-- Title Section -->
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Appraisal') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Appraisal') }}</a></li>
                        <li><a href="#">{{ $appraisal->title }}</a></li>
                    </ul>
                </div>
                <div class="export-btn-area">
                    <a href="{{ route('student.appraisal.appraisalList') }}" class="export_btn">{{ get_phrase('Back') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Feedback Form -->
<div class="row">
    <div class="col-12">
        <div class="eSection-wrap-2">
            <form method="POST" action="{{ route('student.appraisal.submit', ['id' => $appraisal->id]) }}">
                @csrf

                <div class="container">
                    @php
                        $teacher_ids = json_decode($appraisal->teacher_id);
                        $teachers = [];
                        foreach($teacher_ids as $teacher_id) {
                            $teacher = DB::table('users')->where('id', $teacher_id)->first();
                            if ($teacher) {
                                $info = json_decode($teacher->user_information, true);
                                $user_image = !empty($info['photo']) ? 'uploads/user-images/'.$info['photo'] : 'uploads/user-images/thumbnail.png';
                                $teachers[] = [
                                    'id' => $teacher_id,
                                    'name' => $teacher->name,
                                    'image' => $user_image
                                ];
                            }
                        }
                        $questions = json_decode($appraisal->question);
                        $ans_type = $appraisal->ans_type;
                    @endphp

                    @foreach($teachers as $index => $teacher)
                   
                        <div class="feedback-card">
                            <div class="dAdmin_profile_img d-flex">
                                <img class="img-fluid rounded-circle" width="50" height="50" src="{{ asset('assets') }}/{{ ($teacher['image']) }}" />
                                <h4 class="mx-3 mt-2">{{ $teacher['name'] }}</h4>
                              </div>
                            <ol class="question-list">
                                @foreach($questions as $q_index => $question)
                                    <li>
                                        {{ $question }}
                                        <div class="feedback-input mt-2">
                                            @php 
                                                $teacherId = $teacher_ids[$index];
                                                $prevAnswer = $submittedAnswers[$teacherId][$q_index] ?? null; 
                                            @endphp

                                            @if($ans_type == 'mcq')
                                                <select name="answers[{{ $teacherId }}][{{ $q_index }}]" class="form-select" {{ $prevAnswer ? 'disabled' : '' }}>
                                                    <option value="Excellent" {{ $prevAnswer == 'Excellent' ? 'selected' : '' }}>🌟 Excellent</option>
                                                    <option value="Good" {{ $prevAnswer == 'Good' ? 'selected' : '' }}>👍 Good</option>
                                                    <option value="Average" {{ $prevAnswer == 'Average' ? 'selected' : '' }}>😐 Average</option>
                                                    <option value="Poor" {{ $prevAnswer == 'Poor' ? 'selected' : '' }}>👎 Poor</option>
                                                </select>
                                                @elseif($ans_type == 'rating')
                                                <div class="rating-stars" data-index="{{ $teacherId }}_{{ $q_index }}">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="bi bi-star {{ $prevAnswer >= $i ? 'active' : '' }}" data-value="{{ $i }}"></i>
                                                    @endfor
                                                    <input type="hidden" name="answers[{{ $teacherId }}][{{ $q_index }}]" id="ratingInput_{{ $teacherId }}_{{ $q_index }}" value="{{ $prevAnswer ?? 0 }}" {{ $prevAnswer ? 'disabled' : '' }}>
                                                </div>
                                            @elseif($ans_type == 'binary')
                                                <select name="answers[{{ $teacherId }}][{{ $q_index }}]" class="form-select" {{ $prevAnswer ? 'disabled' : '' }}>
                                                    <option value="Yes" {{ $prevAnswer == 'Yes' ? 'selected' : '' }}>✅ Yes</option>
                                                    <option value="No" {{ $prevAnswer == 'No' ? 'selected' : '' }}>❌ No</option>
                                                </select>
                                            @elseif($ans_type == 'text')
                                                <textarea name="answers[{{ $teacherId }}][{{ $q_index }}]" class="form-control" placeholder="Write your feedback" {{ $prevAnswer ? 'disabled' : '' }}>{{ $prevAnswer ?? '' }}</textarea>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-primary px-4 py-2">{{get_phrase('Submit Feedback')}}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- <div class="container">
    <h3>📋 Teacher-wise Feedback</h3>

    @php
        $groupedFeedbacks = [];
        foreach ($feedbacks as $feedback) {
            $answers = json_decode($feedback->answers, true);
            foreach ($answers as $teacherId => $teacherAnswers) {
                $groupedFeedbacks[$teacherId][] = [
                    'appraisal_id' => $feedback->apprasial_id,
                    'student_id' => $feedback->student_id,
                    'answers' => $teacherAnswers
                ];
            }
        }
    @endphp

    <div class="accordion" id="teacherAccordion">
        @foreach($groupedFeedbacks as $teacherId => $feedbacks)
            @php
                $teacher = DB::table('users')->where('id', $teacherId)->first();
            @endphp
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $teacherId }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $teacherId }}" aria-expanded="false">
                        👨‍🏫 {{ $teacher->name }} ({{ count($feedbacks) }} Appraisals)
                    </button>
                </h2>
                <div id="collapse{{ $teacherId }}" class="accordion-collapse collapse" data-bs-parent="#teacherAccordion">
                    <div class="accordion-body">
                        @php
                            // Group feedbacks by appraisal_id
                            $appraisals = [];
                            foreach ($feedbacks as $feedback) {
                                $appraisals[$feedback['appraisal_id']][] = $feedback;
                            }
                        @endphp

                        @foreach($appraisals as $appraisalId => $appraisalFeedbacks)
                            @php
                                $appraisal = DB::table('appraisals')->find($appraisalId);
                                $summary = ['Excellent' => 0, 'Good' => 0, 'Average' => 0, 'Poor' => 0];
                                foreach ($appraisalFeedbacks as $feedback) {
                                    foreach ($feedback['answers'] as $answer) {
                                        if (isset($summary[$answer])) {
                                            $summary[$answer]++;
                                        }
                                    }
                                }
                            @endphp
                            
                            <h4>Appraisal: {{ $appraisal->title }}</h4>
                            <div class="answer-summary">
                                <p><strong>Answer Summary:</strong></p>
                                <ul>
                                    <li>🌟 Excellent: {{ $summary['Excellent'] }}</li>
                                    <li>👍 Good: {{ $summary['Good'] }}</li>
                                    <li>😐 Average: {{ $summary['Average'] }}</li>
                                    <li>👎 Poor: {{ $summary['Poor'] }}</li>
                                </ul>
                                <button class="btn btn-primary toggle-students" data-target="#students{{ $teacherId }}_{{ $appraisalId }}">View Student Responses</button>
                            </div>

                            <div id="students{{ $teacherId }}_{{ $appraisalId }}" class="students-list d-none">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Answers</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($appraisalFeedbacks as $feedback)
                                        @php
                                            $student = DB::table('users')->where('id', $feedback['student_id'])->first();
                                            $user_image = get_user_image($student->id);
                                        @endphp
                                            <tr>
                                                <td>
                                                    <div class="dAdmin_profile_img">
                                                        <img class="img-fluid rounded-circle" width="50" height="50" src="{{ $user_image }}" />
                                                    </div>
                                                    {{ $student->name }}
                                                </td>
                                                <td>
                                                    <ul>
                                                        @foreach($feedback['answers'] as $index => $answer)
                                                            @if ($appraisal->ans_type == 'rating')
                                                                <li>Q{{ $index+1 }}:
                                                                    <div class="rating-stars">
                                                                        @for($i = 1; $i <= 5; $i++)
                                                                            <i class="bi bi-star {{ $i <= $answer ? 'active' : '' }}"></i>
                                                                        @endfor
                                                                    </div>
                                                                </li>
                                                            @else
                                                                <li>Q{{ $index+1 }}: {{ $answer }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div> --}}

<!-- JavaScript for Rating Stars -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".rating-stars i").forEach(star => {
        star.addEventListener("click", function () {
            let selectedValue = this.getAttribute("data-value");
            let parentDiv = this.parentElement;
            let inputField = parentDiv.querySelector("input[type='hidden']");

            // Highlight stars up to the selected one
            parentDiv.querySelectorAll("i").forEach(s => {
                s.classList.remove("active");
                if (s.getAttribute("data-value") <= selectedValue) {
                    s.classList.add("active");
                }
            });

            // Update hidden input field
            if (inputField) {
                inputField.value = selectedValue;
                console.log("Updated rating:", inputField.value); // Debugging
            }
        });
    });

    // Ensure form submits the correct rating values
    document.querySelector("form").addEventListener("submit", function (e) {
        document.querySelectorAll(".rating-stars").forEach(starContainer => {
            let inputField = starContainer.querySelector("input[type='hidden']");

            if (inputField && inputField.value === "0") {
                alert("⚠️ Please select a rating before submitting!");
                e.preventDefault(); // Prevent form submission
            }
        });
    });
});
</script>


@endsection
