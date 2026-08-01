<?php

namespace App\Http\Controllers\Applicant;

use App\Models\ApplicationPayment;
use App\Models\AuditLog;
use App\Support\Admissions\ApplicantNotifier;
use App\Support\Admissions\ApplicationFee;
use App\Support\Admissions\ApplicationProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The application fee: bank deposit with proof, or an online card payment.
 *
 * Nothing here ever marks a fee paid on the applicant's say-so. A deposit is
 * recorded as 'pending' for the finance office to confirm; a card payment is
 * only settled after Stripe is asked, server-side, whether the session
 * actually completed. The redirect back from a gateway is a hint that
 * something happened, not evidence of it.
 */
class PaymentController extends BaseApplicantController
{
    public function index()
    {
        $admission = $this->currentApplication();
        $admission->load(['intakeSession', 'payments']);

        if (! ApplicationFee::isRequired($admission)) {
            return redirect()->route('applicant.dashboard')
                ->with('success', get_phrase('No application fee is payable for your chosen intake.'));
        }

        return view('applicant.payment', [
            'admission'   => $admission,
            'amount'      => ApplicationFee::amountFor($admission),
            'methods'     => ApplicationFee::availableMethods($admission->school_id),
            'bankDetails' => ApplicationFee::bankInstructions(),
            'payments'    => $admission->payments()->latest('id')->get(),
            'percent'     => ApplicationProgress::percent($admission),
        ]);
    }

    /**
     * Bank deposit / mobile money: the applicant tells us what they paid and
     * uploads proof; finance confirms it later from the admin review screen.
     */
    public function submitOffline(Request $request)
    {
        $admission = $this->currentApplication();
        $amount    = ApplicationFee::amountFor($admission);

        if ($amount <= 0) {
            return redirect()->route('applicant.dashboard');
        }

        if ($admission->isFeeSettled()) {
            return back()->with('error', get_phrase('Your application fee has already been settled.'));
        }

        $validated = $request->validate([
            'reference' => 'required|string|max:100',
            'note'      => 'nullable|string|max:500',
            'proof'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ], [
            'reference.required' => get_phrase('Please enter the transaction or deposit reference.'),
            'proof.required'     => get_phrase('Please attach your deposit slip or transaction message.'),
        ]);

        $destination = public_path(ApplicationPayment::PROOF_DIR);

        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $file     = $request->file('proof');
        $storedAs = 'pay' . $admission->id . '_' . uniqid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move($destination, $storedAs);

        $payment = ApplicationPayment::create([
            'school_id'    => $admission->school_id,
            'admission_id' => $admission->id,
            'applicant_id' => $this->applicant()->id,
            'amount'       => $amount,
            'currency'     => ApplicationFee::currency(),
            'method'       => 'offline',
            'status'       => ApplicationPayment::STATUS_PENDING,
            'reference'    => $validated['reference'],
            'note'         => $validated['note'] ?? null,
            'proof_file'   => $storedAs,
        ]);

        ApplicationFee::refreshStatus($admission);

        AuditLog::record('create', 'Admissions', "Applicant submitted offline application-fee payment for {$admission->app_number} (ref {$payment->reference}).", [
            'event_type'  => 'DATA',
            'record_type' => ApplicationPayment::class,
            'record_id'   => $payment->id,
            'school_id'   => $admission->school_id,
        ]);

        ApplicantNotifier::paymentReceived($admission, $payment);

        return redirect()->route('applicant.payment')
            ->with('success', get_phrase('Thank you. Your payment details have been sent to the finance office for confirmation.'));
    }

    /**
     * Starts a Stripe Checkout session and redirects to it.
     *
     * A 'pending' payment row is written first, keyed by our own reference,
     * so a payment that completes at the gateway but never makes it back
     * through the success URL (closed tab, dropped connection) is still
     * traceable from this side.
     */
    public function startGateway(Request $request, string $gateway)
    {
        $admission = $this->currentApplication();
        $amount    = ApplicationFee::amountFor($admission);

        if ($amount <= 0 || $admission->isFeeSettled()) {
            return redirect()->route('applicant.dashboard');
        }

        if ($gateway !== 'stripe' || ! ApplicationFee::gatewayIsConfigured('stripe', $admission->school_id)) {
            return back()->with('error', get_phrase('That payment method is not available right now. Please use bank deposit.'));
        }

        $secretKey = get_payment_keys('stripe', 'test_secret_key') ?: get_payment_keys('stripe', 'secret_live_key');

        if (blank($secretKey)) {
            return back()->with('error', get_phrase('Card payments are not fully configured. Please use bank deposit.'));
        }

        $reference = 'APPFEE-' . $admission->id . '-' . strtoupper(Str::random(8));

        $payment = ApplicationPayment::create([
            'school_id'    => $admission->school_id,
            'admission_id' => $admission->id,
            'applicant_id' => $this->applicant()->id,
            'amount'       => $amount,
            'currency'     => ApplicationFee::currency(),
            'method'       => 'stripe',
            'status'       => ApplicationPayment::STATUS_PENDING,
            'reference'    => $reference,
        ]);

        try {
            \Stripe\Stripe::setApiKey($secretKey);

            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'mode'                 => 'payment',
                'client_reference_id'  => $reference,
                'customer_email'       => $admission->email,
                'line_items' => [[
                    'quantity'   => 1,
                    'price_data' => [
                        'currency'     => strtolower(ApplicationFee::currency()),
                        'unit_amount'  => (int) round($amount * 100),
                        'product_data' => [
                            'name'        => get_phrase('Application Fee') . ' — ' . $admission->app_number,
                            'description' => optional($admission->programme)->name,
                        ],
                    ],
                ]],
                'success_url' => route('applicant.payment.gateway.return', ['gateway' => 'stripe', 'payment' => $payment->id]) . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('applicant.payment.gateway.cancel', ['gateway' => 'stripe', 'payment' => $payment->id]),
            ]);
        } catch (\Throwable $e) {
            report($e);

            $payment->update(['status' => ApplicationPayment::STATUS_FAILED, 'note' => 'Gateway session could not be created.']);

            return back()->with('error', get_phrase('We could not start the card payment. Please try again or pay by bank deposit.'));
        }

        $payment->update(['gateway_txn_id' => $session->id]);

        ApplicationFee::refreshStatus($admission);

        return redirect()->away($session->url);
    }

    /**
     * Gateway return. The session is re-fetched from Stripe and only a
     * payment_status of 'paid' settles the fee — the applicant landing on
     * this URL proves nothing on its own.
     */
    public function gatewayReturn(Request $request, string $gateway, int $paymentId)
    {
        $admission = $this->currentApplication();

        $payment = ApplicationPayment::where('admission_id', $admission->id)->find($paymentId);

        if (! $payment || $gateway !== 'stripe') {
            return redirect()->route('applicant.payment')->with('error', get_phrase('We could not match that payment.'));
        }

        if ($payment->isSettled()) {
            return redirect()->route('applicant.payment')->with('success', get_phrase('Your application fee is already settled.'));
        }

        $secretKey = get_payment_keys('stripe', 'test_secret_key') ?: get_payment_keys('stripe', 'secret_live_key');

        try {
            \Stripe\Stripe::setApiKey($secretKey);
            $session = \Stripe\Checkout\Session::retrieve($request->query('session_id') ?: $payment->gateway_txn_id);
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('applicant.payment')
                ->with('error', get_phrase('We could not confirm your payment with the provider. If you were charged, contact the finance office with your reference: ') . $payment->reference);
        }

        if (($session->payment_status ?? null) !== 'paid') {
            $payment->update([
                'status'          => ApplicationPayment::STATUS_FAILED,
                'gateway_payload' => ['payment_status' => $session->payment_status ?? null],
            ]);

            ApplicationFee::refreshStatus($admission);

            return redirect()->route('applicant.payment')
                ->with('error', get_phrase('Your payment was not completed. You can try again or pay by bank deposit.'));
        }

        $payment->update([
            'status'          => ApplicationPayment::STATUS_PAID,
            'gateway_txn_id'  => $session->id,
            'paid_at'         => now(),
            'gateway_payload' => ['payment_intent' => $session->payment_intent ?? null, 'payment_status' => $session->payment_status],
        ]);

        ApplicationFee::refreshStatus($admission);

        AuditLog::record('create', 'Admissions', "Application fee paid online for {$admission->app_number} (ref {$payment->reference}).", [
            'event_type'  => 'DATA',
            'record_type' => ApplicationPayment::class,
            'record_id'   => $payment->id,
            'school_id'   => $admission->school_id,
        ]);

        ApplicantNotifier::paymentReceived($admission, $payment);

        return redirect()->route('applicant.application.step', ApplicationProgress::STEP_REVIEW)
            ->with('success', get_phrase('Payment received. Your application fee is settled.'));
    }

    public function gatewayCancel(string $gateway, int $paymentId)
    {
        $admission = $this->currentApplication();

        $payment = ApplicationPayment::where('admission_id', $admission->id)->find($paymentId);

        if ($payment && $payment->status === ApplicationPayment::STATUS_PENDING) {
            $payment->update(['status' => ApplicationPayment::STATUS_FAILED, 'note' => get_phrase('Cancelled at the payment page.')]);
            ApplicationFee::refreshStatus($admission);
        }

        return redirect()->route('applicant.payment')
            ->with('error', get_phrase('Your payment was cancelled. Nothing has been charged.'));
    }
}
