<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Offer Letter — {{ $admission->first_name }} {{ $admission->last_name }}</title>
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; margin: 40px; }
.header { text-align: center; margin-bottom: 30px; }
.header h2 { margin: 0 0 5px; font-size: 20px; text-transform: uppercase; }
.header p { margin: 2px 0; font-size: 11px; color: #666; }
.ref { float: right; font-size: 11px; color: #888; }
.clearfix::after { content: ''; display: block; clear: both; }
h4 { color: #1a3c5e; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
table.info { width: 100%; border-collapse: collapse; margin: 15px 0; }
table.info td { padding: 5px 10px; border: 1px solid #eee; }
table.info td:first-child { font-weight: bold; width: 35%; background: #f8f8f8; }
.conditions { background: #f0f7ff; border: 1px solid #bee3f8; border-radius: 4px; padding: 12px; margin: 15px 0; }
.signature-area { margin-top: 50px; display: flex; justify-content: space-between; }
.sig-block { text-align: center; width: 200px; }
.sig-block .sig-line { border-top: 1px solid #333; padding-top: 5px; font-size: 11px; }
.footer { text-align: center; font-size: 10px; color: #888; margin-top: 40px; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>
<div class="header">
    <h2>{{ $school->school_name ?? 'Institution Name' }}</h2>
    <p>{{ $school->address ?? '' }}</p>
    <p>Email: {{ $school->email ?? '' }} | Tel: {{ $school->phone ?? '' }}</p>
</div>
<div class="clearfix">
    <div class="ref">Ref: {{ $admission->app_number }}<br>Date: {{ $admission->offer_date?->format('d F Y') ?? date('d F Y') }}</div>
</div>
<br>
<p>Dear <strong>{{ $admission->first_name }} {{ $admission->last_name }}</strong>,</p>

<h4>OFFER OF ADMISSION</h4>

<p>We are pleased to inform you that following a review of your application, you have been offered admission to <strong>{{ $school->school_name ?? 'this institution' }}</strong> for the following programme:</p>

<table class="info">
    <tr><td>Programme</td><td>{{ optional($admission->programme)->name }}</td></tr>
    <tr><td>Programme Level</td><td>{{ ucfirst(optional($admission->programme)->level ?? '') }}</td></tr>
    <tr><td>Mode of Study</td><td>{{ ucwords(str_replace('_',' ', optional($admission->programme)->mode ?? '')) }}</td></tr>
    <tr><td>Duration</td><td>{{ optional($admission->programme)->duration }} year(s)</td></tr>
    <tr><td>Intake Session</td><td>{{ optional($admission->intakeSession)->name }}</td></tr>
    <tr><td>Application Number</td><td>{{ $admission->app_number }}</td></tr>
</table>

<div class="conditions">
    <strong>Conditions of this Offer:</strong>
    <ol>
        <li>This offer is subject to verification of your original academic certificates.</li>
        <li>You are required to report for registration on or before the commencement of the semester.</li>
        <li>Please pay the required tuition and other fees upon registration.</li>
        <li>This offer is non-transferable and valid for the stated intake session only.</li>
    </ol>
    @if($admission->notes)
    <p><strong>Additional Notes:</strong> {{ $admission->notes }}</p>
    @endif
</div>

<p>Congratulations on this achievement. We look forward to welcoming you as a student at our institution.</p>
<p>Yours sincerely,</p>

<div class="signature-area">
    <div class="sig-block">
        <br><br>
        <div class="sig-line">Registrar</div>
    </div>
    <div class="sig-block">
        <br><br>
        <div class="sig-line">Principal / Vice-Chancellor</div>
    </div>
</div>

<div class="footer">
    {{ $school->school_name ?? '' }} &bull; {{ $school->address ?? '' }}<br>
    This letter was generated on {{ date('d F Y') }}
</div>
</body>
</html>
