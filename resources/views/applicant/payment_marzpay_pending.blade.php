@extends('applicant.layout')

@section('title', get_phrase('Application Fee'))
@section('subtitle', get_phrase('Approve the MarzPay prompt on your phone to complete payment.'))

@section('content')

<div class="ap-card text-center py-5">
    <div id="mp-waiting">
        <div class="spinner-border text-primary mb-3" role="status" style="width:3rem;height:3rem;"></div>
        <h5>{{ get_phrase('Check your phone') }}</h5>
        <p class="ap-hint">{{ get_phrase('Approve the MarzPay prompt to complete your application fee payment.') }}</p>
        <p class="ap-hint">{{ get_phrase('This page will update automatically once payment is confirmed.') }}</p>
    </div>

    <div id="mp-paid" class="d-none">
        <i class="bi bi-check-circle-fill" style="color:var(--ap-accent); font-size:3rem;"></i>
        <h5 class="mt-3">{{ get_phrase('Payment received') }}</h5>
        <a href="{{ route('applicant.application') }}" class="ap-btn ap-btn-primary mt-3">{{ get_phrase('Continue Application') }}</a>
    </div>

    <div id="mp-failed" class="d-none">
        <i class="bi bi-x-circle-fill text-danger" style="font-size:3rem;"></i>
        <h5 class="mt-3">{{ get_phrase('Payment not completed') }}</h5>
        <a href="{{ route('applicant.payment') }}" class="ap-btn ap-btn-primary mt-3">{{ get_phrase('Try again') }}</a>
    </div>
</div>

<script>
(function () {
    var statusUrl = @json(route('applicant.payment.marzpay.status', $payment->id));
    var attempts = 0;
    var maxAttempts = 60;

    function poll() {
        attempts++;
        fetch(statusUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.status === 'paid') {
                    document.getElementById('mp-waiting').classList.add('d-none');
                    document.getElementById('mp-paid').classList.remove('d-none');
                } else if (data.status === 'failed') {
                    document.getElementById('mp-waiting').classList.add('d-none');
                    document.getElementById('mp-failed').classList.remove('d-none');
                } else if (attempts < maxAttempts) {
                    setTimeout(poll, 4000);
                }
            })
            .catch(function () {
                if (attempts < maxAttempts) {
                    setTimeout(poll, 4000);
                }
            });
    }

    setTimeout(poll, 4000);
})();
</script>

@endsection
