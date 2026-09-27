@extends('layouts.signin_page')

@section('content')
<div class="container py-5" style="max-width:560px">
    <h1 class="h3 mb-2">{{ get_phrase('Set up your staff password') }}</h1>
    <p class="text-muted">{{ get_phrase('Choose a password to activate your PIIE staff account.') }}</p>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $message)<div>{{ $message }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="mb-3">
            <label for="email" class="form-label">{{ get_phrase('Email') }} *</label>
            <input id="email" type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required autocomplete="username" readonly>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">{{ get_phrase('New password') }} *</label>
            <input id="password" type="password" name="password" class="form-control" required autocomplete="new-password">
        </div>
        <div class="mb-3">
            <label for="password-confirmation" class="form-label">{{ get_phrase('Confirm new password') }} *</label>
            <input id="password-confirmation" type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
        </div>
        <p class="small text-muted">{{ get_phrase('This link can be used once and expires after 60 minutes.') }}</p>
        <button type="submit" class="btn btn-primary">{{ get_phrase('Set password and continue') }}</button>
    </form>
</div>
@endsection
