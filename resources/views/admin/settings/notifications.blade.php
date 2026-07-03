@extends('admin.navigation')
@section('content')
<div class="mainSection-title"><div class="row"><div class="col-12">
    <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
        <div class="d-flex flex-column">
            <h4>{{ get_phrase('Notification Settings') }}</h4>
            <ul class="d-flex align-items-center eBreadcrumb-2">
                <li><a href="{{ route('admin.settings.school') }}">{{ get_phrase('Settings') }}</a></li>
                <li><a href="#">{{ get_phrase('Notifications') }}</a></li>
            </ul>
        </div>
    </div>
</div></div></div>

@include('admin.settings.partials.settings_nav', ['active' => 'notifications'])
@if(session('success'))<div class="alert alert-success"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>@endif

<form action="{{ route('admin.settings.notifications.save') }}" method="POST">
@csrf
<div class="row">
    <div class="col-md-6 mb-3">
        <div class="eSection-wrap h-100">
            <div class="p-3 border-bottom"><strong><i class="bi bi-envelope text-primary"></i> {{ get_phrase('Email Notifications') }}</strong></div>
            <div class="p-4">
            @php
            $email_notifs = [
                'notif_new_student'   => ['label' => 'New Student Registration', 'desc' => 'Send email when a new student registers'],
                'notif_fee_paid'      => ['label' => 'Fee Payment Received', 'desc' => 'Send email when a fee payment is confirmed'],
                'notif_exam_published'=> ['label' => 'Exam Published', 'desc' => 'Notify students when an exam is published'],
                'notif_assignment'    => ['label' => 'Assignment Posted', 'desc' => 'Notify students when a new assignment is posted'],
                'notif_leave_request' => ['label' => 'Leave Request Submitted', 'desc' => 'Notify admin when staff submits a leave request'],
                'notif_notice'        => ['label' => 'New Notice Posted', 'desc' => 'Notify all users when a notice is posted'],
            ];
            @endphp
            @foreach($email_notifs as $key => $info)
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                <div>
                    <div style="font-size:13px;font-weight:600">{{ get_phrase($info['label']) }}</div>
                    <div style="font-size:11px;color:#6c757d">{{ get_phrase($info['desc']) }}</div>
                </div>
                <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer">
                    <input type="checkbox" name="{{ $key }}" {{ ($notif_settings[$key] ?? false) ? 'checked' : '' }}>
                    <span style="font-size:11px">{{ ($notif_settings[$key] ?? false) ? get_phrase('On') : get_phrase('Off') }}</span>
                </label>
            </div>
            @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="eSection-wrap h-100">
            <div class="p-3 border-bottom"><strong><i class="bi bi-phone text-success"></i> {{ get_phrase('SMS Notifications') }}</strong></div>
            <div class="p-4">
            @php
            $sms_notifs = [
                'sms_new_student' => ['label' => 'New Student SMS', 'desc' => 'Send SMS to new student on registration'],
                'sms_fee_paid'    => ['label' => 'Payment Confirmation SMS', 'desc' => 'Send SMS receipt on fee payment'],
                'sms_exam_result' => ['label' => 'Exam Result SMS', 'desc' => 'Send SMS when exam results are published'],
            ];
            @endphp
            @foreach($sms_notifs as $key => $info)
            <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                <div>
                    <div style="font-size:13px;font-weight:600">{{ get_phrase($info['label']) }}</div>
                    <div style="font-size:11px;color:#6c757d">{{ get_phrase($info['desc']) }}</div>
                </div>
                <label class="d-flex align-items-center gap-2 mb-0" style="cursor:pointer">
                    <input type="checkbox" name="{{ $key }}" {{ ($notif_settings[$key] ?? false) ? 'checked' : '' }}>
                    <span style="font-size:11px">{{ ($notif_settings[$key] ?? false) ? get_phrase('On') : get_phrase('Off') }}</span>
                </label>
            </div>
            @endforeach
            <div class="alert alert-info mt-3" style="font-size:11px">
                <i class="bi bi-info-circle"></i> {{ get_phrase('SMS requires an SMS gateway integration (Twilio, Africa\'s Talking, etc.)') }}
            </div>
            </div>
        </div>
    </div>
</div>
<div class="text-end"><button type="submit" class="eBtn eBtn-primary"><i class="bi bi-save"></i> {{ get_phrase('Save Settings') }}</button></div>
</form>
@endsection
