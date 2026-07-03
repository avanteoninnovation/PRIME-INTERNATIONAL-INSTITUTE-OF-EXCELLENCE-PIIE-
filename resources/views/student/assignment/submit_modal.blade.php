<div class="eoff-form">
    <form method="POST" class="d-block ajaxForm" action="{{ route('student.assignments.submit', $assignment->id) }}" enctype="multipart/form-data">
        @csrf
        <div class="form-row">
            <div class="fpb-7"><h6>{{ $assignment->title }}</h6>
                @if($assignment->description)<p class="text-muted small">{{ $assignment->description }}</p>@endif
                <div class="d-flex gap-2">
                    <span class="badge bg-info">{{ get_phrase('Due') }}: {{ $assignment->due_date?->format('d M Y') }}</span>
                    <span class="badge bg-secondary">{{ get_phrase('Total Marks') }}: {{ $assignment->total_marks }}</span>
                </div>
            </div>
            <div class="fpb-7 mt-3"><label class="eForm-label">{{ get_phrase('Your Answer / Notes') }}</label>
                <textarea class="form-control eForm-control" name="answer_text" rows="4" placeholder="{{ get_phrase('Write your answer here...') }}"></textarea></div>
            @if($assignment->allow_file)
            <div class="fpb-7 mt-2"><label class="eForm-label">{{ get_phrase('Upload File') }}</label>
                <input type="file" class="form-control eForm-control" name="file"></div>
            @endif
            <div class="fpb-7 pt-3">
                <button class="btn-form" type="submit">{{ get_phrase('Submit Assignment') }}</button>
            </div>
        </div>
    </form>
</div>
