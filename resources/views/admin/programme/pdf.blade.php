<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #222; }
        h2 { text-align: center; margin-bottom: 4px; }
        .sub { text-align: center; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #e0e0e0; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h2>{{ get_phrase('Programmes') }}</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>{{ get_phrase('Code') }}</th>
                <th>{{ get_phrase('Name') }}</th>
                <th>{{ get_phrase('Level') }}</th>
                <th>{{ get_phrase('Mode') }}</th>
                <th>{{ get_phrase('Duration') }}</th>
                <th>{{ get_phrase('Tuition Fee (UGX)') }}</th>
                <th>{{ get_phrase('Students') }}</th>
                <th>{{ get_phrase('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($programmes as $i => $programme)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $programme->code }}</td>
                <td>{{ $programme->name }}</td>
                <td>{{ $programme->level }}</td>
                <td>{{ $programme->mode }}</td>
                <td>{{ $programme->duration }}</td>
                <td>{{ number_format($programme->tuition_fee, 2) }}</td>
                <td>{{ $programme->activeStudentCount() }}</td>
                <td>{{ $programme->is_active ? get_phrase('Active') : get_phrase('Inactive') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
