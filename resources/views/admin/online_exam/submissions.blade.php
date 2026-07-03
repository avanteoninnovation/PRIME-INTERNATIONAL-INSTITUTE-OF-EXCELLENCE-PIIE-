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
            <thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Score') }}</th><th>{{ get_phrase('Total') }}</th><th>{{ get_phrase('Percentage') }}</th><th>{{ get_phrase('Result') }}</th><th>{{ get_phrase('Submitted At') }}</th></tr></thead>
            <tbody>
            @forelse($submissions as $i => $sub)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($sub->student)->name }}</td>
                <td>{{ $sub->score }}</td>
                <td>{{ $sub->total_marks }}</td>
                <td>{{ $sub->total_marks > 0 ? round(($sub->score/$sub->total_marks)*100,1) : 0 }}%</td>
                <td><span class="badge bg-{{ $sub->passed ? 'success':'danger' }}">{{ $sub->passed ? get_phrase('Pass') : get_phrase('Fail') }}</span></td>
                <td>{{ $sub->created_at?->format('d M Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('No submissions yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
