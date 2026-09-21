@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Student Affairs Requests') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="#">{{ get_phrase('Student Affairs Requests') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

@if(session('message'))<div class="alert alert-success">{{ session('message') }}</div>@endif

<div class="row">
    <div class="col-12">
        <div class="eSection-wrap mb-3">
            <form method="GET" action="{{ route('admin.student_requests.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="eForm-label">{{ get_phrase('Type') }}</label>
                    <select name="type" class="form-select eForm-select">
                        <option value="">{{ get_phrase('All') }}</option>
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}" {{ $type === $value ? 'selected' : '' }}>{{ get_phrase($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="eForm-label">{{ get_phrase('Status') }}</label>
                    <select name="status" class="form-select eForm-select">
                        <option value="">{{ get_phrase('All') }}</option>
                        @foreach(['pending', 'approved', 'rejected'] as $s)
                            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="eBtn eBtn-primary">{{ get_phrase('Filter') }}</button>
                </div>
            </form>
        </div>

        <div class="eSection-wrap">
            <div class="table-responsive">
                <table class="table eTable">
                    <thead>
                        <tr>
                            <th>{{ get_phrase('Student') }}</th>
                            <th>{{ get_phrase('Type') }}</th>
                            <th>{{ get_phrase('Subject') }}</th>
                            <th>{{ get_phrase('Status') }}</th>
                            <th>{{ get_phrase('Submitted') }}</th>
                            <th class="text-end">{{ get_phrase('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>{{ $req->student?->name ?? '—' }}</td>
                            <td><span class="badge bg-secondary">{{ get_phrase($types[$req->type] ?? $req->type) }}</span></td>
                            <td>{{ $req->subject }}</td>
                            <td>
                                <span class="badge bg-{{ $req->status === 'approved' ? 'success' : ($req->status === 'rejected' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($req->status) }}
                                </span>
                            </td>
                            <td>{{ $req->created_at->format('d M Y') }}</td>
                            <td class="text-end">
                                <button type="button" class="eBtn eBtn-sm eBtn-primary" data-bs-toggle="collapse" data-bs-target="#req-{{ $req->id }}">
                                    {{ get_phrase('Review') }}
                                </button>
                            </td>
                        </tr>
                        <tr class="collapse" id="req-{{ $req->id }}">
                            <td colspan="6">
                                @if($req->type === 'transfer')
                                    <dl class="row mb-2">
                                        <dt class="col-sm-3">{{ get_phrase('Transfer Type') }}</dt>
                                        <dd class="col-sm-9">{{ $transferTypes[$req->transfer_type] ?? '—' }}</dd>
                                        <dt class="col-sm-3">{{ get_phrase('Transfer To') }}</dt>
                                        <dd class="col-sm-9">{{ $req->transferToProgramme?->name ?? '—' }}</dd>
                                        <dt class="col-sm-3">{{ get_phrase('Reason') }}</dt>
                                        <dd class="col-sm-9">{{ $transferReasons[$req->transfer_reason] ?? '—' }}</dd>
                                        <dt class="col-sm-3">{{ get_phrase('Phone') }}</dt>
                                        <dd class="col-sm-9">{{ $req->phone_number ?? '—' }}</dd>
                                        @if($req->document_path)
                                            <dt class="col-sm-3">{{ get_phrase('Document') }}</dt>
                                            <dd class="col-sm-9"><a href="{{ asset($req->document_path) }}" target="_blank">{{ get_phrase('View document') }}</a></dd>
                                        @endif
                                    </dl>
                                @endif
                                <p class="text-muted mb-2">{{ $req->details }}</p>
                                <form method="POST" action="{{ route('admin.student_requests.update', $req->id) }}" class="row g-2">
                                    @csrf
                                    <div class="col-md-6">
                                        <textarea name="admin_response" class="form-control eForm-control" rows="2" placeholder="{{ get_phrase('Response (optional)') }}">{{ $req->admin_response }}</textarea>
                                    </div>
                                    <div class="col-md-6 d-flex gap-2 align-items-start">
                                        <button type="submit" name="status" value="approved" class="eBtn eBtn-sm eBtn-success">{{ get_phrase('Approve') }}</button>
                                        <button type="submit" name="status" value="rejected" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Reject') }}</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No requests found') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
