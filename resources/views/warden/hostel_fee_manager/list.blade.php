@extends('warden.navigation')

@section('content')
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Hostel Fees') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Hostel') }}</a></li>
                            <li><a href="#">{{ get_phrase('Fees') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="eSection-wrap">
                <div class="search-filter-area d-flex justify-content-md-between justify-content-center align-items-center flex-wrap gr-15">
                    <form action="{{ route('warden.hostel_fee_manager.list') }}">
                        <div class="search-input d-flex justify-content-start align-items-center">
                            <span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16">
                                    <path id="Search_icon" data-name="Search icon"
                                        d="M2,7A4.951,4.951,0,0,1,7,2a4.951,4.951,0,0,1,5,5,4.951,4.951,0,0,1-5,5A4.951,4.951,0,0,1,2,7Zm12.3,8.7a.99.99,0,0,0,1.4-1.4l-3.1-3.1A6.847,6.847,0,0,0,14,7,6.957,6.957,0,0,0,7,0,6.957,6.957,0,0,0,0,7a6.957,6.957,0,0,0,7,7,6.847,6.847,0,0,0,4.2-1.4Z"
                                        fill="#797c8b" />
                                </svg>
                            </span>
                            <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Search Book" class="form-control" />
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table eTable">
                        <thead>
                            <tr>
                                <th>{{ get_phrase('Student') }}</th>
                                <th>{{ get_phrase('Class') }}</th>
                                <th>{{ get_phrase('Room No') }}</th>
                                @for ($i = 1; $i <= 12; $i++)
                                    @php
                                        $date = Carbon\Carbon::createFromDate(now()->year, $i, 1);
                                    @endphp
                                    <th class="text-center">{{ $date->format('M') }} {{ now()->year }}</th>
                                @endfor
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($students as $student)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="{{ get_user_image($student->id) }}" alt="" style="width: 40px; height: 40px; border-radius: 50%;">
                                            <div class="ms-3">
                                                <strong>{{ $student->name }}</strong><br>
                                                <small>{{ $student->email }}</small>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        @php
                                            $controller = new \App\Http\Controllers\CommonController();
                                            $student_details = $controller->get_student_details_by_id($student->id);
                                        @endphp
                                        {{ $student_details['class_name'] ?? 'N/A' }}<br>
                                        <small>{{ $student_details['section_name'] ?? '' }}</small>
                                    </td>

                                    @php
                                        $application = App\Models\HostelApplication::where('status', 1)
                                            ->where('student_id', $student->id)
                                            ->where('school_id', auth()->user()->school_id)
                                            ->first();
                                        $room = $application ? App\Models\HostelRoom::find($application->room_id) : null;
                                        $hostel = $application ? App\Models\Hostel::find($application->hostel_id) : null;
                                    @endphp
                                    <td>
                                        @if ($room && $hostel)
                                            {{ $room->room_no }}<br>
                                            <small>{{ $hostel->name }}</small>
                                        @else
                                            -
                                        @endif
                                    </td>


                                    @for ($i = 1; $i <= 12; $i++)
                                        @if ($i > now()->month)
                                            <td>-</td>
                                            @continue
                                        @endif

                                        @php
                                            $date = Carbon\Carbon::createFromDate(now()->year, $i, 1);
                                            $startOfMonth = $date->copy()->startOfMonth()->startOfDay();
                                            $endOfMonth = $date->copy()->endOfMonth()->endOfDay();
                                            $feePaid = \App\Models\HostelFee::where('school_id', auth()->user()->school_id)
                                                ->where('student_id', $student->id)
                                                ->whereBetween('fee_payment_date', [$startOfMonth, $endOfMonth])
                                                ->latest()
                                                ->first();
                                            $admissionDate = Carbon\Carbon::parse($application->accepted_at);
                                            $currentMonthDate = Carbon\Carbon::createFromDate(now()->year, $i, 1);
                                            $isBeforeAdmission = $currentMonthDate->lt($admissionDate->startOfMonth());
                                        @endphp

                                        <td class="text-center">
                                            @if ($i >= $application->accepted_at->format('m'))
                                                @if ($feePaid)
                                                    @if ($feePaid->status == 1)
                                                        <span class="eBadge ebg-soft-success">{{ get_phrase('Paid') }}</span>
                                                        <p class="lh-1"><small>{{ $feePaid->created_at->format('d-M-y') }}</small></p>
                                                    @elseif ($feePaid->status == 2)
                                                        <span class="eBadge ebg-soft-danger">{{ get_phrase('Rejected') }}</span>
                                                        <p class="lh-1"><small>{{ $feePaid->created_at->format('d-M-y') }}</small></p>
                                                    @else
                                                        <span class="eBadge ebg-soft-warning">{{ get_phrase('Pending') }}</span>
                                                    @endif
                                                @else
                                                    <span class="eBadge ebg-soft-danger mb-2">{{ get_phrase('Unpaid') }}</span>
                                                @endif

                                                @if ($date->copy()->format('y') == $application->accepted_at->format('y') && $i == $application->accepted_at->format('m'))
                                                    <p class="lh-1"><small>{{ get_phrase('Admission') }} - {{ $application->accepted_at->format('d-M-y') }}</small></p>
                                                @endif
                                            @else
                                                {{-- For months before admission - show "Not Applicable" or similar --}}
                                                <span class="eBadge ebg-soft-dark">{{ get_phrase('N/A') }}</span>
                                                @if ($date->copy()->format('y') == $application->accepted_at->format('y') && $i == $application->accepted_at->format('m'))
                                                    <p class="lh-1"><small>{{ get_phrase('Admission') }} - {{ $application->accepted_at->format('d-M-y') }}</small></p>
                                                @endif
                                            @endif
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $students->links() }}
                </div>
            </div>
        </div>
    </div>

    <style>
        .table th {
            white-space: nowrap;
        }

        .eBadge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        });
    </script>
@endsection
