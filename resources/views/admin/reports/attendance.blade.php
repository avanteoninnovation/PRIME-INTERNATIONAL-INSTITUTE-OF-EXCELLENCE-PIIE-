@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Attendance Report') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.reports.index') }}">{{ get_phrase('Reports') }}</a></li>
                <li><a href="#">{{ get_phrase('Attendance') }}</a></li>
            </ul>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.reports.export', ['type' => 'attendance', 'class_id' => $class_id, 'from' => $date_from, 'to' => $date_to]) }}" class="eBtn eBtn-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export CSV') }}</a>
            <a href="{{ route('admin.reports.index') }}" class="eBtn eBtn-secondary"><i class="bi bi-arrow-left"></i> {{ get_phrase('Back') }}</a>
        </div>
    </div>
</div></div></div>

<div class="row mb-3"><div class="col-12">
    <form class="d-flex gap-3 align-items-end flex-wrap" method="GET">
        <div><label class="eForm-label">{{ get_phrase('Class') }}</label>
            <select name="class_id" class="form-control eForm-control">
                @foreach($classes as $c)<option value="{{ $c->id }}" {{ $c->id==$class_id?'selected':'' }}>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div><label class="eForm-label">{{ get_phrase('From') }}</label><input type="date" class="form-control eForm-control" name="from" value="{{ $date_from }}"></div>
        <div><label class="eForm-label">{{ get_phrase('To') }}</label><input type="date" class="form-control eForm-control" name="to" value="{{ $date_to }}"></div>
        <button class="eBtn eBtn-primary" type="submit"><i class="bi bi-funnel"></i> {{ get_phrase('Filter') }}</button>
    </form>
</div></div>

<div class="row"><div class="col-12"><div class="eSection-wrap">
    <div class="table-responsive">
        <table class="table eTable" style="font-size:12px">
            <thead><tr><th>#</th><th>{{ get_phrase('Student') }}</th><th class="text-center">{{ get_phrase('Present') }}</th><th class="text-center">{{ get_phrase('Absent') }}</th><th class="text-center">{{ get_phrase('Total Days') }}</th><th class="text-center">{{ get_phrase('Attendance %') }}</th></tr></thead>
            <tbody>
            @forelse($attendance as $i => $a)
            @php $pct = $a->total_days > 0 ? round($a->present_days/$a->total_days*100) : 0; @endphp
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ optional($a->student)->name ?? "Student #{$a->student_id}" }}</td>
                <td class="text-center" style="color:#198754;font-weight:600">{{ $a->present_days }}</td>
                <td class="text-center" style="color:#dc3545;font-weight:600">{{ $a->absent_days }}</td>
                <td class="text-center">{{ $a->total_days }}</td>
                <td class="text-center">
                    <div class="progress" style="height:6px;min-width:80px">
                        <div class="progress-bar bg-{{ $pct>=75?'success':($pct>=50?'warning':'danger') }}" style="width:{{ $pct }}%"></div>
                    </div>
                    <span style="font-size:10px">{{ $pct }}%</span>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ get_phrase('No attendance data for selected period') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div></div></div>
@endsection
