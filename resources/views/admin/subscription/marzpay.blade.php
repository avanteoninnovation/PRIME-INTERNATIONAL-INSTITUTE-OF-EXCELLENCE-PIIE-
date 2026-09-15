@php
    $marzpay_amount = (!empty($subscription) && $subascribe_active == '1') ? $upgradePrice : $selected_package['price'];
@endphp

<!-- marzpay -->
<div class="tab-pane fade show active" id="v-pills-marzpay" role="tabpanel" aria-labelledby="v-pills-marzpay-tab" tabindex="0">
    <div class="off_payment_form">

        <form action="{{ route('admin.subscription.marzpay.start', ['id' => $selected_package['id']]) }}" method="post" class="marzpay-form form">
            @csrf

            <hr class="border mb-4">

            <div class="fpb-7">
                <label for="marzpay_phone_number" class="eForm-label">{{ get_phrase('Mobile Money Number') }}</label>
                <input type="tel" class="form-control eForm-control" id="marzpay_phone_number" name="phone_number" placeholder="e.g. 0712345678" value="{{ old('phone_number') }}" required>
                <small class="text-muted d-block mt-1">{{ get_phrase("You'll get a USSD prompt on this phone to approve the payment.") }}</small>
            </div>

            <button type="submit" class="off_payment_btn">
                {{ get_phrase('Pay with MarzPay') }}
            </button>
        </form>

    </div>
</div>
