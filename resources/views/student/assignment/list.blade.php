@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('My Assignments') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="#">{{ get_phrase('Academic') }}</a></li><li><a href="#">{{ get_phrase('Assignments') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Title') }}</th><th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Due Date') }}</th><th>{{ get_phrase('Marks') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Actions') }}</th></tr></thead>
            <tbody>
            @forelse($assignments as $i => $a)
            @php $mySub = $a->submissions->where('student_id', auth()->id())->first(); @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>
                    <strong>{{ $a->title }}</strong>
                    @if($a->description)<br><small class="text-muted">{{ Str::limit($a->description,60) }}</small>@endif
                </td>
                <td>{{ optional($a->subject)->name ?? '—' }}</td>
                <td class="{{ $a->due_date && $a->due_date->isPast() && !$mySub ? 'text-danger' : '' }}">{{ $a->due_date?->format('d M Y') }}</td>
                <td>{{ $mySub ? ($mySub->marks_obtained ?? '?').'/'.$a->total_marks : '—/'.$a->total_marks }}</td>
                <td>
                    @if($mySub)
                        <span class="badge bg-{{ $mySub->status=='graded'?'success':($mySub->status=='submitted'?'info':'secondary') }}">{{ ucfirst($mySub->status) }}</span>
                    @elseif($a->due_date && $a->due_date->isPast())
                        <span class="badge bg-danger">{{ get_phrase('Overdue') }}</span>
                    @else
                        <span class="badge bg-warning">{{ get_phrase('Pending') }}</span>
                    @endif
                </td>
                <td>
                    @if(!$mySub && (!$a->due_date || $a->due_date->isFuture()))
                        <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('student.assignments.modal', $a->id) }}', '{{ get_phrase('Submit Assignment') }}')"><i class="bi bi-upload"></i> {{ get_phrase('Submit') }}</a>
                    @elseif($mySub && $mySub->file_path)
                        <a href="{{ asset($mySub->file_path) }}" target="_blank" class="eBtn eBtn-sm eBtn-secondary"><i class="bi bi-download"></i></a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('No assignments') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
