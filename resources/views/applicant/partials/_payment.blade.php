{{--
    Application fee: what is owed, how to pay it, and what has been submitted
    so far. Shared by the wizard's Payment step and the standalone Fee page.
    Expects: $admission, $amount, $methods, $bankDetails, $payments.
--}}

@php
    use App\Support\Admissions\ApplicationFee;

    $settled  = $admission->isFeeSettled();
    $pending  = $admission->fee_status === \App\Models\Admission::FEE_PENDING;
    $gateways = collect($methods)->where('key', '!=', 'offline');
@endphp

<div class="ap-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="ap-icon-tile {{ $settled ? 'ap-tile-green' : 'ap-tile-amber' }}">
                <i class="bi {{ $settled ? 'bi-check2-circle' : 'bi-credit-card' }}"></i>
            </div>
            <div>
                <h2 class="ap-card-title mb-1">{{ get_phrase('Application Fee') }}</h2>
                <div style="color:var(--ap-muted); font-size:14px;">
                    {{ optional($admission->intakeSession)->name ?: get_phrase('Selected intake') }}
                </div>
            </div>
        </div>

        <div class="text-end">
            <div style="font-size:24px; font-weight:700;">{{ ApplicationFee::format((float) $amount) }}</div>
            <span class="ap-pill bg-{{ $settled ? 'success' : ($pending ? 'primary' : 'warning') }} bg-opacity-10 text-{{ $settled ? 'success' : ($pending ? 'primary' : 'warning') }}">
                {{ get_phrase(ucfirst($admission->fee_status)) }}
            </span>
        </div>
    </div>
</div>

@if($settled)
    <div class="ap-card">
        <div class="d-flex align-items-start gap-3">
            <i class="bi bi-check-circle-fill" style="color:var(--ap-accent); font-size:24px;"></i>
            <div>
                <strong>{{ get_phrase('Your application fee is settled.') }}</strong>
                <p class="mb-0 ap-hint">{{ get_phrase('Nothing further is needed on this step.') }}</p>
            </div>
        </div>
    </div>
@elseif($pending)
    <div class="alert alert-info d-flex align-items-start gap-2">
        <i class="bi bi-hourglass-split mt-1"></i>
        <div>
            <strong>{{ get_phrase('Your payment is being verified.') }}</strong><br>
            {{ get_phrase('The finance office is confirming the details you submitted. You can continue with the rest of your application in the meantime.') }}
        </div>
    </div>
@endif

@unless($settled)
    <div class="row g-4 mt-1">
        {{-- Online payment --}}
        @if($gateways->isNotEmpty())
            <div class="col-lg-5">
                <div class="ap-card h-100">
                    <div class="ap-card-head">
                        <h2 class="ap-card-title"><i class="bi bi-lightning-charge"></i> {{ get_phrase('Pay Online') }}</h2>
                    </div>

                    <p class="ap-hint mb-3">{{ get_phrase('Pay now and this step is completed immediately.') }}</p>

                    @foreach($gateways as $gateway)
                        <form action="{{ route('applicant.payment.gateway.start', $gateway['key']) }}" method="POST" class="mb-2">
                            @csrf
                            <button type="submit" class="ap-btn ap-btn-accent w-100">
                                <i class="bi {{ $gateway['icon'] }}"></i> {{ $gateway['label'] }}
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Bank deposit --}}
        <div class="{{ $gateways->isNotEmpty() ? 'col-lg-7' : 'col-12' }}">
            <div class="ap-card h-100">
                <div class="ap-card-head">
                    <h2 class="ap-card-title"><i class="bi bi-bank"></i> {{ get_phrase('Bank Deposit / Mobile Money') }}</h2>
                </div>

                @if($bankDetails)
                    <div class="p-3 mb-3" style="background:#f9fafb; border:1px solid var(--ap-line); border-radius:9px; white-space:pre-line; font-size:14px;">{{ $bankDetails }}</div>
                @else
                    <div class="alert alert-warning py-2 px-3" style="font-size:14px;">
                        {{ get_phrase('Contact the admissions office for the institution bank details, then record your payment below.') }}
                    </div>
                @endif

                <form action="{{ route('applicant.payment.offline') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ get_phrase('Transaction / Deposit Reference') }} <span class="req">*</span></label>
                            <input type="text" name="reference" class="form-control" value="{{ old('reference') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ get_phrase('Proof of Payment') }} <span class="req">*</span></label>
                            <input type="file" name="proof" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="ap-hint">{{ get_phrase('A photo of the deposit slip or a screenshot of the transaction message.') }}</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ get_phrase('Note') }}</label>
                            <input type="text" name="note" class="form-control" value="{{ old('note') }}"
                                   placeholder="{{ get_phrase('Optional — anything the finance office should know.') }}">
                        </div>
                    </div>

                    <button type="submit" class="ap-btn ap-btn-primary mt-3">
                        <i class="bi bi-send"></i> {{ get_phrase('Submit Payment Details') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endunless

@if($payments->isNotEmpty())
    <div class="ap-card mt-4">
        <div class="ap-card-head">
            <h2 class="ap-card-title"><i class="bi bi-receipt"></i> {{ get_phrase('Payment History') }}</h2>
        </div>

        <div class="table-responsive">
            <table class="table align-middle mb-0" style="font-size:14px;">
                <thead>
                    <tr style="color:var(--ap-muted); font-size:12.5px; text-transform:uppercase;">
                        <th>{{ get_phrase('Date') }}</th>
                        <th>{{ get_phrase('Method') }}</th>
                        <th>{{ get_phrase('Reference') }}</th>
                        <th>{{ get_phrase('Amount') }}</th>
                        <th>{{ get_phrase('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $payment)
                        <tr>
                            <td>{{ $payment->created_at->format('d M Y') }}</td>
                            <td>{{ ucfirst($payment->method) }}</td>
                            <td style="word-break:break-all;">{{ $payment->reference ?: '—' }}</td>
                            <td>{{ ApplicationFee::format((float) $payment->amount) }}</td>
                            <td>
                                @php
                                    $tone = ['paid' => 'success', 'waived' => 'success', 'pending' => 'primary', 'failed' => 'danger', 'rejected' => 'danger'][$payment->status] ?? 'secondary';
                                @endphp
                                <span class="ap-pill bg-{{ $tone }} bg-opacity-10 text-{{ $tone }}">{{ get_phrase(ucfirst($payment->status)) }}</span>
                                @if($payment->status === 'rejected' && $payment->note)
                                    <div class="ap-hint text-danger">{{ $payment->note }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
