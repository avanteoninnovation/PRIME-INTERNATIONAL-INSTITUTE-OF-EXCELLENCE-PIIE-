<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ get_phrase('ID Card') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2933;
            margin: 28px;
        }
        .card {
            width: 340px;
            border: 1px solid #d9e2ec;
            border-radius: 8px;
            padding: 16px;
            margin: 0 auto;
            text-align: center;
        }
        .card img.logo {
            width: 48px;
            height: 48px;
        }
        .card .school-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a3a6b;
            margin: 6px 0 10px;
        }
        .card img.photo {
            border-radius: 50%;
            width: 80px;
            height: 80px;
            object-fit: cover;
        }
        .card h2 {
            font-size: 15px;
            margin: 8px 0 10px;
        }
        table.details {
            width: 100%;
            font-size: 11px;
            text-align: left;
            border-top: 1px solid #d9e2ec;
            padding-top: 8px;
        }
        table.details td {
            padding: 3px 0;
        }
        table.details td.label {
            color: #52606d;
            width: 100px;
        }
    </style>
</head>
<body>
    @php
        $schoolData = App\Models\School::where('id', auth()->user()->school_id)->first();
        $logoPath = !empty($schoolData->school_logo)
            ? public_path('assets/uploads/school_logo/' . $schoolData->school_logo)
            : public_path('assets/images/id_logo.png');
    @endphp
    <div class="card">
        @if(file_exists($logoPath))
            <img class="logo" src="{{ $logoPath }}">
        @endif
        <div class="school-title">{{ optional($schoolData)->title }}</div>

        @php
            $photoPath = str_replace(asset(''), public_path(''), (string) $student_details['photo']);
        @endphp
        @if(file_exists($photoPath))
            <img class="photo" src="{{ $photoPath }}">
        @endif

        <h2>{{ $student_details['name'] }}</h2>

        <table class="details">
            <tr><td class="label">{{ get_phrase('Code') }}</td><td>{{ null_checker($student_details['code']) }}</td></tr>
            @if($programme)
                <tr><td class="label">{{ get_phrase('Programme') }}</td><td>{{ $programme->name }}</td></tr>
            @else
                <tr><td class="label">{{ get_phrase('Class') }}</td><td>{{ null_checker($student_details['class_name']) }}</td></tr>
                <tr><td class="label">{{ get_phrase('Section') }}</td><td>{{ null_checker($student_details['section_name']) }}</td></tr>
            @endif
            <tr><td class="label">{{ get_phrase('Blood') }}</td><td>{{ null_checker(strtoupper((string) $student_details['blood_group'])) }}</td></tr>
            <tr><td class="label">{{ get_phrase('Contact') }}</td><td>{{ null_checker($student_details['phone']) }}</td></tr>
        </table>
    </div>
</body>
</html>
