@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Online Exam Results Report') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.reports.index') }}">{{ get_phrase('Reports') }}</a></li>
                <li><a href="#">{{ get_phrase('Exams') }}</a></li>
            </ul>
        </div>
        <a href="{{ route('admin.reports.index') }}" class="eBtn eBtn-secondary"><i class="bi bi-arrow-left"></i> {{ get_phrase('Back') }}</a>
    </div>
</div></div></div>
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable" style="font-size:12px">
            <thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Exam') }}</th><th class="text-center">{{ get_phrase('Score') }}</th><th class="text-center">{{ get_phrase('Total') }}</th><th class="text-center">{{ get_phrase('%') }}</th><th class="text-center">{{ get_phrase('Result') }}</th><th>{{ get_phrase('Submitted') }}</th></tr></thead>
            <tbody>
            @forelse($submissions as $i => $sub)
            @php $pct = $sub->total_marks>0 ? round($sub->score/$sub->total_marks*100,1) : 0; @endphp
            <tr>
                <td>{{ $submissions->firstItem() + $i }}</td>
                <td>{{ optional($sub->student)->name ?? '—' }}</td>
                <td>{{ optional($sub->exam)->title ?? '—' }}</td>
                <td class="text-center">{{ $sub->score }}</td>
                <td class="text-center">{{ $sub->total_marks }}</td>
                <td class="text-center">{{ $pct }}%</td>
                <td class="text-center"><span class="badge bg-{{ $sub->passed?'success':'danger' }}">{{ $sub->passed ? get_phrase('Pass') : get_phrase('Fail') }}</span></td>
                <td>{{ $sub->created_at?->format('d M Y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-4">{{ get_phrase('No exam submissions yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($submissions instanceof \Illuminate\Pagination\LengthAwarePaginator)
        {{ $submissions->links() }}
    @endif
</div></div></div>
@endsection
