@extends('student.navigation')

@section('content')
    <div class="main-section-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card mt-4">
                        <div class="card-header bg-transparent border-bottom">
                            <h4 class="card-title">{{ get_phrase('Hostel Fee') }}</h4>
                        </div>
                        <div class="card-body">
                            @if (session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-danger">{{ session('error') }}</div>
                            @endif

                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">{{ get_phrase('Hostel Information') }}</h6>
                                            <p class="mb-1"><strong>{{ get_phrase('Hostel') }}:</strong> {{ $allocation->hostel->name ?? 'N/A' }}</p>
                                            <p class="mb-1"><strong>{{ get_phrase('Room') }}:</strong> {{ $allocation->room->room_number ?? 'N/A' }}</p>
                                            <p class="mb-0"><strong>{{ get_phrase('Monthly Fee') }}:</strong> {{ currency($allocation->hostel->fee ?? 0) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>{{ get_phrase('Month') }}</th>
                                            <th>{{ get_phrase('Year') }}</th>
                                            <th>{{ get_phrase('Status') }}</th>
                                            <th>{{ get_phrase('Payment Date') }}</th>
                                            <th>{{ get_phrase('Amount') }}</th>
                                            <th>{{ get_phrase('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($monthlyFees as $fee)
                                            <tr>
                                                <td>{{ $fee['month'] }}</td>
                                                <td>{{ $fee['year'] }}</td>
                                                <td>
                                                    @if ($fee['status'] == 'Paid')
                                                        <span class="badge bg-success">{{ get_phrase('Paid') }}</span>
                                                    @else
                                                        <span class="badge bg-danger">{{ get_phrase('Unpaid') }}</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($fee['fee_details'] && $fee['fee_details']['payment_date'])
                                                        {{ date('d-M-Y', strtotime($fee['fee_details']['payment_date'])) }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>{{ currency($allocation->hostel->fee ?? 0) }}</td>
                                                <td>
                                                    @if ($fee['status'] == 'Unpaid' && $fee['is_current_or_past'])
                                                        <a href="{{ route('student.hostel_fee.pay_monthly', ['month' => $fee['month_number'], 'year' => $fee['year']]) }}" class="btn btn-primary btn-sm">
                                                            Pay Now
                                                        </a>
                                                    @elseif($fee['status'] == 'Paid')
                                                        <a href="{{ route('student.hostel_fee.invoice', $fee['fee_details']['id']) }}" class="btn btn-info btn-sm">
                                                            View Invoice
                                                        </a>
                                                    @else
                                                        <span class="text-muted">{{ get_phrase('Not Available') }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
