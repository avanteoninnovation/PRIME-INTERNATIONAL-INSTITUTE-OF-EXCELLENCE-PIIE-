@extends('admin.navigation')

@section('content')
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Hostels') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Hostels') }}</a></li>
                        </ul>
                    </div>
                    <div class="export-btn-area">
                        <a href="javascript:;" class="export_btn" onclick="rightModal('{{ route('admin.hostel.create_hostel') }}', '{{ get_phrase('Create Hostel') }}')">{{ get_phrase('Add Hostel') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-7 offset-md-2">
            <div class="eSection-wrap">
                @if (count($hostels) > 0)
                    <!-- Table -->
                    <div class="table-responsive tScrollFix pb-2">
                        <table class="table eTable">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ get_phrase('Hostel Name') }}</th>
                                    <th scope="col">{{ get_phrase('Hostel Type') }}</th>
                                    <th scope="col">{{ get_phrase('Hostel Address') }}</th>
                                    <th scope="col" class="text-end">{{ get_phrase('Options') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($hostels as $key => $hostel)
                                    <tr>
                                        <td>
                                            {{ $key + 1 }}
                                        </td>
                                        <td>
                                            {{ $hostel['name'] }}
                                        </td>
                                        <td>
                                            {{ $hostel['type'] == 1 ? 'Boys' : 'Girls' }}
                                        </td>
                                        <td>
                                            {{ $hostel['address'] }}
                                        </td>
                                        <td class="text-center">
                                            <div class="adminTable-action">
                                                <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2" data-bs-toggle="dropdown" aria-expanded="false">
                                                    {{ get_phrase('Actions') }}
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:;" onclick="rightModal('{{ route('admin.hostel.edit_hostel', ['id' => $hostel->id]) }}', '{{ get_phrase('Edit Hostel') }}')">{{ get_phrase('Edit') }}</a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item" href="javascript:;" onclick="confirmModal('{{ route('admin.hostel.delete_hostel', ['id' => $hostel->id]) }}', 'undefined');">{{ get_phrase('Delete') }}</a>
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
                    <div class="hostels_content">
                        <div class="empty_box center">
                            <img class="mb-3" width="150px" src="{{ asset('assets/images/empty_box.png') }}" />
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- End Exam category area -->
@endsection
