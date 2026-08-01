@extends('applicant.layout')

@section('title', get_phrase('Application Fee'))
@section('subtitle', get_phrase('Pay your application fee or record a payment you have already made.'))

@section('content')

@include('applicant.partials._payment')

<div class="d-flex justify-content-end mt-4">
    <a href="{{ route('applicant.application') }}" class="ap-btn ap-btn-ghost">
        <i class="bi bi-arrow-left"></i> {{ get_phrase('Back to My Application') }}
    </a>
</div>

@endsection
