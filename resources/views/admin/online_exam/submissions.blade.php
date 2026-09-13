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
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Attempt') }}</th><th>{{ get_phrase('Score') }}</th><th>{{ get_phrase('Total') }}</th><th>{{ get_phrase('Percentage') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Result') }}</th><th>{{ get_phrase('Submitted At') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($submissions as $i => $sub)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($sub->student)->name }}</td>
                <td>{{ $sub->attempt_no }}</td>
                <td>{{ $sub->score }}</td>
                <td>{{ $sub->total_marks_snapshot }}</td>
                <td>{{ $sub->total_marks_snapshot > 0 ? round(($sub->score/$sub->total_marks_snapshot)*100,1) : 0 }}%</td>
                <td><span class="badge bg-secondary">{{ get_phrase(ucwords(str_replace('_',' ',$sub->status))) }}</span></td>
                <td>
                    @if(in_array($sub->status, ['finalized','submitted','timed_out']))
                        <span class="badge bg-{{ $sub->passed ? 'success':'danger' }}">{{ $sub->passed ? get_phrase('Pass') : get_phrase('Fail') }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $sub->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
                <td>
                    <a href="{{ route('admin.online_exams.proctoring.review', ['id' => $exam->id, 'submission' => $sub->id]) }}" class="eBtn eBtn-sm eBtn-secondary" title="{{ get_phrase('Review Proctoring') }}"><i class="bi bi-camera-video"></i></a>
                    <a href="{{ route('admin.online_exams.results', $exam->id) }}" class="eBtn eBtn-sm eBtn-primary" title="{{ get_phrase('Mark / Finalize') }}"><i class="bi bi-check2-square"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center text-muted py-4">{{ get_phrase('No submissions yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
