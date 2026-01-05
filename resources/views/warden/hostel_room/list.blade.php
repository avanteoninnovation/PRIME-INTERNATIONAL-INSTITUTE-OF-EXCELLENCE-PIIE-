@extends('warden.navigation')

@section('content')
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Hostel Rooms') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Hostel Rooms') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-7 offset-md-2">
            <div class="eSection-wrap">
                @if (count($hostel_rooms) > 0)
                    <!-- Table -->
                    <div class="table-responsive tScrollFix pb-2">
                        <table class="table eTable">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ get_phrase('Hostel Name') }}</th>
                                    <th scope="col">{{ get_phrase('Room Number') }}</th>
                                    <th scope="col">{{ get_phrase('Room Capacity') }}</th>
                                    <th scope="col">{{ get_phrase('Remaining Capacity') }}</th>
                                    <th scope="col">{{ get_phrase('Seat Fee') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hostel_rooms as $key => $room)
                                    <tr>
                                        <td>
                                            {{ $key + 1 }}
                                        </td>
                                        <td>
                                            {{ $room->hostel->name }}
                                        </td>
                                        <td>
                                            {{ $room->room_no }}
                                        </td>
                                        <td>
                                            {{ $room->capacity }}
                                        </td>
                                        <td>{{ $room->capacity - $room->occupied }} {{ get_phrase('available') }}</td>
                                        <td>
                                            {{ $room->seat_fee }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="hostel_rooms_content">
                        <div class="empty_box center">
                            <img class="mb-3" width="150px" src="{{ asset('assets/images/empty_box.png') }}" />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- End  area -->
@endsection
