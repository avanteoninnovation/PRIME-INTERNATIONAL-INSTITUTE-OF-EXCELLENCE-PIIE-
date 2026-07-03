@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Submissions') }}: {{ $assignment->title }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('admin.assignments.index') }}">{{ get_phrase('Assignments') }}</a></li><li><a href="#">{{ get_phrase('Submissions') }}</a></li></ul>
        </div>
    </div>
</div></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable">
            <thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th>{{ get_phrase('Submitted At') }}</th><th>{{ get_phrase('File') }}</th><th>{{ get_phrase('Marks') }}</th><th>{{ get_phrase('Status') }}</th><th>{{ get_phrase('Grade') }}</th></tr></thead>
            <tbody>
            @forelse($submissions as $i => $sub)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($sub->student)->name }}</td>
                <td>{{ $sub->submitted_at?->format('d M Y H:i') }}</td>
                <td>@if($sub->file_path)<a href="{{ asset($sub->file_path) }}" target="_blank" class="eBtn eBtn-sm eBtn-primary"><i class="bi bi-download"></i></a>@else—@endif</td>
                <td>
                    <form method="POST" action="{{ route('admin.assignments.grade', $sub->id) }}" class="d-flex gap-1">
                        @csrf
                        <input type="number" class="form-control form-control-sm" name="marks_obtained" value="{{ $sub->marks_obtained }}" min="0" max="{{ $assignment->total_marks }}" style="width:80px">
                        <button class="eBtn eBtn-sm eBtn-primary" type="submit">{{ get_phrase('Save') }}</button>
                    </form>
                </td>
                <td><span class="badge bg-{{ $sub->status=='graded'?'success':($sub->status=='submitted'?'warning':'secondary') }}">{{ ucfirst($sub->status) }}</span></td>
                <td>{{ $sub->grade ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ get_phrase('No submissions yet') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
