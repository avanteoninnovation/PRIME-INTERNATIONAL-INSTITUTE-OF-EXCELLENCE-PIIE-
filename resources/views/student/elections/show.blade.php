@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex flex-column">
        <h4>{{ $election->title }}</h4>
        <ul class="d-flex align-items-center eBreadcrumb-2">
            <li><a href="{{ route('student.dashboard') }}">{{ get_phrase('Home') }}</a></li>
            <li><a href="{{ route('student.elections.index') }}">{{ get_phrase('Elections') }}</a></li>
            <li><a href="#">{{ $election->title }}</a></li>
        </ul>
    </div>
</div></div></div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="row">
    @if(!$verified)
        <div class="col-12 col-md-6">
            <div class="eSection-wrap">
                <h5 class="mb-3">{{ get_phrase('Verify Your Identity') }}</h5>
                <p class="text-muted">{{ get_phrase('Enter your registration number to confirm your identity before voting.') }}</p>
                <form method="POST" action="{{ route('student.elections.verify', $election->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="eForm-label">{{ get_phrase('Registration Number') }}</label>
                        <input type="text" name="registration_number" class="form-control eForm-control" required autofocus>
                    </div>
                    <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Verify') }}</button>
                </form>
            </div>
        </div>
    @else
        @foreach($election->positions as $position)
            <div class="col-12 mb-3">
                <div class="eSection-wrap">
                    <h5 class="mb-3">{{ $position->title }}</h5>

                    @if($votedPositionIds->contains($position->id))
                        <p class="text-success mb-0"><i class="fa fa-check-circle"></i> {{ get_phrase('You have already voted for this position.') }}</p>
                    @else
                        <form method="POST" action="{{ route('student.elections.vote', $election->id) }}">
                            @csrf
                            <input type="hidden" name="position_id" value="{{ $position->id }}">
                            @forelse($position->candidates as $candidate)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="candidate_id" value="{{ $candidate->id }}" id="candidate-{{ $candidate->id }}" required>
                                    <label class="form-check-label" for="candidate-{{ $candidate->id }}">
                                        <strong>{{ $candidate->student?->name }}</strong>
                                        @if($candidate->manifesto)
                                            <br><small class="text-muted">{{ $candidate->manifesto }}</small>
                                        @endif
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted">{{ get_phrase('No candidates for this position yet.') }}</p>
                            @endforelse
                            @if($position->candidates->isNotEmpty())
                                <button type="submit" class="eBtn eBtn-primary mt-2">{{ get_phrase('Cast Vote') }}</button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
@endsection
