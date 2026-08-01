@extends('applicant.layout')

@section('title', get_phrase('Application Fee'))
@section('subtitle', get_phrase('Settle the application fee for your chosen intake.'))

@section('content')

@include('applicant.application._stepper')

@include('applicant.partials._payment')

<div class="d-flex justify-content-end mt-4">
    <a href="{{ route('applicant.application.step', 'review') }}" class="ap-btn ap-btn-primary">
        {{ get_phrase('Continue to Review') }} <i class="bi bi-arrow-right"></i>
    </a>
</div>

@endsection
