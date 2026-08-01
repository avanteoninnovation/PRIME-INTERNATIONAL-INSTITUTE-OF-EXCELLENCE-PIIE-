@extends('applicant.layout')

@section('title', get_phrase('Track My Application'))
@section('subtitle', get_phrase('Every update on your application, newest at the bottom.'))

@section('content')

<div class="row g-4">
    <div class="col-lg-8">
        <div class="ap-card">
            <div class="ap-card-head">
                <h2 class="ap-card-title"><i class="bi bi-activity"></i> {{ get_phrase('Application Timeline') }}</h2>
                <span class="ap-pill bg-{{ $admission->statusColor() }} bg-opacity-10 text-{{ $admission->statusColor() }}">
                    {{ $admission->statusLabel() }}
                </span>
            </div>

            @if($events->isNotEmpty())
                <div class="ap-timeline">
                    @foreach($events as $event)
                        <div class="ap-timeline-item {{ $loop->last ? 'is-current' : 'is-done' }}">
                            <span class="ap-timeline-dot">
                                <i class="bi {{ $loop->last ? 'bi-record-circle' : 'bi-check-lg' }}"></i>
                            </span>
                            <p class="ap-timeline-title">{{ $event->title }}</p>
                            <p class="ap-timeline-meta">
                                {{ $event->created_at->format('d M Y, H:i') }}
                                @if($event->actor_type === 'staff')
                                    · {{ get_phrase('Admissions Office') }}
                                @elseif($event->actor_type === 'applicant')
                                    · {{ get_phrase('You') }}
                                @endif
                            </p>
                            @if($event->note)
                                <p class="ap-timeline-note">{{ $event->note }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="ap-empty">
                    <i class="bi bi-clock-history"></i>
                    <p class="mb-0">{{ get_phrase('Nothing has happened on your application yet.') }}</p>
                </div>
            @endif
        </div>

        @if($history->isNotEmpty())
            <div class="ap-card mt-4">
                <div class="ap-card-head">
                    <h2 class="ap-card-title"><i class="bi bi-archive"></i> {{ get_phrase('Previous Applications') }}</h2>
                </div>

                @foreach($history as $previous)
                    <div class="d-flex flex-wrap align-items-center gap-3 py-3" style="border-bottom:1px solid #f1f2f4;">
                        <div class="flex-grow-1">
                            <div style="font-weight:700; font-size:14.5px;">{{ $previous->app_number }}</div>
                            <div style="color:var(--ap-muted); font-size:13px;">
                                {{ optional($previous->programme)->name ?: get_phrase('No programme selected') }}
                                · {{ $previous->created_at->format('M Y') }}
                            </div>
                        </div>
                        <span class="ap-pill bg-{{ $previous->statusColor() }} bg-opacity-10 text-{{ $previous->statusColor() }}">
                            {{ $previous->statusLabel() }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="ap-card">
            <div class="ap-card-head">
                <h2 class="ap-card-title"><i class="bi bi-info-circle"></i> {{ get_phrase('At a Glance') }}</h2>
            </div>

            <dl class="mb-0">
                <div class="ap-kv"><dt>{{ get_phrase('Application Number') }}</dt><dd>{{ $admission->app_number }}</dd></div>
                <div class="ap-kv"><dt>{{ get_phrase('Programme') }}</dt><dd>{{ optional($admission->programme)->name ?: '—' }}</dd></div>
                <div class="ap-kv"><dt>{{ get_phrase('Intake') }}</dt><dd>{{ optional($admission->intakeSession)->name ?: '—' }}</dd></div>
                <div class="ap-kv"><dt>{{ get_phrase('Submitted') }}</dt>
                    <dd>{{ $admission->submitted_at ? $admission->submitted_at->format('d M Y') : get_phrase('Not yet') }}</dd>
                </div>
                <div class="ap-kv"><dt>{{ get_phrase('Progress') }}</dt><dd>{{ $percent }}%</dd></div>
                <div class="ap-kv"><dt>{{ get_phrase('Application Fee') }}</dt><dd>{{ get_phrase(ucfirst($admission->fee_status)) }}</dd></div>
            </dl>

            <div class="d-grid gap-2 mt-4">
                <a href="{{ route('applicant.summary') }}" class="ap-btn ap-btn-ghost">
                    <i class="bi bi-file-text"></i> {{ get_phrase('View Full Application') }}
                </a>
                @if(in_array($admission->status, ['accepted', 'enrolled']))
                    <a href="{{ route('applicant.offer_letter') }}" class="ap-btn ap-btn-accent">
                        <i class="bi bi-download"></i> {{ get_phrase('Download Offer Letter') }}
                    </a>
                @endif
            </div>
        </div>

        @if($admission->decision_note)
            <div class="ap-card mt-4">
                <div class="ap-card-head">
                    <h2 class="ap-card-title"><i class="bi bi-chat-left-text"></i> {{ get_phrase('Note from the Admissions Office') }}</h2>
                </div>
                <p class="mb-0" style="font-size:14.5px; white-space:pre-line;">{{ $admission->decision_note }}</p>
            </div>
        @endif
    </div>
</div>

@endsection
