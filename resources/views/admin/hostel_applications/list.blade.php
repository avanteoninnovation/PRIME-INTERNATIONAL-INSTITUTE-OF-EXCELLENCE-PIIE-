@extends('admin.navigation')

@section('content')
    <div class="mainSection-title">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
                    <div class="d-flex flex-column">
                        <h4>{{ get_phrase('Hostel Applications') }}</h4>
                        <ul class="d-flex align-items-center eBreadcrumb-2">
                            <li><a href="#">{{ get_phrase('Home') }}</a></li>
                            <li><a href="#">{{ get_phrase('Hostel Applications') }}</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="eSection-wrap">
                @if (count($applications) > 0)
                    <div class="table-responsive">
                        <table class="table eTable">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ get_phrase('Student') }}</th>
                                    <th scope="col">{{ get_phrase('Hostel') }}</th>
                                    <th scope="col">{{ get_phrase('Room') }}</th>
                                    <th scope="col">{{ get_phrase('Applied Date') }}</th>
                                    <th scope="col">{{ get_phrase('Status') }}</th>
                                    <th scope="col">{{ get_phrase('Note') }}</th>
                                    <th scope="col" class="text-end">{{ get_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($applications as $key => $application)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $application->student->name ?? 'N/A' }}</td>
                                        <td>{{ $application->hostel->name ?? 'N/A' }}</td>
                                        <td>{{ $application->room->room_no ?? 'N/A' }}</td>
                                        <td>{{ date('d M Y', strtotime($application->created_at)) }}</td>
                                        <td>
                                            @if ($application->status == 0)
                                                <span class="eBadge ebg-info">{{ get_phrase('Pending') }}</span>
                                            @elseif($application->status == 1)
                                                <span class="eBadge ebg-success">{{ get_phrase('Approved') }}</span>
                                            @else
                                                <span class="eBadge ebg-danger">{{ get_phrase('Rejected') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $application->note ?? '-' }}</td>
                                        <td class="text-end">
                                            @if ($application->status == 0)
                                                <div class="adminTable-action">
                                                    <button type="button" class="eBtn eBtn-black dropdown-toggle table-action-btn-2" data-bs-toggle="dropdown" aria-expanded="false">
                                                        {{ get_phrase('Actions') }}
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end eDropdown-menu-2 eDropdown-table-action">
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="confirmAction('{{ route('admin.hostel.applications.approve', $application->id) }}', '{{ get_phrase('Are you sure to approve this application?') }}')">
                                                                {{ get_phrase('Approve') }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="javascript:;" onclick="confirmAction('{{ route('admin.hostel.applications.reject', $application->id) }}', '{{ get_phrase('Are you sure to reject this application?') }}')">
                                                                {{ get_phrase('Reject') }}
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @else
                                                <span class="text-muted">{{ get_phrase('No actions') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty_box center">
                        <img class="mb-3" width="150px" src="{{ asset('assets/images/empty_box.png') }}" />
                        <br>
                        <span class="">{{ get_phrase('No applications found') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function confirmAction(url, message) {
            if (confirm(message)) {
                window.location.href = url;
            }
        }
    </script>
@endsection
