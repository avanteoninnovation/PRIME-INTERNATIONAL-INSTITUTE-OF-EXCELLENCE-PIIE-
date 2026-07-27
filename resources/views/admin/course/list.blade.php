@extends('admin.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Courses') }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="#">{{ get_phrase('Home') }}</a></li>
                        <li><a href="#">{{ get_phrase('Academic') }}</a></li>
                        <li><a href="#">{{ get_phrase('Courses') }}</a></li>
                    </ul>
                </div>
                <div class="export-btn-area d-flex gap-2">
                    <a href="{{ route('admin.courses.export', ['search' => $search]) }}" class="export_btn bg-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
                    <a href="{{ route('admin.courses.export_excel', ['search' => $search]) }}" class="export_btn bg-secondary"><i class="bi bi-file-earmark-excel"></i> {{ get_phrase('Export Excel') }}</a>
                    <a href="{{ route('admin.courses.print', ['search' => $search, 'inline' => 1]) }}" target="_blank" class="export_btn bg-secondary"><i class="bi bi-printer"></i> {{ get_phrase('Print') }}</a>
                    <a href="{{ route('admin.courses.print', ['search' => $search]) }}" class="export_btn bg-secondary"><i class="bi bi-file-earmark-pdf"></i> {{ get_phrase('Export PDF') }}</a>
                    <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.courses.open_modal') }}', '{{ get_phrase('Add Course') }}')">{{ get_phrase('Add Course') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <div class="search-filter-area d-flex justify-content-between align-items-center flex-wrap gr-15 mb-3">
                <form action="{{ route('admin.courses.index') }}">
                    <div class="search-input d-flex align-items-center">
                        <input type="text" name="search" value="{{ $search }}" placeholder="{{ get_phrase('Search courses') }}" class="form-control eForm-control">
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table eTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ get_phrase('Code') }}</th>
                            <th>{{ get_phrase('Name') }}</th>
                            <th>{{ get_phrase('Programme') }}</th>
                            <th>{{ get_phrase('Credit') }}</th>
                            <th>{{ get_phrase('Type') }}</th>
                            <th>{{ get_phrase('Level') }}</th>
                            <th>{{ get_phrase('CATS') }}</th>
                            <th>{{ get_phrase('EXAM') }}</th>
                            <th>{{ get_phrase('Pass Mark') }}</th>
                            <th>{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($courses as $i => $course)
                        <tr>
                            <td>{{ $courses->firstItem() + $i }}</td>
                            <td><strong>{{ $course->code }}</strong></td>
                            <td>{{ $course->name }}</td>
                            <td>{{ optional($course->programme)->name ?? '—' }}</td>
                            <td>{{ $course->credits }}</td>
                            <td>{{ ucfirst($course->course_type) }}</td>
                            <td><span class="badge bg-primary">{{ $course->level }}</span></td>
                            <td>{{ $course->cats_marks }}</td>
                            <td>{{ $course->exam_marks }}</td>
                            <td>{{ $course->pass_mark }}</td>
                            <td>
                                <a href="javascript:;" class="eBtn eBtn-sm eBtn-primary" onclick="rightModal('{{ route('admin.courses.open_modal', ['id' => $course->id]) }}', '{{ get_phrase('Edit Course') }}')"><i class="bi bi-pencil"></i></a>
                                <a href="{{ route('admin.courses.marks', $course->id) }}" class="eBtn eBtn-sm eBtn-info"><i class="bi bi-journal-text"></i></a>
                                <a href="{{ route('admin.courses.destroy', $course->id) }}" class="eBtn eBtn-sm eBtn-danger" onclick="return confirm('{{ get_phrase('Delete this course?') }}')"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">{{ get_phrase('No courses found') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $courses->withQueryString()->links() }}</div>
        </div>
    </div>
</div>
@endsection
