@extends('admin.navigation')
@section('content')

@php
    use App\Models\Admission;
    use App\Models\AdmissionDocument;
    use App\Models\ApplicationPayment;
    use App\Support\Admissions\ApplicationFee;

    $blank = '—';
@endphp

<div class="mainSection-title">
    <div class="row"><div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
            <div class="d-flex flex-column">
                <h4>{{ $admission->full_name }}</h4>
                <ul class="d-flex align-items-center eBreadcrumb-2">
                    <li><a href="{{ route('admin.hei_admissions.index') }}">{{ get_phrase('Admissions') }}</a></li>
                    <li><a href="#">{{ $admission->app_number }}</a></li>
                </ul>
            </div>
            <div class="export-btn-area d-flex gap-2">
                <a href="{{ route('admin.hei_admissions.index') }}" class="export_btn export_btn-outline">
                    <i class="bi bi-arrow-left"></i> {{ get_phrase('Back to Applications') }}
                </a>
                @if(in_array($admission->status, ['accepted', 'enrolled']))
                    <a href="{{ route('admin.hei_admissions.offer_letter', $admission->id) }}" class="export_btn">
                        <i class="bi bi-file-pdf"></i> {{ get_phrase('Offer Letter') }}
                    </a>
                @endif
            </div>
        </div>
    </div></div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
@if($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="row g-3">
    {{-- ── Left column: the application itself ─────────────────────── --}}
    <div class="col-lg-8">

        {{-- Snapshot --}}
        <div class="eSection-wrap mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                <div>
                    <div class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em;">{{ get_phrase('Application Number') }}</div>
                    <h5 class="mb-0">{{ $admission->app_number }}</h5>
                </div>
                <div class="d-flex gap-2 align-items-center flex-wrap">
                    <span class="badge bg-{{ $admission->statusColor() }}">{{ $admission->statusLabel() }}</span>
                    <span class="badge bg-{{ $admission->isFeeSettled() ? 'success' : 'warning' }}">
                        {{ get_phrase('Fee') }}: {{ ucfirst($admission->fee_status) }}
                    </span>
                    <span class="badge bg-light text-dark">
                        {{ $admission->source === 'public' ? get_phrase('Applicant Portal') : get_phrase('Staff Entry') }}
                    </span>
                </div>
            </div>

            <div class="progress mb-2" style="height:8px;">
                <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%"></div>
            </div>
            <small class="text-muted">{{ $progress }}% {{ get_phrase('complete') }}</small>

            @if(! empty($blockers))
                <div class="alert alert-warning mt-3 mb-0 py-2">
                    <strong>{{ get_phrase('Outstanding on the applicant side') }}:</strong>
                    {{ implode(', ', $blockers) }}
                </div>
            @endif
        </div>

        {{-- Personal --}}
        <div class="eSection-wrap mb-3">
            <h5 class="mb-3"><i class="bi bi-person-vcard"></i> {{ get_phrase('Personal Information') }}</h5>
            <div class="row g-2">
                @foreach([
                    get_phrase('Full Name')            => $admission->full_name,
                    get_phrase('Email')                => $admission->email,
                    get_phrase('Phone')                => $admission->phone,
                    get_phrase('Date of Birth')        => optional($admission->dob)->format('d M Y'),
                    get_phrase('Gender')               => $admission->gender,
                    get_phrase('Marital Status')       => $admission->marital_status,
                    get_phrase('Nationality')          => $admission->nationality,
                    get_phrase('Country of Residence') => $admission->country_of_residence,
                    get_phrase('National ID')          => $admission->national_id_no,
                    get_phrase('Passport No.')         => $admission->passport_no,
                    get_phrase('Address')              => trim($admission->physical_address . ' ' . $admission->city),
                    get_phrase('Religion')             => $admission->religion,
                ] as $label => $value)
                    <div class="col-md-6 d-flex gap-2 py-1" style="border-bottom:1px solid #f1f2f4;">
                        <span class="text-muted" style="min-width:150px; font-size:13px;">{{ $label }}</span>
                        <strong style="font-size:13.5px;">{{ $value ?: $blank }}</strong>
                    </div>
                @endforeach
            </div>

            @if($admission->has_disability)
                <div class="alert alert-info mt-3 mb-0 py-2">
                    <strong>{{ get_phrase('Declared disability / support need') }}:</strong>
                    {{ $admission->disability_details ?: get_phrase('No details given') }}
                </div>
            @endif

            <h6 class="mt-4 mb-2 text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:.05em;">
                {{ get_phrase('Next of Kin') }}
            </h6>
            <div class="row g-2">
                @foreach([
                    get_phrase('Name')         => $admission->nok_name,
                    get_phrase('Relationship') => $admission->nok_relationship,
                    get_phrase('Phone')        => $admission->nok_phone,
                    get_phrase('Email')        => $admission->nok_email,
                    get_phrase('Address')      => $admission->nok_address,
                ] as $label => $value)
                    <div class="col-md-6 d-flex gap-2 py-1" style="border-bottom:1px solid #f1f2f4;">
                        <span class="text-muted" style="min-width:150px; font-size:13px;">{{ $label }}</span>
                        <strong style="font-size:13.5px;">{{ $value ?: $blank }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Programme --}}
        <div class="eSection-wrap mb-3">
            <h5 class="mb-3"><i class="bi bi-mortarboard"></i> {{ get_phrase('Programme Selection') }}</h5>
            <div class="row g-2">
                @foreach([
                    get_phrase('First Choice')   => optional($admission->programme)->name,
                    get_phrase('Second Choice')  => optional($admission->secondChoiceProgramme)->name,
                    get_phrase('Intake')         => optional($admission->intakeSession)->name,
                    get_phrase('Study Mode')     => $admission->study_mode,
                    get_phrase('Sponsorship')    => $admission->sponsor_type,
                    get_phrase('Sponsor')        => $admission->sponsor_name,
                    get_phrase('Sponsor Phone')  => $admission->sponsor_phone,
                    get_phrase('Heard About Us') => $admission->how_did_you_hear,
                ] as $label => $value)
                    <div class="col-md-6 d-flex gap-2 py-1" style="border-bottom:1px solid #f1f2f4;">
                        <span class="text-muted" style="min-width:150px; font-size:13px;">{{ $label }}</span>
                        <strong style="font-size:13.5px;">{{ $value ?: $blank }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Education --}}
        <div class="eSection-wrap mb-3">
            <h5 class="mb-3"><i class="bi bi-journal-text"></i> {{ get_phrase('Education History') }}</h5>

            @if($admission->educationHistory->isNotEmpty())
                <div class="table-responsive">
                    <table class="table eTable mb-0">
                        <thead><tr>
                            <th>{{ get_phrase('Institution') }}</th><th>{{ get_phrase('Award') }}</th>
                            <th>{{ get_phrase('Subject') }}</th><th>{{ get_phrase('Grade') }}</th><th>{{ get_phrase('Years') }}</th>
                        </tr></thead>
                        <tbody>
                            @foreach($admission->educationHistory as $qualification)
                                <tr>
                                    <td>{{ $qualification->institution }}</td>
                                    <td>{{ $qualification->award ?: $blank }}</td>
                                    <td>{{ $qualification->subject ?: $blank }}</td>
                                    <td>{{ $qualification->grade ?: $blank }}</td>
                                    <td>{{ $qualification->period ?: $blank }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($admission->qualifications)
                <div class="p-3 mt-3" style="background:#f8f9fb; border-radius:8px; white-space:pre-line; font-size:14px;">{{ $admission->qualifications }}</div>
            @endif

            @if($admission->educationHistory->isEmpty() && ! $admission->qualifications)
                <p class="text-muted mb-0">{{ get_phrase('No education history supplied.') }}</p>
            @endif
        </div>

        {{-- Documents --}}
        <div class="eSection-wrap mb-3">
            <h5 class="mb-3"><i class="bi bi-folder2-open"></i> {{ get_phrase('Supporting Documents') }}</h5>

            @forelse($docChecklist as $row)
                @php
                    $requirement = $row['requirement'];
                    $tone = ['verified' => 'success', 'pending' => 'primary', 'rejected' => 'danger', 'missing' => 'warning'][$row['state']] ?? 'secondary';
                @endphp

                <div class="p-3 mb-2" style="border:1px solid #e7e9ee; border-radius:8px;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <strong>{{ $requirement->label }} @if($requirement->is_required)<span class="text-danger">*</span>@endif</strong>
                        <span class="badge bg-{{ $tone }}">{{ ucfirst(str_replace('-', ' ', $row['state'])) }}</span>
                    </div>

                    @foreach($row['files'] as $file)
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 p-2" style="background:#f8f9fb; border-radius:6px;">
                            <i class="bi {{ $file->isImage() ? 'bi-file-image' : 'bi-file-earmark-pdf' }}"></i>
                            <div class="flex-grow-1">
                                <div style="font-size:13.5px; font-weight:600; word-break:break-all;">{{ $file->original_name }}</div>
                                <small class="text-muted">
                                    {{ $file->human_size }} · {{ $file->created_at->format('d M Y') }}
                                    @if($file->reviewed_at)
                                        · {{ get_phrase('Reviewed') }} {{ $file->reviewed_at->format('d M Y') }}
                                    @endif
                                    @if($file->review_note)
                                        <br><span class="text-danger">{{ $file->review_note }}</span>
                                    @endif
                                </small>
                            </div>

                            <a href="{{ $file->url }}" target="_blank" class="eBtn eBtn-sm eBtn-primary" title="{{ get_phrase('Open') }}">
                                <i class="bi bi-eye"></i>
                            </a>

                            <form action="{{ route('admin.hei_admissions.document.review', $file->id) }}" method="POST" class="d-flex gap-1">
                                @csrf
                                <input type="hidden" name="status" value="{{ AdmissionDocument::STATUS_VERIFIED }}">
                                <button type="submit" class="eBtn eBtn-sm eBtn-success" title="{{ get_phrase('Verify') }}"
                                        {{ $file->status === AdmissionDocument::STATUS_VERIFIED ? 'disabled' : '' }}>
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>

                            <button type="button" class="eBtn eBtn-sm eBtn-danger" title="{{ get_phrase('Reject') }}"
                                    onclick="document.getElementById('rejectDoc{{ $file->id }}').classList.toggle('d-none')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>

                        <form action="{{ route('admin.hei_admissions.document.review', $file->id) }}" method="POST"
                              id="rejectDoc{{ $file->id }}" class="d-none mt-2">
                            @csrf
                            <input type="hidden" name="status" value="{{ AdmissionDocument::STATUS_REJECTED }}">
                            <div class="d-flex gap-2">
                                <input type="text" name="review_note" class="form-control eForm-control"
                                       placeholder="{{ get_phrase('Why is this not acceptable? The applicant will see this.') }}" required>
                                <button type="submit" class="eBtn eBtn-danger">{{ get_phrase('Reject') }}</button>
                            </div>
                        </form>
                    @endforeach

                    @if($row['files']->isEmpty())
                        <small class="text-muted d-block mt-1">{{ get_phrase('Nothing uploaded yet.') }}</small>
                    @endif
                </div>
            @empty
                <p class="text-muted mb-0">{{ get_phrase('No document requirements are configured.') }}</p>
            @endforelse
        </div>

        {{-- Fee --}}
        <div class="eSection-wrap mb-3">
            <h5 class="mb-3"><i class="bi bi-credit-card"></i> {{ get_phrase('Application Fee') }}</h5>

            <p class="mb-3">
                {{ get_phrase('Payable') }}: <strong>{{ $feeAmount > 0 ? ApplicationFee::format((float) $feeAmount) : get_phrase('No fee for this intake') }}</strong>
                <span class="badge bg-{{ $admission->isFeeSettled() ? 'success' : 'warning' }} ms-2">{{ ucfirst($admission->fee_status) }}</span>
            </p>

            @forelse($admission->payments->sortByDesc('id') as $payment)
                <div class="p-3 mb-2" style="border:1px solid #e7e9ee; border-radius:8px;">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <strong>{{ ApplicationFee::format((float) $payment->amount) }}</strong>
                            <span class="text-muted"> · {{ ucfirst($payment->method) }} · {{ $payment->reference ?: $blank }}</span>
                            <br><small class="text-muted">{{ $payment->created_at->format('d M Y, H:i') }}</small>
                            @if($payment->note)<br><small class="text-muted">{{ $payment->note }}</small>@endif
                        </div>

                        <div class="d-flex gap-1 align-items-center flex-wrap">
                            @php
                                $payTone = ['paid' => 'success', 'waived' => 'success', 'pending' => 'primary', 'failed' => 'danger', 'rejected' => 'danger'][$payment->status] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $payTone }}">{{ ucfirst($payment->status) }}</span>

                            @if($payment->proof_file)
                                <a href="{{ $payment->proof_url }}" target="_blank" class="eBtn eBtn-sm eBtn-primary" title="{{ get_phrase('View proof') }}">
                                    <i class="bi bi-receipt"></i>
                                </a>
                            @endif

                            @if(! $payment->isSettled())
                                <form action="{{ route('admin.hei_admissions.payment.review', $payment->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ ApplicationPayment::STATUS_PAID }}">
                                    <button type="submit" class="eBtn eBtn-sm eBtn-success">{{ get_phrase('Confirm') }}</button>
                                </form>
                                <form action="{{ route('admin.hei_admissions.payment.review', $payment->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ ApplicationPayment::STATUS_REJECTED }}">
                                    <button type="submit" class="eBtn eBtn-sm eBtn-danger">{{ get_phrase('Reject') }}</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-muted mb-3">{{ get_phrase('No payment has been recorded.') }}</p>
            @endforelse

        </div>
    </div>

    {{-- ── Right column: acting on it ──────────────────────────────── --}}
    <div class="col-lg-4">

        {{-- Decision --}}
        <div class="eSection-wrap mb-3">
            <h5 class="mb-3"><i class="bi bi-clipboard-check"></i> {{ get_phrase('Decision') }}</h5>

            <form action="{{ route('admin.hei_admissions.status', $admission->id) }}" method="POST">
                @csrf
                <label class="form-label">{{ get_phrase('Status') }}</label>
                <select name="status" class="form-control eForm-control mb-3">
                    @foreach($statuses as $option)
                        <option value="{{ $option }}" {{ $admission->status === $option ? 'selected' : '' }}>
                            {{ ucwords(str_replace('_', ' ', $option)) }}
                        </option>
                    @endforeach
                </select>

                <label class="form-label">{{ get_phrase('Note to the applicant') }}</label>
                <textarea name="decision_note" class="form-control eForm-control mb-2" rows="3"
                          placeholder="{{ get_phrase('Included in the email the applicant receives. Optional.') }}">{{ $admission->decision_note }}</textarea>

                <label class="form-label">{{ get_phrase('Portal password (on enrolment)') }}</label>
                <input type="text" name="password" class="form-control eForm-control mb-3"
                       placeholder="{{ get_phrase('Leave blank to generate one automatically') }}">

                <button type="submit" class="eBtn w-100">{{ get_phrase('Update Status') }}</button>
            </form>
        </div>

        {{-- Request corrections --}}
        @if(! in_array($admission->status, [Admission::STATUS_DRAFT, Admission::STATUS_ENROLLED]))
            <div class="eSection-wrap mb-3">
                <h5 class="mb-3"><i class="bi bi-arrow-counterclockwise"></i> {{ get_phrase('Return for Corrections') }}</h5>
                <p class="text-muted" style="font-size:13.5px;">
                    {{ get_phrase('Reopens the application for editing and emails the applicant. It is not a rejection.') }}
                </p>

                <form action="{{ route('admin.hei_admissions.correction', $admission->id) }}" method="POST">
                    @csrf
                    <textarea name="correction_note" class="form-control eForm-control mb-2" rows="3" required
                              placeholder="{{ get_phrase('What exactly does the applicant need to change?') }}">{{ $admission->correction_note }}</textarea>
                    <button type="submit" class="eBtn eBtn-warning w-100">{{ get_phrase('Return to Applicant') }}</button>
                </form>
            </div>
        @endif

        {{-- Internal notes --}}
        <div class="eSection-wrap mb-3">
            <h5 class="mb-3"><i class="bi bi-lock"></i> {{ get_phrase('Internal Notes') }}</h5>
            <p class="text-muted" style="font-size:13.5px;">{{ get_phrase('Staff only — never shown to the applicant.') }}</p>

            <form action="{{ route('admin.hei_admissions.notes', $admission->id) }}" method="POST">
                @csrf
                <textarea name="notes" class="form-control eForm-control mb-2" rows="4">{{ $admission->notes }}</textarea>
                <button type="submit" class="eBtn eBtn-outline w-100">{{ get_phrase('Save Notes') }}</button>
            </form>
        </div>

        {{-- Timeline --}}
        <div class="eSection-wrap">
            <h5 class="mb-3"><i class="bi bi-activity"></i> {{ get_phrase('History') }}</h5>

            @forelse($timeline as $event)
                <div class="pb-3 mb-3" style="border-bottom:1px solid #f1f2f4;">
                    <div style="font-weight:600; font-size:14px;">{{ $event->title }}</div>
                    <small class="text-muted">
                        {{ $event->created_at->format('d M Y, H:i') }}
                        @if($event->actor_name) · {{ $event->actor_name }}@endif
                        @unless($event->is_visible_to_applicant)
                            <span class="badge bg-secondary ms-1">{{ get_phrase('Internal') }}</span>
                        @endunless
                    </small>
                    @if($event->note)<div class="mt-1" style="font-size:13.5px;">{{ $event->note }}</div>@endif
                </div>
            @empty
                <p class="text-muted mb-0">{{ get_phrase('No history recorded yet.') }}</p>
            @endforelse
        </div>
    </div>
</div>

@endsection
