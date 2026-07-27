<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h2 { text-align: center; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h2>{{ get_phrase('Courses') }}</h2>

    <table>
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
            </tr>
        </thead>
        <tbody>
            @foreach($courses as $i => $course)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $course->code }}</td>
                <td>{{ $course->name }}</td>
                <td>{{ optional($course->programme)->name }}</td>
                <td>{{ $course->credits }}</td>
                <td>{{ ucfirst($course->course_type) }}</td>
                <td>{{ $course->level }}</td>
                <td>{{ $course->cats_marks }}</td>
                <td>{{ $course->exam_marks }}</td>
                <td>{{ $course->pass_mark }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
