<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ get_phrase('Set up your PIIE staff account') }}</title>
</head>
<body style="font-family:Arial,sans-serif;color:#243447;line-height:1.5">
    <h2>{{ get_phrase('Set up your PIIE staff account') }}</h2>
    <p>{{ get_phrase('Hello') }} {{ $staffName }},</p>
    <p>{{ get_phrase('An administrator has invited you to set a password for your institutional staff account.') }}</p>
    <p><a href="{{ $setupUrl }}">{{ get_phrase('Choose your password') }}</a></p>
    <p>{{ get_phrase('This single-use link expires in 60 minutes. If you were not expecting it, contact your school administrator.') }}</p>
</body>
</html>
