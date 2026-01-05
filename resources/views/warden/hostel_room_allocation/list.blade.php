@extends('warden.navigation')

@section('content')
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Hostel Room Allocations') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Hostel Room Allocations') }}</a></li>
                        </ul>
                    </div>
                    <div class="export-btn-area">
                        <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('warden.hostel.create_allocation') }}', '{{ get_phrase('Create Hostel Room Allocation') }}')">{{ get_phrase('Add Hostel Room Allocation') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-7 offset-md-2">
            <div class="eSection-wrap">
                @if (count($hostel_room_allocations) > 0)
                    <!-- Table -->
                    <div class="table-responsive tScrollFix pb-2">
                        <table class="table eTable">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ get_phrase('Student') }}</th>
                                    <th scope="col">{{ get_phrase('Hostel') }}</th>
                                    <th scope="col">{{ get_phrase('Room') }}</th>
                                    <th scope="col" class="text-end">{{ get_phrase('Options') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hostel_room_allocations as $key => $allocation)
                                    <tr>
                                        <td>
                                            {{ $key + 1 }}
                                        </td>
                                        <td>
                                            {{ $allocation->student->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            {{ $allocation->room->hostel->name ?? 'N/A' }}
                                        </td>
                                        <td>
                                            {{ $allocation->room->room_no ?? 'N/A' }}
                                        </td>
                                        <td class="text-center">
                                            <div class="wardenTable-action">
                                                <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ get_phrase('Actions') }}
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:;" onclick="rightModal('{{ route('warden.hostel.edit_allocation', ['id' => $allocation->id]) }}', '{{ get_phrase('Edit Hostel Room Allocation') }}')">{{ get_phrase('Edit') }}</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('warden.hostel.delete_allocation', ['id' => $allocation->id]) }}', 'undefined');">{{ get_phrase('Delete') }}</a>
                                                    </li>
                                                </ul>
                                            </div>
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
