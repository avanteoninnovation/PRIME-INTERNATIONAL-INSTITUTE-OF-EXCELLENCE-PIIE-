@extends('applicant.layout')

@section('title', get_phrase('Application Summary'))
@section('subtitle', get_phrase('A full copy of what you submitted, for your records.'))

@section('content')

<div class="ap-card mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <div style="color:var(--ap-muted); font-size:13px; text-transform:uppercase; letter-spacing:.06em; font-weight:600;">
                {{ get_phrase('Application Number') }}
            </div>
            <div style="font-size:19px; font-weight:700;">{{ $admission->app_number }}</div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="ap-pill bg-{{ $admission->statusColor() }} bg-opacity-10 text-{{ $admission->statusColor() }}">
                {{ $admission->statusLabel() }}
            </span>
            @if(in_array($admission->status, ['accepted', 'enrolled']))
                <a href="{{ route('applicant.offer_letter') }}" class="ap-btn ap-btn-accent">
                    <i class="bi bi-download"></i> {{ get_phrase('Offer Letter') }}
                </a>
            @endif
        </div>
    </div>
</div>

@include('applicant.partials._summary', ['docChecklist' => $checklist])

@endsection
