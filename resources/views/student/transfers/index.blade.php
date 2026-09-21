@extends('student.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('My Transfers') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('student.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="#">{{ get_phrase('My Transfers') }}</a></li>
            </ul>
        </div>
        <button type="button" class="eBtn eBtn-primary" data-bs-toggle="modal" data-bs-target="#transferApplicationModal">
            {{ get_phrase('Add') }}
        </button>
    </div>
</div></div></div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <div class="table-responsive">
                <table class="table eTable">
                    <thead>
                        <tr>
                            <th>{{ get_phrase('Transfer Type') }}</th>
                            <th>{{ get_phrase('Transfer To') }}</th>
                            <th>{{ get_phrase('Reason') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th>{{ get_phrase('Submitted') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>{{ $transferTypes[$transfer->transfer_type] ?? '—' }}</td>
                            <td>{{ $transfer->transferToProgramme?->name ?? '—' }}</td>
                            <td>{{ $transferReasons[$transfer->transfer_reason] ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ $transfer->status === 'approved' ? 'success' : ($transfer->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($transfer->status) }}
                                </span>
                                @if($transfer->admin_response)
                                    <div class="text-muted small mt-1">{{ $transfer->admin_response }}</div>
                                @endif
                            </td>
                            <td>{{ $transfer->created_at->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ get_phrase('No transfer applications submitted yet') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Inter/Intra Programme Transfer Application modal --}}
<div class="modal fade" id="transferApplicationModal" tabindex="-1" aria-labelledby="transferApplicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('student.transfers.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="transferApplicationModalLabel">{{ get_phrase('Inter/Intra Programme Transfer Application') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Current Programme') }}</label>
                            <input type="text" class="form-control eForm-control" value="{{ $currentProgramme->name ?? get_phrase('Not applicable') }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Name') }}</label>
                            <input type="text" class="form-control eForm-control" value="{{ auth()->user()->name }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Registration Number') }}</label>
                            <input type="text" class="form-control eForm-control" value="{{ auth()->user()->code }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Email') }}</label>
                            <input type="text" class="form-control eForm-control" value="{{ auth()->user()->email }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Phone Number') }}</label>
                            <input type="text" name="phone_number" class="form-control eForm-control" value="{{ old('phone_number', $defaultPhone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Supporting Documents') }}</label>
                            <input type="file" name="document" class="form-control eForm-control" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Transfer Type') }} <span class="text-danger">*</span></label>
                            <select name="transfer_type" class="form-select eForm-select" required>
                                <option value="">{{ get_phrase('Select') }}</option>
                                @foreach($transferTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('transfer_type') === $value ? 'selected' : '' }}>{{ get_phrase($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Transfer to Programme') }} <span class="text-danger">*</span></label>
                            <select name="transfer_to_programme_id" class="form-select eForm-select" required>
                                <option value="">{{ get_phrase('Select') }}</option>
                                @foreach($programmes as $prog)
                                    <option value="{{ $prog->id }}" {{ (string) old('transfer_to_programme_id') === (string) $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="eForm-label">{{ get_phrase('Reason for Transfer') }} <span class="text-danger">*</span></label>
                            <select name="transfer_reason" class="form-select eForm-select" required>
                                <option value="">{{ get_phrase('Select') }}</option>
                                @foreach($transferReasons as $value => $label)
                                    <option value="{{ $value }}" {{ old('transfer_reason') === $value ? 'selected' : '' }}>{{ get_phrase($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="eForm-label">{{ get_phrase('Additional Details') }}</label>
                            <textarea name="details" class="form-control eForm-control" rows="3">{{ old('details') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="eBtn eBtn-secondary" data-bs-dismiss="modal">{{ get_phrase('Close') }}</button>
                    <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Save') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modal = new bootstrap.Modal(document.getElementById('transferApplicationModal'));
        modal.show();
    });
</script>
@endif
@endsection
