@php
    $state = $liveClass->computed_status;
    $label = $state === \App\Models\LiveClass::STATUS_SCHEDULED && !empty($startingSoon)
        ? get_phrase('Starting Soon')
        : match ($state) {
            \App\Models\LiveClass::STATUS_DRAFT => get_phrase('Draft'),
            \App\Models\LiveClass::STATUS_SCHEDULED => get_phrase('Scheduled'),
            \App\Models\LiveClass::STATUS_LIVE => get_phrase('Live Now'),
            \App\Models\LiveClass::STATUS_ENDED => get_phrase('Ended'),
            \App\Models\LiveClass::STATUS_CANCELLED => get_phrase('Cancelled'),
            default => get_phrase('Scheduled'),
        };
    $badgeClass = match ($state) {
        \App\Models\LiveClass::STATUS_DRAFT => 'bg-secondary',
        \App\Models\LiveClass::STATUS_SCHEDULED => 'bg-primary',
        \App\Models\LiveClass::STATUS_LIVE => 'bg-success',
        \App\Models\LiveClass::STATUS_ENDED => 'bg-light text-dark',
        \App\Models\LiveClass::STATUS_CANCELLED => 'bg-danger',
        default => 'bg-secondary',
    };
@endphp
<span class="badge {{ $badgeClass }}">{{ $label }}</span>
