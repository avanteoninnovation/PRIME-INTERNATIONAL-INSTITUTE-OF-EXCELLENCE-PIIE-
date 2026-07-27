@extends('admin.navigation')

@section('content')
<div class="mainSection-title">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                <div class="d-flex flex-column">
                    <h4>{{ get_phrase('Course Marks') }} — {{ $course->code }} {{ $course->name }}</h4>
                    <ul class="d-flex align-items-center eBreadcrumb-2">
                        <li><a href="{{ route('admin.courses.index') }}">{{ get_phrase('Courses') }}</a></li>
                        <li><a href="#">{{ get_phrase('Marks') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <p class="text-muted">
                {{ get_phrase('CAT max') }}: <strong>{{ $course->cats_marks }}</strong> &nbsp;|&nbsp;
                {{ get_phrase('EXAM max') }}: <strong>{{ $course->exam_marks }}</strong> &nbsp;|&nbsp;
                {{ get_phrase('Pass Mark') }}: <strong>{{ $course->pass_mark }}</strong>
            </p>

            <form method="POST" action="{{ route('admin.courses.marks.update', $course->id) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table eTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ get_phrase('Student') }}</th>
                                <th>{{ get_phrase('CAT') }} (/{{ $course->cats_marks }})</th>
                                <th>{{ get_phrase('EXAM') }} (/{{ $course->exam_marks }})</th>
                                <th>{{ get_phrase('Total') }}</th>
                                <th>{{ get_phrase('Pass/Fail') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $i => $profile)
                            @php
                                $existing = $marksByStudent[$profile->user_id] ?? null;
                                $obtained = $existing['obtained'] ?? null;
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ optional($profile->user)->name }}</td>
                                <td>
                                    <input type="number" class="form-control eForm-control" name="cats[{{ $profile->user_id }}]"
                                        min="0" max="{{ $course->cats_marks }}" value="{{ old("cats.{$profile->user_id}", $existing['cats'] ?? '') }}">
                                </td>
                                <td>
                                    <input type="number" class="form-control eForm-control" name="exam[{{ $profile->user_id }}]"
                                        min="0" max="{{ $course->exam_marks }}" value="{{ old("exam.{$profile->user_id}", $existing['exam'] ?? '') }}">
                                </td>
                                <td>{{ $obtained ?? '—' }}</td>
                                <td>
                                    @if(is_numeric($obtained))
                                        @if($obtained >= $course->pass_mark)
                                            <span class="badge bg-success">{{ get_phrase('Pass') }}</span>
                                        @else
                                            <span class="badge bg-danger">{{ get_phrase('Fail') }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No students enrolled in this programme') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($students->isNotEmpty())
                <button class="btn-form mt-3" type="submit">{{ get_phrase('Save Marks') }}</button>
                @endif
            </form>
        </div>
    </div>
</div>
@endsection
