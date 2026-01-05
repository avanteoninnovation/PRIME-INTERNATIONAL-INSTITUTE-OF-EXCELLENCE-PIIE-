@extends('warden.navigation')

@section('content')
    <div class="mainSection-title">
        <h4>{{ get_phrase('Pending Offline Payments') }}</h4>
        <ul class="d-flex align-items-center eBreadcrumb-2">
            <li><a href="#">{{ get_phrase('Home') }}</a></li>
            <li><a href="#">{{ get_phrase('Hostel') }}</a></li>
            <li><a href="#">{{ get_phrase('Offline Payments') }}</a></li>
        </ul>
    </div>

    <div class="eSection-wrap">
        <div class="table-responsive">
            <table class="table eTable">
                <thead>
                    <tr>
                        <th>{{ get_phrase('Student') }}</th>
                        <th>{{ get_phrase('Month') }}</th>
                        <th>{{ get_phrase('Amount') }}</th>
                        <th>{{ get_phrase('Status') }}</th>
                        <th>{{ get_phrase('Payment Document') }}</th>
                        <th>{{ get_phrase('Action') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($pendingPayments as $fee)
                        @php
                            $student = \App\Models\User::find($fee->student_id);
                        @endphp
                        <tr>
                            <td>
                                {{ $student->name ?? 'N/A' }}<br>
                                <small>{{ $student->email ?? '' }}</small>
                            </td>

                            <td>{{ Carbon\Carbon::parse($fee->fee_payment_date)->format('M Y') }}</td>

                            <td>{{ $fee->amount ?? '-' }}</td>

                            <td>
                                <span class="eBadge ebg-soft-warning">
                                    {{ get_phrase('Pending') }}
                                </span>
                            </td>
                            <td class="link">
                                @if (!empty($fee->document_image))
                                    @php
                                        $fileExtension = pathinfo($fee->document_image, PATHINFO_EXTENSION);
                                        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                                    @endphp

                                    @if (in_array(strtolower($fileExtension), $allowedExtensions))
                                        <a href="{{ asset('assets/uploads/hostel_fees/' . $fee->document_image) }}" download data-lightbox="models" data-title="{{ $fee->document_image }}"> <strong>{{ $fee->document_image }} </strong>
                                        @else
                                            <a href="{{ asset('assets/uploads/hostel_fees/' . $fee->document_image) }}" download data-title="{{ $fee->document_image }}"> <strong>{{ $fee->document_image }} </strong>
                                    @endif
                                @endif

                                </a>
                            </td>
                            <td class="text-start">
                                <div class="adminTable-action">
                                    <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2" data-bs-toggle="dropdown" aria-expanded="false">
                                        {{ get_phrase('Actions') }}
                                    </button>

                                    <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">

                                        <li>
                                            <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('warden.accept.offline.payment.hostel', $fee->id) }}', 'undefined');">
                                                {{ get_phrase('Accept') }}
                                            </a>
                                        </li>

                                        <li>
                                            <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('warden.reject.offline.payment.hostel', $fee->id) }}', 'undefined');">
                                                {{ get_phrase('Reject') }}
                                            </a>
                                        </li>



                                    </ul>
                                </div>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $pendingPayments->links() }}
        </div>
    </div>
@endsection
