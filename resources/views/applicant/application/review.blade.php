@extends('applicant.layout')

@section('title', get_phrase('Review & Submit'))
@section('subtitle', get_phrase('Check everything below, then send your application to the admissions office.'))

@section('content')

@include('applicant.application._stepper')

@if($admission->submitted_at)
    <div class="alert alert-success d-flex align-items-start gap-3">
        <i class="bi bi-check-circle-fill fs-4"></i>
        <div>
            <strong>{{ get_phrase('Your application was submitted on') }} {{ $admission->submitted_at->format('d M Y, H:i') }}.</strong>
            <p class="mb-0">{{ get_phrase('You can follow its progress under Track My Application.') }}</p>
        </div>
    </div>
@elseif(! $canSubmit)
    <div class="alert alert-warning">
        <strong class="d-block mb-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            {{ get_phrase('A few things still need your attention before you can submit') }}:
        </strong>
        <ul class="mb-0">
            @foreach($blockers as $blocker)
                <li>{{ $blocker }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('applicant.partials._summary')

@unless($readOnly)
    <div class="ap-card mt-4">
        <div class="ap-card-head">
            <h2 class="ap-card-title"><i class="bi bi-send-check"></i> {{ get_phrase('Declaration') }}</h2>
        </div>

        <form action="{{ route('applicant.application.submit') }}" method="POST">
            @csrf

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="declaration" id="declaration" value="1" {{ $canSubmit ? '' : 'disabled' }}>
                <label class="form-check-label" for="declaration" style="font-size:14.5px;">
                    {{-- Declaration text is deliberately not run through get_phrase(): that
                         helper auto-inserts any string it's given into language.phrase, a
                         column capped at varchar(191) — this sentence is longer than that
                         and is legal/declaration wording we don't want to paraphrase-shorten
                         just to fit a translation-lookup table. --}}
                    I declare that the information given in this application is true and complete to the best of my knowledge. I understand that giving false information may lead to my application being rejected or my admission being cancelled.
                </label>
            </div>

            <div class="d-flex flex-wrap gap-2 justify-content-end">
                <a href="{{ route('applicant.application.step', 'personal') }}" class="ap-btn ap-btn-ghost">
                    <i class="bi bi-pencil"></i> {{ get_phrase('Keep Editing') }}
                </a>
                <button type="submit" class="ap-btn ap-btn-accent" {{ $canSubmit ? '' : 'disabled' }}>
                    <i class="bi bi-send"></i>
                    {{ $admission->status === \App\Models\Admission::STATUS_NEEDS_CORRECTION ? get_phrase('Resubmit Application') : get_phrase('Submit Application') }}
                </button>
            </div>

            @unless($canSubmit)
                <p class="ap-hint text-end mt-2 mb-0">{{ get_phrase('Resolve the items listed above to enable submission.') }}</p>
            @endunless
        </form>
    </div>
@endunless

@endsection
