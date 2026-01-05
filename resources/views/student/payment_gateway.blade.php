<?php
use App\Models\PaymentMethods;

$active_payment_methods = PaymentMethods::where('school_id', auth()->user()->school_id)
    ->orWhere('mode', 'offline')
    ->get();
$number_of_activated_payment_gateway = PaymentMethods::where('status', 1)
    ->where('school_id', auth()->user()->school_id)
    ->orWhere('mode', 'offline')
    ->get();

$off = '';

if (count($number_of_activated_payment_gateway) == 1) {
    $off = ' show active';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>{{ get_phrase('Hostel Fee Payment | Ekator 8') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <!-- all the css files -->
    <link rel="shortcut icon" href="{{ asset('/assets/images/logo.png') }}">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('/assets/vendors/bootstrap-5.1.3/css/bootstrap.min.css') }}">

    <!--Custom css-->
    <link rel="stylesheet" type="text/css" href="{{ asset('/assets/css/swiper-bundle.min.css') }}">

    <link rel="stylesheet" type="text/css" href="{{ asset('/assets/css/custom.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('/assets/css/style.css') }}" />
    <!-- Datepicker css -->

    <link rel="stylesheet" href="{{ asset('/assets/css/daterangepicker.css') }}" />
    <!-- Select2 css -->
    <link rel="stylesheet" href="{{ asset('/assets/css/select2.min.css') }}" />

    <link rel="stylesheet" type="text/css" href="{{ asset('/assets/vendors/bootstrap-icons-1.8.1/bootstrap-icons.css') }}">

<body>

    <div class="main_content paymentContent">
        <div class="paymentHeader d-flex justify-content-between align-items-center">
            <h5 class="title">
                {{ get_phrase('Make Hostel Fee Payment') }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="location.href='{{ redirect()->back()->getTargetUrl() }}'"></button>
        </div>

        <div class="paymentWrap d-flex align-items-start flex-wrap">
            <div class="paymentLeft">
                <p class="payment_tab_title pb-30">
                    {{ get_phrase('Payment Gateway') }}
                </p>

                <!-- Only show offline payment -->
                <div class="nav flex-md-column flex-row nav-pills payment_modalTab" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                    <div class="tabItem show active" id="v-pills-offline-tab" data-bs-toggle="pill" data-bs-target="#v-pills-offline" role="tab" aria-controls="v-pills-offline" aria-selected="true">
                        <div class="payment_gateway_option d-flex align-items-center">
                            <div class="logo">
                                @php
                                    $image_logo = '/assets/images/offline.png';
                                @endphp
                                <img src="{{ asset($image_logo) }}" alt="" />
                            </div>
                            <div class="info">
                                <p class="card_no">{{ get_phrase('Offline') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="paymentRight">
                <!-- Your existing invoice summary code -->

                <div class="tab-content payment_modalTab_content" id="v-pills-tabContent">
                    <div class="tab-pane fade show active" id="v-pills-offline" role="tabpanel" aria-labelledby="v-pills-offline-tab" tabindex="0">
                        <div class="off_payment_form">
                            <form action="{{ route('student.offline.payment.hostel') }}" class="offline-form form" method="post" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="amount" value="{{ $fee_details['total_amount'] }}">
                                <input type="hidden" name="hostel_fee" value="1">

                                <div class="payable_document">
                                    <label for="payableDocuemnt" class="eForm-label">
                                        {{ get_phrase('Document of your payment') }} (jpg, pdf, txt, png, docx)
                                    </label>
                                    <input type="file" class="form-control eForm-control-file" id="document_image" name="document_image" required>
                                </div>

                                <button type="submit" class="off_payment_btn">
                                    {{ get_phrase('Submit payment document') }}
                                </button>

                                <div class="offline_payment_instruction">
                                    <p>
                                        {{ get_phrase('Instruction') . ': ' . get_phrase('Admin will review your payment document and then approve the Payment.') }}
                                    </p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('/assets/vendors/jquery/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('/assets/vendors/bootstrap-5.1.3/js/bootstrap.bundle.min.js') }}"></script>

    <script src="{{ asset('/assets/js/swiper-bundle.min.js') }}"></script>

    <script src="{{ asset('/assets/js/moment.min.js') }}"></script>
    <script src="{{ asset('/assets/js/daterangepicker.min.js') }}"></script>
    <!-- Select2 js -->
    <script src="{{ asset('/assets/js/select2.min.js') }}"></script>

    <!--Custom Script-->
    <script src="{{ asset('/assets/js/script.js') }}"></script>
</body>

</html>
