@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Reports & Analytics') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.dashboard') }}">{{ get_phrase('Home') }}</a></li>
                <li><a href="#">{{ get_phrase('Reports') }}</a></li>
            </ul>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.reports.export', 'students') }}" class="eBtn eBtn-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export Students CSV') }}</a>
            <a href="{{ route('admin.reports.export', 'finance') }}" class="eBtn eBtn-secondary"><i class="bi bi-download"></i> {{ get_phrase('Export Finance CSV') }}</a>
        </div>
    </div>
</div></div></div>

{{-- Summary Stats --}}
<div class="row mb-3">
    <div class="col-md-3 col-6">
        <div class="eSection-wrap text-center p-3" style="border-top:3px solid #1a3a6b">
            <div style="font-size:28px;font-weight:800;color:#1a3a6b">{{ $total_students }}</div>
            <div style="font-size:11px;color:#6c757d">{{ get_phrase('Total Students') }}</div>
            <div style="font-size:10px;color:#198754;margin-top:2px">{{ $active_students }} {{ get_phrase('active') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="eSection-wrap text-center p-3" style="border-top:3px solid #c8860a">
            <div style="font-size:28px;font-weight:800;color:#c8860a">{{ $total_staff }}</div>
            <div style="font-size:11px;color:#6c757d">{{ get_phrase('Total Staff') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="eSection-wrap text-center p-3" style="border-top:3px solid #198754">
            <div style="font-size:28px;font-weight:800;color:#198754">UGX {{ number_format($fee_collected) }}</div>
            <div style="font-size:11px;color:#6c757d">{{ get_phrase('Fees Collected') }}</div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="eSection-wrap text-center p-3" style="border-top:3px solid #dc3545">
            <div style="font-size:28px;font-weight:800;color:#dc3545">UGX {{ number_format($fee_outstanding) }}</div>
            <div style="font-size:11px;color:#6c757d">{{ get_phrase('Outstanding Fees') }}</div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Enrolment by Programme --}}
    <div class="col-lg-7 mb-3">
        <div class="eSection-wrap">
            <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                <strong>{{ get_phrase('Enrolment by Programme') }}</strong>
                <a href="{{ route('admin.reports.students') }}" class="eBtn eBtn-sm eBtn-secondary">{{ get_phrase('Full Report') }}</a>
            </div>
            <div class="p-3">
            @if($programmes->isEmpty())
                <div class="text-center text-muted py-3">{{ get_phrase('No programme data') }}</div>
            @else
            <div class="table-responsive">
                <table class="table eTable" style="font-size:12px">
                    <thead><tr><th>{{ get_phrase('Programme') }}</th><th>{{ get_phrase('Level') }}</th><th>{{ get_phrase('Mode') }}</th><th class="text-center">{{ get_phrase('Students') }}</th></tr></thead>
                    <tbody>
                    @foreach($programmes->take(10) as $p)
                    <tr>
                        <td><div style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $p->name }}">{{ $p->name }}</div></td>
                        <td><span class="badge bg-primary" style="font-size:9px">{{ $p->level }}</span></td>
                        <td>{{ ucfirst($p->mode ?? '—') }}</td>
                        <td class="text-center"><strong>{{ $p->student_count ?? 0 }}</strong></td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
            </div>
        </div>
    </div>

    {{-- Right column --}}
    <div class="col-lg-5 mb-3">
        {{-- Attendance today --}}
        <div class="eSection-wrap mb-3">
            <div class="p-3 border-bottom"><strong>{{ get_phrase('Attendance Overview') }}</strong></div>
            <div class="p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:12px">{{ get_phrase("Today's Attendance") }}</span>
                    <strong style="color:#1a3a6b">{{ $today_present }} / {{ $total_enrolled }}</strong>
                </div>
                @php $att_pct = $total_enrolled > 0 ? round($today_present/$total_enrolled*100) : 0 @endphp
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-success" style="width:{{ $att_pct }}%"></div>
                </div>
                <div class="text-end mt-1" style="font-size:10px;color:#6c757d">{{ $att_pct }}%</div>
                <a href="{{ route('admin.reports.attendance') }}" class="eBtn eBtn-sm eBtn-secondary mt-2">{{ get_phrase('Attendance Report') }}</a>
            </div>
        </div>

        {{-- Exam stats --}}
        <div class="eSection-wrap">
            <div class="p-3 border-bottom"><strong>{{ get_phrase('Online Exam Results') }}</strong></div>
            <div class="p-3">
                @if($total_submissions > 0)
                @php $pass_pct = round($passed_submissions/$total_submissions*100) @endphp
                <div class="d-flex justify-content-around text-center mb-2">
                    <div>
                        <div style="font-size:22px;font-weight:800;color:#1a3a6b">{{ $total_submissions }}</div>
                        <div style="font-size:10px;color:#6c757d">{{ get_phrase('Submissions') }}</div>
                    </div>
                    <div>
                        <div style="font-size:22px;font-weight:800;color:#198754">{{ $passed_submissions }}</div>
                        <div style="font-size:10px;color:#6c757d">{{ get_phrase('Passed') }}</div>
                    </div>
                    <div>
                        <div style="font-size:22px;font-weight:800;color:#dc3545">{{ $total_submissions - $passed_submissions }}</div>
                        <div style="font-size:10px;color:#6c757d">{{ get_phrase('Failed') }}</div>
                    </div>
                </div>
                <div class="progress" style="height:8px">
                    <div class="progress-bar bg-success" style="width:{{ $pass_pct }}%"></div>
                    <div class="progress-bar bg-danger" style="width:{{ 100-$pass_pct }}%"></div>
                </div>
                <div class="text-center mt-1" style="font-size:10px;color:#6c757d">{{ get_phrase('Pass Rate') }}: {{ $pass_pct }}%</div>
                @else
                <div class="text-center text-muted py-2" style="font-size:12px">{{ get_phrase('No exam submissions yet') }}</div>
                @endif
                <a href="{{ route('admin.reports.exams') }}" class="eBtn eBtn-sm eBtn-secondary mt-2">{{ get_phrase('Exam Report') }}</a>
            </div>
        </div>
    </div>
</div>

{{-- Quick Report Links --}}
<div class="row">
    <div class="col-12">
        <div class="eSection-wrap">
            <div class="p-3 border-bottom"><strong>{{ get_phrase('Available Reports') }}</strong></div>
            <div class="p-3">
                <div class="row g-3">
                    @php $report_cards = [
                        ['icon'=>'bi-person-check','title'=>'Student Enrolment Report','desc'=>'Full list of enrolled students with status and programme','color'=>'#1a3a6b','route'=>route('admin.reports.students')],
                        ['icon'=>'bi-cash-stack','title'=>'Fee Collection Report','desc'=>'Invoice summary, payments received and outstanding balances','color'=>'#198754','route'=>route('admin.reports.finance')],
                        ['icon'=>'bi-calendar-check','title'=>'Attendance Report','desc'=>'Daily class attendance records with present/absent breakdown','color'=>'#c8860a','route'=>route('admin.reports.attendance')],
                        ['icon'=>'bi-laptop','title'=>'Online Exam Results','desc'=>'CBT exam submissions, scores and pass/fail analysis','color'=>'#6f42c1','route'=>route('admin.reports.exams')],
                        ['icon'=>'bi-graph-up','title'=>'Gradebook Report','desc'=>'Student academic marks by class, section and exam category','color'=>'#0dcaf0','route'=>route('admin.gradebook')],
                        ['icon'=>'bi-book','title'=>'Library Report','desc'=>'Book issue and return records','color'=>'#fd7e14','route'=>route('admin.book_issue.list')],
                        ['icon'=>'bi-building','title'=>'Asset Register','desc'=>'Institutional asset inventory and condition report','color'=>'#20c997','route'=>route('admin.assets.index')],
                        ['icon'=>'bi-clock-history','title'=>'Audit Log','desc'=>'Full system activity log with user actions and timestamps','color'=>'#dc3545','route'=>route('admin.audit_log.index')],
                    ]; @endphp
                    @foreach($report_cards as $rc)
                    <div class="col-md-3 col-sm-6">
                        <a href="{{ $rc['route'] }}" style="text-decoration:none">
                            <div class="p-3 rounded border h-100" style="transition:.2s" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background=''">
                                <div style="width:40px;height:40px;border-radius:8px;background:{{ $rc['color'] }}18;display:flex;align-items:center;justify-content:center;margin-bottom:10px">
                                    <i class="bi {{ $rc['icon'] }}" style="color:{{ $rc['color'] }};font-size:18px"></i>
                                </div>
                                <div style="font-size:13px;font-weight:600;color:#212529;margin-bottom:4px">{{ get_phrase($rc['title']) }}</div>
                                <div style="font-size:11px;color:#6c757d">{{ get_phrase($rc['desc']) }}</div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
