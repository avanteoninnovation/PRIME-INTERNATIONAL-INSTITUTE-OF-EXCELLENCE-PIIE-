@php
    use App\Models\School;
    $school = !empty($data['school_id']) ? School::find($data['school_id']) : null;
    $institution = $school->title ?? get_settings('system_title') ?? 'Prime International Institute of Excellence';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ get_phrase('Class Reminder') }}</title>
</head>
<body style="margin:0; padding:0; font-family:'Cabin', Arial, sans-serif; background:#f5f6f8;">
    <div style="padding: 30px 12px;">
        <table style="background:#fff; box-shadow: rgba(100,100,111,.15) 0 7px 29px 0; padding: 40px 34px; margin:auto; width:600px; max-width:100%; border-radius:10px;" cellpadding="0" cellspacing="0">
            <tbody>
                <tr>
                    <td>
                        <p style="color:#0C141D; font-size:20px; font-weight:700; margin:0;">{{ $institution }}</p>
                        <h3 style="font-size:18px; color:#0C141D; margin:20px 0 0;">
                            {{ get_phrase('Your class') }} "{{ $data['class_title'] }}" {{ $data['window_label'] }}
                        </h3>
                        <p style="font-size:15px; color:#7B7F84; margin-top:10px;">{{ get_phrase('Dear') }} {{ $data['student_name'] }},</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table style="border:1px solid #E4E7EC; margin-top:10px; width:100%;" cellpadding="10" cellspacing="0">
                            @if(!empty($data['subject']))
                                <tr><td>{{ get_phrase('Course') }}: <strong>{{ $data['subject'] }}</strong></td></tr>
                            @endif
                            @if(!empty($data['teacher_name']))
                                <tr><td>{{ get_phrase('Lecturer') }}: <strong>{{ $data['teacher_name'] }}</strong></td></tr>
                            @endif
                            <tr><td>{{ get_phrase('Date') }}: <strong>{{ $data['date'] }}</strong></td></tr>
                            <tr><td>{{ get_phrase('Time') }}: <strong>{{ $data['time'] }}</strong></td></tr>
                        </table>
                    </td>
                </tr>
                @if(!empty($data['join_url']))
                <tr style="text-align:center;">
                    <td style="padding-top:24px;">
                        <a href="{{ $data['join_url'] }}" target="_blank" style="display:inline-block; background:#1a3a6b; color:#fff; text-decoration:none; padding:12px 26px; border-radius:6px; font-weight:600;">
                            {{ get_phrase('Join Class') }}
                        </a>
                    </td>
                </tr>
                @endif
                <tr>
                    <td style="padding-top:30px;">
                        <p style="font-size:14px; color:#7B7F84;">{{ get_phrase('Regards') }},<br>{{ $institution }}</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
