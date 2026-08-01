@extends('applicant.layout')

@section('title', get_phrase('Documents'))
@section('subtitle', get_phrase('Everything you have uploaded, and anything still outstanding.'))

@section('content')

@include('applicant.partials._documents')

<div class="d-flex justify-content-end mt-4">
    <a href="{{ route('applicant.application') }}" class="ap-btn ap-btn-ghost">
        <i class="bi bi-arrow-left"></i> {{ get_phrase('Back to My Application') }}
    </a>
</div>

@endsection
