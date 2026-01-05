@extends('admin.navigation')
   
@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div
              class="d-flex justify-content-between align-items-center flex-wrap gr-15"
            >
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Student Feedback') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Appraisal') }}</a></li>
                        <li><a href="#">{{ get_phrase('Student Feedback') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap-2">
            <div class="container mt-5">
                @php
                    $groupedFeedbacks = [];
                    foreach ($feedbacks as $feedback) {
                        $answers = json_decode($feedback->answers, true);
                        foreach ($answers as $teacherId => $teacherAnswers) {
                            $groupedFeedbacks[$teacherId][$feedback->apprasial_id][] = [
                                'student_id' => $feedback->student_id,
                                'answers' => $teacherAnswers
                            ];
                        }
                    }
                @endphp
                 @if(count($groupedFeedbacks) > 0)
                <div class="accordion" id="teacherAccordion">
                    @foreach($groupedFeedbacks as $teacherId => $appraisals)
                        @php $teacher = DB::table('users')->where('id', $teacherId)->first(); 
                            if ($teacher) {
                                $info = json_decode($teacher->user_information, true);
                                $user_image = !empty($info['photo']) ? 'uploads/user-images/'.$info['photo'] : 'uploads/user-images/thumbnail.png';
                                $teachers[] = [
                                    'id' => $teacherId,
                                    'name' => $teacher->name,
                                    'image' => $user_image
                                ];
                            }
                        @endphp
                        <div class="accordion-item mb-3">
                            <div class="accordion-header">
                                <button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#teacher{{ $teacherId }}">
                                    <img class="img-fluid rounded-circle mx-3" width="50" height="50" src="{{ asset('assets/' . $user_image) }}" />   {{ $teacher->name }}
                                </button>
                            </div>
                            <div id="teacher{{ $teacherId }}" class="accordion-collapse collapse" data-bs-parent="#teacherAccordion">
                                <div class="accordion-body">
                                    <div class="accordion" id="appraisalAccordion{{ $teacherId }}">
                                        @foreach($appraisals as $appraisalId => $feedbacks)
                                            @php
                                                $appraisal = DB::table('appraisals')->find($appraisalId);
                                                $appraisalSummary = [];
                                                
                                                foreach ($feedbacks as $feedback) {
                                                    foreach ($feedback['answers'] as $index => $answer) {
                                                        $appraisalSummary[$index][$answer] = ($appraisalSummary[$index][$answer] ?? 0) + 1;
                                                    }
                                                }
                                            @endphp
                                            @if (!empty($appraisal))
                                            <div class="accordion-item mb-3">
                                                <div class="accordion-header">
                                                    <button class="accordion-button" data-bs-toggle="collapse" data-bs-target="#appraisal{{ $appraisalId }}">
                                                        {{ $appraisal->title }}
                                                    </button>
                                                </div>
                                                <div id="appraisal{{ $appraisalId }}" class="accordion-collapse collapse" data-bs-parent="#appraisalAccordion{{ $teacherId }}">
                                                    <div class="accordion-body">
                                                       
                                                        <span>📊 {{get_phrase('Answer Summary')}}</span>
                                                            @if ($appraisal->ans_type == 'mcq')
                                                            <table class="table table-sm table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>{{get_phrase('Question')}}</th>
                                                                        <th>{{get_phrase('Excellent')}}</th>
                                                                        <th>{{get_phrase('Good')}}</th>
                                                                        <th>{{get_phrase('Average')}}</th>
                                                                        <th>{{get_phrase('Poor')}}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($appraisalSummary as $questionIndex => $summary)
                                                                    @php
                                                                        $questions = json_decode($appraisal->question, true);
                                                                        $questionText = $questions[$questionIndex] ?? 'Q'.($questionIndex+1);
                                                                    @endphp
                                                                        <tr>
                                                                            <td style="max-width: 173px;">{{ 'Q'.($questionIndex+1).': '.$questionText }}</td>
                                                                            <td>{{ $summary['Excellent'] ?? 0 }}</td>
                                                                            <td>{{ $summary['Good'] ?? 0 }}</td>
                                                                            <td>{{ $summary['Average'] ?? 0 }}</td>
                                                                            <td>{{ $summary['Poor'] ?? 0 }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        
                                                        @elseif($appraisal->ans_type == 'rating')
                                                            <table class="table table-sm table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>{{get_phrase('Question')}}</th>
                                                                        <th>⭐⭐⭐⭐⭐</th>
                                                                        <th>⭐⭐⭐⭐</th>
                                                                        <th>⭐⭐⭐</th>
                                                                        <th>⭐⭐</th>
                                                                        <th>⭐</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($appraisalSummary as $questionIndex => $summary)
                                                                    @php
                                                                        $questions = json_decode($appraisal->question, true);
                                                                        $questionText = $questions[$questionIndex] ?? 'Q'.($questionIndex+1);
                                                                    @endphp
                                                                    <tr>
                                                                        <td style="max-width: 173px;">{{ 'Q'.($questionIndex+1).': '.$questionText }}</td>
                                                                            <td>{{ $summary['5'] ?? 0 }}</td>
                                                                            <td>{{ $summary['4'] ?? 0 }}</td>
                                                                            <td>{{ $summary['3'] ?? 0 }}</td>
                                                                            <td>{{ $summary['2'] ?? 0 }}</td>
                                                                            <td>{{ $summary['1'] ?? 0 }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        
                                                        @elseif($appraisal->ans_type == 'binary')
                                                            <table class="table table-sm table-bordered">
                                                                <thead>
                                                                    <tr>
                                                                        <th>{{get_phrase('Question')}}</th>
                                                                        <th>{{get_phrase('Yes')}}</th>
                                                                        <th>{{get_phrase('No')}}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($appraisalSummary as $questionIndex => $summary)
                                                                    @php
                                                                        $questions = json_decode($appraisal->question, true);
                                                                        $questionText = $questions[$questionIndex] ?? 'Q'.($questionIndex+1);
                                                                    @endphp
                                                                    <tr>
                                                                        <td style="max-width: 173px;">{{ 'Q'.($questionIndex+1).': '.$questionText }}</td>
                                                                            <td>{{ $summary['Yes'] ?? 0 }}</td>
                                                                            <td>{{ $summary['No'] ?? 0 }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                            @elseif($appraisal->ans_type == 'text')
                                                                @php
                                                                    $questions = json_decode($appraisal->question, true);
                                                                @endphp
                                                                <table class="table table-sm table-bordered">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>{{ get_phrase('Question') }}</th>
                                                                            <th>{{ get_phrase('Student') }}</th>
                                                                            <th>{{ get_phrase('Answer') }}</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($feedbacks as $feedback)
                                                                            @php
                                                                                $student = DB::table('users')->where('id', $feedback['student_id'])->first();
                                                                            @endphp
                                                                            @foreach($feedback['answers'] as $index => $answer)
                                                                                <tr>
                                                                                    <td>{{ 'Q'.($index+1).': '.($questions[$index] ?? '') }}</td>
                                                                                    <td>{{ $student->name }}</td>
                                                                                    <td>{{ $answer }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            @endif
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                @else
                <div class="empty_box center">
                    <img class="mb-3" width="150px" src="{{ asset('assets/images/empty_box.png') }}" />
                    <br>
                    <span class="">{{ get_phrase('No data found') }}</span>
                </div> 
                @endif
            </div>
        </div>
    </div>
</div>

@endsection