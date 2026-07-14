@extends('teacher.navigation')

@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12 d-flex justify-content-between align-items-center">
    <h4>{{ get_phrase('Results') }}: {{ $exam->title }}</h4>
    <a class="export_btn" href="{{ route('teacher.online_exams.attempts', $exam->id) }}">{{ get_phrase('Back to Attempts') }}</a>
</div></div></div>

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="eSection-wrap">
    <table class="table eTable">
        <thead>
            <tr>
                <th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Score') }}</th><th>{{ get_phrase('Percentage') }}</th><th>{{ get_phrase('Pass/Fail') }}</th><th>{{ get_phrase('Manual Status') }}</th><th>{{ get_phrase('Finalized') }}</th><th>{{ get_phrase('Release') }}</th><th>{{ get_phrase('Submission Method') }}</th><th>{{ get_phrase('Actions') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $i => $submission)
                @php
                    $totalMarks = (float) ($exam->total_marks ?: 0);
                    $score = (float) ($submission->effective_score ?? 0);
                    $percentage = $totalMarks > 0 ? round(($score / $totalMarks) * 100, 2) : 0;
                    $pendingManual = $submission->status === \App\Models\OnlineExamSubmission::STATUS_PENDING_MANUAL;
                    $isFinalized = $submission->status === \App\Models\OnlineExamSubmission::STATUS_FINALIZED;
                @endphp
                <tr>
                    <td>{{ $submissions->firstItem() + $i }}</td>
                    <td>{{ optional($submission->student)->name ?? '—' }}</td>
                    <td>{{ number_format($score, 2) }}</td>
                    <td>{{ $percentage }}%</td>
                    <td>{{ is_null($submission->passed) ? '—' : ($submission->passed ? 'Pass' : 'Fail') }}</td>
                    <td>{{ $pendingManual ? 'Pending Manual Marking' : 'Completed' }}</td>
                    <td>{{ $isFinalized ? 'Yes' : 'No' }}</td>
                    <td>{{ $submission->isResultVisible() ? 'Released' : 'Hidden' }}</td>
                    <td>{{ $submission->submitted_via ?: '—' }}</td>
                    <td>
                        <a class="eBtn eBtn-sm eBtn-info" href="{{ route('teacher.online_exams.marking') }}">{{ get_phrase('Marking') }}</a>
                        <form method="POST" action="{{ route('teacher.online_exams.results.finalize', $submission->id) }}" class="d-inline">
                            @csrf
                            <button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Finalize') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="text-center text-muted">{{ get_phrase('No results found') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    {{ $submissions->links() }}
</div>
@endsection
