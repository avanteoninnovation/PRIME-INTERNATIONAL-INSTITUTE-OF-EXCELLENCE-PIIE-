@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Submissions') }}: {{ $exam->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.online_exams.index') }}">{{ get_phrase('Online Exams') }}</a></li><li><a href="#">{{ get_phrase('Submissions') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive online-exam-table-wrap">
        <table class="table eTable online-exam-table">
            <thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Attempt') }}</th><th>{{ get_phrase('Automatic') }}</th><th>{{ get_phrase('Manual') }}</th><th>{{ get_phrase('Total') }}</th><th>{{ get_phrase('Maximum') }}</th><th>{{ get_phrase('Pending') }}</th><th>{{ get_phrase('Marking') }}</th><th>{{ get_phrase('Finalization') }}</th><th>{{ get_phrase('Release') }}</th><th>{{ get_phrase('Submitted At') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($submissions as $i => $sub)
            @php
                $summary = \App\Support\OnlineExams\OnlineExamMarking::summary($sub);
                $pending = (int) $summary['pending'];
                $finalized = in_array($sub->status, ['finalized', 'result_published'], true);
                $released = $sub->isResultPublished();
            @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($sub->student)->name }}</td>
                <td>{{ $sub->attempt_no }}</td>
                <td>{{ number_format($summary['objective_score'], 2) }}</td>
                <td>{{ number_format($summary['manual_score'], 2) }}</td>
                <td>{{ number_format($summary['score'], 2) }}</td>
                <td>{{ number_format($sub->total_marks_snapshot, 2) }}</td>
                <td>{{ $pending }}</td>
                <td><span class="badge bg-{{ $pending > 0 ? 'warning text-dark' : 'success' }}">{{ $pending > 0 ? get_phrase('Awaiting Marking') : get_phrase('Marking Complete') }}</span></td>
                <td>{{ $finalized ? get_phrase('Finalized') : get_phrase('Not Finalized') }}</td>
                <td>{{ $released ? get_phrase('Released') : get_phrase('Not Released') }}</td>
                <td>{{ $sub->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
                <td>
                    <a href="{{ route('admin.online_exams.proctoring.review', ['id' => $exam->id, 'submission' => $sub->id]) }}" class="eBtn eBtn-sm eBtn-secondary">{{ get_phrase('Proctoring') }} @if($sub->proctoring_events_count)({{ $sub->proctoring_events_count }})@endif</a>
                    <a href="{{ route('admin.online_exams.results', $exam->id) }}" class="eBtn eBtn-sm eBtn-primary">{{ $pending > 0 ? get_phrase('Mark / Review') : get_phrase('Results & Marking') }}</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="13" class="text-center text-muted py-4">{{ get_phrase('No submissions yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
