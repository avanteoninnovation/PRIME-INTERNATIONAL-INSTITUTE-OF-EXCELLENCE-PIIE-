@extends('admin.navigation')

@section('content')
<div class="rbac">
    @include('admin.rbac._header', ['title' => get_phrase('Account Access'), 'crumb' => $member->name, 'tab' => 'staff'])

    @if (session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
    @if (session('error'))<div class="alert alert-warning">{{ session('error') }}</div>@endif

    <div class="rbac-card">
        <h5>{{ get_phrase('Account Access') }}</h5>
        <p class="rbac-muted">{{ get_phrase('Send a secure link so this staff member can choose their own password. No password is sent by email.') }}</p>
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="rbac-muted small">{{ get_phrase('Email') }}</div>
                <div class="text-break">{{ $member->email }}</div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="rbac-muted small">{{ get_phrase('Login account') }}</div>
                <div>{{ $member->account_status === 'disable' ? get_phrase('Disabled') : get_phrase('Enabled') }}</div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="rbac-muted small">{{ get_phrase('Staff status') }}</div>
                <div>{{ get_phrase(ucwords(str_replace('_', ' ', $member->staff_status ?: 'active'))) }}</div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="rbac-muted small">{{ get_phrase('Password setup') }}</div>
                <div>{{ $setupPending ? get_phrase('Setup required') : get_phrase('Password established') }}</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.rbac.staff.account-access.send', $member->id) }}">
            @csrf
            <button type="submit" class="btn btn-primary">
                {{ session('setup_link_sent') ? get_phrase('Resend Password Setup Link') : get_phrase('Send Password Setup Link') }}
            </button>
            <a href="{{ route('admin.rbac.staff.show', $member->id) }}" class="btn btn-outline-secondary">{{ get_phrase('Back to Manage Access') }}</a>
        </form>
    </div>
</div>
@endsection
