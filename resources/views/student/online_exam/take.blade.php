@extends('student.navigation')
@section('content')
<style>
    /* Deterrent-level lockdown, not a security boundary — a determined
       student can always defeat client-side controls (a second device, a
       screenshot, disabling JS). This raises the bar for casual copying and
       gives staff a proctoring trail to review; it is not a substitute for
       exam design that assumes some attempts are dishonest. */
    #examTakeRoot {
        user-select: none;
        -webkit-user-select: none;
    }
    #examTakeRoot textarea,
    #examTakeRoot input[type="text"] {
        user-select: text;
        -webkit-user-select: text;
    }
    @media print {
        #examTakeRoot { display: none !important; }
        body::after {
            content: "{{ get_phrase('Printing is disabled during this exam.') }}";
            display: block;
            text-align: center;
            padding: 40px;
            font-size: 20px;
        }
    }
    #fullscreenWarning {
        position: fixed; inset: 0; z-index: 2000;
        background: rgba(20, 20, 20, .92);
        color: #fff;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        text-align: center; padding: 30px;
    }
    .save-status { font-size: 12px; }
    .save-status.is-saving { color: #b58900; }
    .save-status.is-saved { color: #0f6e3d; }
    .save-status.is-dirty { color: #b42318; }
    .save-status.is-retrying { color: #7a5af8; }
    .save-status.is-failed { color: #b42318; }
    .question-nav-dot {
        width: 32px; height: 32px; border-radius: 6px;
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 600; text-decoration: none;
        border: 1px solid #d0d5dd; color: #344054; margin: 2px;
    }
    .question-nav-dot.is-answered { background: #0f6e3d; border-color: #0f6e3d; color: #fff; }
</style>

<div id="fullscreenWarning" class="d-none">
    <i class="bi bi-exclamation-triangle-fill fs-1 mb-3"></i>
    <h4>{{ get_phrase('You have exited fullscreen') }}</h4>
    <p class="mb-4">{{ get_phrase('This exam requires fullscreen mode. This has been recorded. Return to fullscreen to continue.') }}</p>
    <button type="button" class="eBtn eBtn-primary" id="returnFullscreenBtn">{{ get_phrase('Return to Fullscreen') }}</button>
</div>

<div id="examTakeRoot">
    <div class="mainSection-title"><div class="row"><div class="col-12">
        <div class="d-flex justify-content-between align-items-center flex-wrap gr-15">
            <div class="d-flex flex-column">
                <h4>{{ $exam->title }}</h4>
                <ul class="d-flex align-items-center eBreadcrumb-2"><li><a href="{{ route('student.online_exam.list') }}">{{ get_phrase('Exams') }}</a></li><li><a href="#">{{ get_phrase('Take Exam') }}</a></li></ul>
            </div>
            <div class="text-end">
                <span class="badge bg-danger fs-6" id="timer">--:--</span>
                <div id="overallSaveStatus" class="save-status mt-1"></div>
            </div>
        </div>
    </div></div></div>

    <div class="row">
        <div class="col-lg-9">
            <div class="eSection-wrap">
                @if($exam->instructions)
                <div class="alert alert-info mb-4"><strong>{{ get_phrase('Instructions') }}:</strong> {{ $exam->instructions }}</div>
                @endif

                @foreach($questions as $qi => $q)
                @php
                    $existing = $existingAnswers->get($q->id);
                    $options = $optionOrders->get($q->id, []);
                    $publicQuestion = $q->public_question ?? null;
                    $questionType = $q->normalized_type;
                    $structuredOptions = is_array($publicQuestion) ? ($publicQuestion['options'] ?? []) : [];
                    $matchingLeft = is_array($publicQuestion) ? ($publicQuestion['left_items'] ?? []) : [];
                    $matchingRight = is_array($publicQuestion) ? ($publicQuestion['right_items'] ?? []) : [];
                    $orderingItems = is_array($publicQuestion) ? ($publicQuestion['items'] ?? []) : [];
                    $structuredPrompt = is_array($publicQuestion) ? ($publicQuestion['prompt'] ?? $q->question) : $q->question;
                    $existingPayload = optional($existing)->answer_payload ? json_decode($existing->answer_payload, true) : null;
                @endphp
                <div class="card mb-3 online-exam-question-card" data-question-id="{{ $q->id }}" id="question-block-{{ $q->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <p class="mb-2"><strong>Q{{ $qi + 1 }}.</strong> @if($questionType !== 'fill_blank' || $q->question_schema_version === null){{ $q->question }}@endif
                               <span class="badge bg-secondary ms-2">{{ $q->marks }} {{ get_phrase('mark(s)') }}</span></p>
                            <span class="save-status" id="save-status-{{ $q->id }}"></span>
                        </div>

                        @if($questionType === 'fill_blank' && $q->question_schema_version !== null)
                            @foreach(preg_split('/(\[\[[a-z][a-z0-9_-]{0,31}\]\])/i', $structuredPrompt, -1, PREG_SPLIT_DELIM_CAPTURE) as $promptPart)
                                @if(preg_match('/^\[\[([a-z][a-z0-9_-]{0,31})\]\]$/i', $promptPart, $blankMatch))
                                    <input class="form-control d-inline-block exam-answer-input fill-blank-input" style="max-width:260px" type="text" data-question-id="{{ $q->id }}" data-answer-type="fill_blank" data-blank-id="{{ $blankMatch[1] }}" value="{{ data_get($existingPayload, 'blanks.'.$blankMatch[1], '') }}" aria-label="{{ get_phrase('Answer blank') }} {{ $blankMatch[1] }}">
                                @else
                                    {{ $promptPart }}
                                @endif
                            @endforeach
                        @elseif($questionType === 'multiple_select')
                            <div class="small text-muted mb-2">{{ get_phrase('Select all that apply.') }}</div>
                            @foreach($structuredOptions as $option)
                            <div class="form-check">
                                <input class="form-check-input exam-answer-input" type="checkbox" value="{{ $option['id'] }}"
                                       id="q{{ $q->id }}{{ $option['id'] }}" data-question-id="{{ $q->id }}" data-answer-type="multiple_select"
                                       @checked(in_array($option['id'], (array) data_get($existingPayload, 'selected_option_ids', []), true))>
                                <label class="form-check-label" for="q{{ $q->id }}{{ $option['id'] }}">{{ $option['label'] }}</label>
                            </div>
                            @endforeach
                        @elseif($questionType === 'numeric')
                            <input class="form-control eForm-control exam-answer-input" type="text" inputmode="decimal"
                                   data-question-id="{{ $q->id }}" data-answer-type="numeric"
                                   value="{{ data_get($existingPayload, 'value', optional($existing)->answer_text) }}"
                                   placeholder="{{ get_phrase('Enter a numerical answer') }}">
                        @elseif($questionType === 'matching')
                            <div class="small text-muted mb-2">{{ get_phrase('Select the matching item for each row.') }}</div>
                            @foreach($matchingLeft as $left)
                            <div class="row align-items-center mb-2"><div class="col-md-5">{{ $left['text'] }}</div><div class="col-md-7"><select class="form-select exam-answer-input" data-question-id="{{ $q->id }}" data-answer-type="matching" data-left-id="{{ $left['id'] }}"><option value="">{{ get_phrase('Select a match') }}</option>@foreach($matchingRight as $right)<option value="{{ $right['id'] }}" @selected(data_get($existingPayload, 'pairs.'.$left['id']) === $right['id'])>{{ $right['text'] }}</option>@endforeach</select></div></div>
                            @endforeach
                        @elseif($questionType === 'ordering')
                            <div class="small text-muted mb-2">{{ get_phrase('Arrange the items in the correct order.') }}</div>
                            <ol class="ordering-list list-group" data-question-id="{{ $q->id }}">@foreach($orderingItems as $item)<li class="list-group-item d-flex justify-content-between align-items-center" data-order-id="{{ $item['id'] }}"><span>{{ $item['text'] }}</span><span><button type="button" class="btn btn-sm btn-outline-secondary order-up" aria-label="Move up">↑</button> <button type="button" class="btn btn-sm btn-outline-secondary order-down" aria-label="Move down">↓</button></span></li>@endforeach</ol>
                        @elseif($q->type === 'mcq')
                            @foreach($options as $optKey => $optText)
                            <div class="form-check">
                                <input class="form-check-input exam-answer-input" type="radio"
                                       name="answers[{{ $q->id }}]" value="{{ $optKey }}"
                                       id="q{{ $q->id }}{{ $optKey }}" data-question-id="{{ $q->id }}" data-answer-type="option"
                                       {{ optional($existing)->selected_option === $optKey ? 'checked' : '' }}>
                                <label class="form-check-label" for="q{{ $q->id }}{{ $optKey }}">{{ strtoupper($optKey) }}. {{ $optText }}</label>
                            </div>
                            @endforeach
                        @elseif($q->type === 'true_false')
                            <div class="form-check">
                                <input class="form-check-input exam-answer-input" type="radio" name="answers[{{ $q->id }}]" value="true"
                                       id="q{{ $q->id }}t" data-question-id="{{ $q->id }}" data-answer-type="option"
                                       {{ optional($existing)->selected_option === 'true' ? 'checked' : '' }}>
                                <label class="form-check-label" for="q{{ $q->id }}t">{{ get_phrase('True') }}</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input exam-answer-input" type="radio" name="answers[{{ $q->id }}]" value="false"
                                       id="q{{ $q->id }}f" data-question-id="{{ $q->id }}" data-answer-type="option"
                                       {{ optional($existing)->selected_option === 'false' ? 'checked' : '' }}>
                                <label class="form-check-label" for="q{{ $q->id }}f">{{ get_phrase('False') }}</label>
                            </div>
                        @else
                            <textarea class="form-control eForm-control exam-answer-input" name="answers[{{ $q->id }}]" rows="3"
                                      data-question-id="{{ $q->id }}" data-answer-type="text"
                                      placeholder="{{ get_phrase('Your answer...') }}">{{ optional($existing)->answer_text }}</textarea>
                        @endif
                    </div>
                </div>
                @endforeach

                <div class="text-center mt-4">
                    <button type="button" class="eBtn eBtn-primary" id="finalSubmitBtn">{{ get_phrase('Submit Exam') }}</button>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="eSection-wrap" style="position:sticky; top:16px;">
                <h6>{{ get_phrase('Questions') }}</h6>
                <div class="mb-3">
                    @foreach($questions as $qi => $q)
                        <a href="#question-block-{{ $q->id }}" class="question-nav-dot" id="nav-dot-{{ $q->id }}">{{ $qi + 1 }}</a>
                    @endforeach
                </div>
                <p class="small text-muted mb-0">
                    <i class="bi bi-info-circle"></i>
                    {{ get_phrase('Answers are saved automatically. You do not need to save manually.') }}
                </p>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('student.online_exam.timeout_submit', $submission->id) }}" id="timeoutForm" class="d-none">
    @csrf
</form>
<form method="POST" action="{{ route('student.online_exam.submit', $exam->id) }}" id="finalSubmitForm" class="d-none">
    @csrf
    <input type="hidden" name="submission_id" value="{{ $submission->id }}">
</form>

@endsection

@push('scripts')
<script>
(function () {
    "use strict";

    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var submissionId = {{ $submission->id }};
    var fullscreenRequired = @json((bool) $exam->fullscreen_required);
    var saveAnswerUrl = "{{ route('student.online_exam.save_answer', $submission->id) }}";
    var heartbeatUrl = "{{ route('student.online_exam.heartbeat', $submission->id) }}";
    var proctoringUrl = "{{ route('student.online_exam.proctoring_event', $submission->id) }}";
    var recoveryKey = 'piie.exam.recovery.v1.' + {{ (int) $submission->student_id }} + '.' + {{ (int) $exam->id }} + '.' + submissionId;
    var serverAnswers = @json($serverAnswers ?? []);
    var recoveryState = {};
    var retryAttempts = {};
    var retryTimers = {};
    var tabLeaseKey = recoveryKey + '.tab';
    var tabId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + Math.random();
    var tabConflict = false;

    // ── Navigation / copy-paste / print lockdown ───────────────────────
    document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
    ['copy', 'cut', 'paste'].forEach(function (evt) {
        document.addEventListener(evt, function (e) { e.preventDefault(); });
    });
    // Warns against an accidental close/refresh, but must never fire for
    // the exam's own submit paths — otherwise every legitimate submission
    // (manual or timed-out) would trigger a confusing "leave site?" dialog
    // right as the form is navigating away on purpose.
    var isSubmitting = false;
    var timeoutFinalizing = false;
    window.addEventListener('beforeunload', function (e) {
        if (isSubmitting) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // ── Timer, seeded from the server, not restarted on refresh ────────
    var remainingSeconds = {{ (int) $remainingSeconds }};
    var timerEl = document.getElementById('timer');

    function finalizeTimeout() {
        if (timeoutFinalizing || isSubmitting) return;
        timeoutFinalizing = true;
        clearInterval(timerInterval);
        flushPendingSaves().then(function (saved) {
            if (!saved) {
                Object.keys(dirtyQuestions).forEach(function (questionId) {
                    if (dirtyQuestions[questionId]) setStatus(questionId, 'failed');
                });
            }
            submitViaForm('timeoutForm');
        });
    }

    function renderTimer() {
        var m = Math.floor(Math.max(0, remainingSeconds) / 60);
        var s = Math.max(0, remainingSeconds) % 60;
        timerEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    renderTimer();
    var timerInterval = setInterval(function () {
        remainingSeconds--;
        renderTimer();
        if (remainingSeconds <= 0) {
            clearInterval(timerInterval);
            finalizeTimeout();
        }
    }, 1000);

    function submitViaForm(formId) {
        isSubmitting = true;
        document.getElementById(formId).submit();
    }

    // ── Heartbeat: re-syncs the timer and catches server-side expiry ──
    function heartbeat() {
        fetch(heartbeatUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.expired) {
                finalizeTimeout();
                return;
            }
            if (data.expires_at) {
                var secondsLeft = Math.round((new Date(data.expires_at) - new Date(data.server_time)) / 1000);
                if (isFinite(secondsLeft)) {
                    remainingSeconds = secondsLeft;
                }
            }
        })
        .catch(function () { /* offline — keep counting locally until reconnected */ });
    }
    setInterval(heartbeat, 20000);

    // ── Proctoring events ───────────────────────────────────────────────
    function logProctoringEvent(eventType, metadata, useBeacon) {
        var payload = {
            submission_id: submissionId,
            event_type: eventType,
            metadata: metadata || null,
        };

        if (useBeacon && navigator.sendBeacon) {
            var data = new FormData();
            data.append('_token', csrfToken);
            data.append('submission_id', submissionId);
            data.append('event_type', eventType);
            navigator.sendBeacon(proctoringUrl, data);
            return;
        }

        fetch(proctoringUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        }).catch(function () {});
    }

    // Tab-switch detection.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            logProctoringEvent('tab_hidden', null, true);
        }
    });

    // Fullscreen enforcement.
    var fullscreenWarning = document.getElementById('fullscreenWarning');

    function requestFullscreen() {
        var el = document.documentElement;
        var request = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
        if (request) {
            request.call(el).catch(function () {});
        }
    }

    if (fullscreenRequired) {
        requestFullscreen();

        document.addEventListener('fullscreenchange', function () {
            if (document.fullscreenElement) {
                fullscreenWarning.classList.add('d-none');
                logProctoringEvent('fullscreen_started');
            } else {
                fullscreenWarning.classList.remove('d-none');
                logProctoringEvent('fullscreen_exited');
            }
        });

        var returnBtn = document.getElementById('returnFullscreenBtn');
        if (returnBtn) {
            returnBtn.addEventListener('click', requestFullscreen);
        }
    }

    // ── Answer autosave ─────────────────────────────────────────────────
    // Per-question dirty tracking; radios save immediately on change (a
    // discrete action), text answers debounce while typing, and a 10-second
    // sweep catches anything still unsaved either way — matching all three
    // paths back to the one endpoint the server already trusts.
    var dirtyQuestions = {};
    var savingQuestions = {};
    var answerRevisions = {};
    var acknowledgedRevisions = {};
    var revisionConflicts = {};
    var lastWrittenRecovery = {};
    Object.keys(serverAnswers).forEach(function (id) {
        answerRevisions[id] = acknowledgedRevisions[id] = Number(serverAnswers[id].answer_revision || 0);
    });
    var debounceTimers = {};

    function setStatus(questionId, state) {
        var el = document.getElementById('save-status-' + questionId);
        if (!el) return;
        el.classList.remove('is-saving', 'is-saved', 'is-dirty', 'is-retrying', 'is-failed');
        if (state === 'saving') { el.textContent = "{{ get_phrase('Saving…') }}"; el.classList.add('is-saving'); }
        else if (state === 'saved') { el.textContent = "{{ get_phrase('Saved') }}"; el.classList.add('is-saved'); }
        else if (state === 'dirty') { el.textContent = "{{ get_phrase('Unsaved') }}"; el.classList.add('is-dirty'); }
        else if (state === 'retrying') { el.textContent = "{{ get_phrase('Offline / retrying') }}"; el.classList.add('is-retrying'); }
        else if (state === 'failed') { el.textContent = "{{ get_phrase('Save failed') }}"; el.classList.add('is-failed'); }
        else { el.textContent = ''; }
    }

    function setOverallStatus(state) {
        var el = document.getElementById('overallSaveStatus');
        if (!el) return;
        el.className = 'save-status mt-1';
        if (state === 'offline') { el.textContent = "{{ get_phrase('Offline — answers are kept on this device') }}"; el.classList.add('is-failed'); }
        else if (state === 'connected') { el.textContent = "{{ get_phrase('Connected') }}"; el.classList.add('is-saved'); }
        else if (state === 'retrying') { el.textContent = "{{ get_phrase('Retrying unsaved answers') }}"; el.classList.add('is-retrying'); }
        else if (state === 'conflict') { el.textContent = "{{ get_phrase('Another exam tab is open — keep only one tab active') }}"; el.classList.add('is-failed'); }
    }

    function readRecovery() {
        try {
            var raw = window.localStorage.getItem(recoveryKey);
            recoveryState = raw ? JSON.parse(raw) : {};
            if (!recoveryState || typeof recoveryState !== 'object') recoveryState = {};
            lastWrittenRecovery = JSON.parse(JSON.stringify(recoveryState));
        } catch (e) { recoveryState = {}; }
    }

    function writeRecovery() {
        try {
            var stored = JSON.parse(window.localStorage.getItem(recoveryKey) || '{}');
            if (!stored || typeof stored !== 'object') stored = {};
            Object.keys(lastWrittenRecovery).forEach(function (id) {
                // An acknowledgement in this tab must not erase another tab's draft.
                if (!recoveryState[id] && JSON.stringify(stored[id]) === JSON.stringify(lastWrittenRecovery[id])) {
                    delete stored[id];
                }
            });
            Object.keys(recoveryState).forEach(function (id) {
                if (JSON.stringify(recoveryState[id]) !== JSON.stringify(lastWrittenRecovery[id])) stored[id] = recoveryState[id];
            });
            if (Object.keys(stored).length === 0) window.localStorage.removeItem(recoveryKey);
            else window.localStorage.setItem(recoveryKey, JSON.stringify(stored));
            lastWrittenRecovery = JSON.parse(JSON.stringify(recoveryState));
        } catch (e) { /* private browsing or quota limits: server autosave still applies */ }
    }

    function claimTabLease() {
        try {
            var nowMs = Date.now();
            var current = JSON.parse(window.localStorage.getItem(tabLeaseKey) || 'null');
            if (current && current.tabId !== tabId && nowMs - (current.lastSeen || 0) < 15000) {
                tabConflict = true;
                setOverallStatus('conflict');
            }
            window.localStorage.setItem(tabLeaseKey, JSON.stringify({ tabId: tabId, lastSeen: nowMs }));
        } catch (e) {}
    }

    function refreshTabLease() {
        try { window.localStorage.setItem(tabLeaseKey, JSON.stringify({ tabId: tabId, lastSeen: Date.now() })); } catch (e) {}
    }

    claimTabLease();
    setInterval(refreshTabLease, 5000);
    window.addEventListener('storage', function (event) {
        if (event.key === tabLeaseKey && event.newValue) {
            try {
                var other = JSON.parse(event.newValue);
                if (other.tabId !== tabId) {
                    tabConflict = true;
                    setOverallStatus('conflict');
                }
            } catch (e) {}
        }
    });
    window.addEventListener('beforeunload', function () {
        try {
            var current = JSON.parse(window.localStorage.getItem(tabLeaseKey) || 'null');
            if (current && current.tabId === tabId) window.localStorage.removeItem(tabLeaseKey);
        } catch (e) {}
    });

    function rememberLocalAnswer(questionId, value) {
        var previous = recoveryState[questionId];
        recoveryState[questionId] = {
            selected_option: value.selected_option,
            answer_text: value.answer_text,
            answer_payload: value.answer_payload || null,
            revision: answerRevisions[questionId] || 0,
            acknowledged_revision: acknowledgedRevisions[questionId] || 0,
            server_updated_at: previous && Object.prototype.hasOwnProperty.call(previous, 'server_updated_at')
                ? previous.server_updated_at
                : ((serverAnswers[questionId] || {}).updated_at || null),
        };
        writeRecovery();
    }

    function applyRecoveredValue(questionId, value) {
        var checked = document.querySelectorAll('input.exam-answer-input[data-question-id="' + questionId + '"]');
        checked.forEach(function (input) {
            if (input.dataset.answerType === 'multiple_select') {
                input.checked = (value.answer_payload && Array.isArray(value.answer_payload.selected_option_ids))
                    ? value.answer_payload.selected_option_ids.indexOf(input.value) !== -1 : false;
            } else if (input.dataset.answerType === 'fill_blank') {
                input.value = value.answer_payload && value.answer_payload.blanks ? (value.answer_payload.blanks[input.dataset.blankId] || '') : '';
            } else if (input.dataset.answerType === 'matching') {
                input.value = value.answer_payload && value.answer_payload.pairs ? (value.answer_payload.pairs[input.dataset.leftId] || '') : '';
            } else input.checked = input.value === value.selected_option;
        });
        var textarea = document.querySelector('textarea.exam-answer-input[data-question-id="' + questionId + '"]');
        if (textarea) textarea.value = value.answer_text || '';
        var numeric = document.querySelector('input.exam-answer-input[data-answer-type="numeric"][data-question-id="' + questionId + '"]');
        if (numeric && value.answer_payload) numeric.value = value.answer_payload.value ?? '';
        var ordering = document.querySelector('.ordering-list[data-question-id="' + questionId + '"]');
        if (ordering && value.answer_payload && Array.isArray(value.answer_payload.ordered_ids)) value.answer_payload.ordered_ids.forEach(function(id){ var item=ordering.querySelector('[data-order-id="'+id+'"]'); if(item) ordering.appendChild(item); });
    }

    function recoverLocalAnswers() {
        readRecovery();
        Object.keys(recoveryState).forEach(function (questionId) {
            var local = recoveryState[questionId];
            var server = serverAnswers[questionId] || {};
            if (!local) return;
            applyRecoveredValue(questionId, local);
            answerRevisions[questionId] = Math.max(answerRevisions[questionId] || 0, local.revision || 1);
            dirtyQuestions[questionId] = true;
            // Old timestamp-only drafts require explicit reconciliation as well.
            if (!Number.isInteger(local.acknowledged_revision)
                || Number(server.answer_revision || 0) > local.acknowledged_revision) {
                showRevisionConflict(questionId, server);
                return;
            }
            setStatus(questionId, 'retrying');
            markNavAnswered(questionId);
        });
        writeRecovery();
        Object.keys(dirtyQuestions).forEach(function (questionId) { saveQuestion(questionId); });
    }

    function currentValueFor(questionId) {
        var fillBlanks = document.querySelectorAll('input.exam-answer-input[data-answer-type="fill_blank"][data-question-id="' + questionId + '"]');
        if (fillBlanks.length) {
            var blankValues = {};
            fillBlanks.forEach(function (input) { if (input.value !== '') blankValues[input.dataset.blankId] = input.value; });
            return { selected_option: null, answer_text: null, answer_payload: Object.keys(blankValues).length ? { type: 'fill_blank', blanks: blankValues } : null };
        }
        var multiple = document.querySelectorAll('input.exam-answer-input[data-answer-type="multiple_select"][data-question-id="' + questionId + '"]:checked');
        if (multiple.length) return { selected_option: null, answer_text: null, answer_payload: { type: 'multiple_select', selected_option_ids: Array.from(multiple).map(function (input) { return input.value; }) } };
        var numeric = document.querySelector('input.exam-answer-input[data-answer-type="numeric"][data-question-id="' + questionId + '"]');
        if (numeric) return numeric.value.trim() === '' ? { selected_option: null, answer_text: null, answer_payload: null } : { selected_option: null, answer_text: null, answer_payload: { type: 'numeric', value: numeric.value.trim() } };
        var matching = document.querySelectorAll('select.exam-answer-input[data-answer-type="matching"][data-question-id="' + questionId + '"]');
        if (matching.length) { var pairs={}; matching.forEach(function(s){ if(s.value) pairs[s.dataset.leftId]=s.value; }); return {selected_option:null,answer_text:null,answer_payload:Object.keys(pairs).length?{type:'matching',pairs:pairs}:null}; }
        var ordering = document.querySelector('.ordering-list[data-question-id="' + questionId + '"]');
        if (ordering) return {selected_option:null,answer_text:null,answer_payload:{type:'ordering',ordered_ids:Array.from(ordering.querySelectorAll('[data-order-id]')).map(function(i){return i.dataset.orderId;})}};
        var checked = document.querySelector('input.exam-answer-input[data-question-id="' + questionId + '"]:checked');
        if (checked) {
            return { selected_option: checked.value, answer_text: null, answer_payload: null };
        }
        var textarea = document.querySelector('textarea.exam-answer-input[data-question-id="' + questionId + '"]');
        if (textarea) {
            return { selected_option: null, answer_text: textarea.value, answer_payload: null };
        }
        return null;
    }

    function markNavAnswered(questionId) {
        var dot = document.getElementById('nav-dot-' + questionId);
        if (dot) dot.classList.add('is-answered');
    }

    function saveQuestion(questionId) {
        if (revisionConflicts[questionId]) return;
        var value = currentValueFor(questionId);
        if (!value) return;

        var requestRevision = answerRevisions[questionId] || 0;
        if (retryTimers[questionId]) return;
        if (savingQuestions[questionId]) {
            dirtyQuestions[questionId] = true;
            setStatus(questionId, 'retrying');
            return;
        }

        savingQuestions[questionId] = true;
        setStatus(questionId, 'saving');
        rememberLocalAnswer(questionId, value);

        fetch(saveAnswerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                submission_id: submissionId,
                question_id: questionId,
                answer_revision: requestRevision,
                selected_option: value.selected_option,
                answer_text: value.answer_text,
                answer_payload: value.answer_payload,
            }),
        })
        .then(async function (r) {
            var data = await r.json();
            savingQuestions[questionId] = false;
            if (r.status === 409) {
                showRevisionConflict(questionId, data);
                return;
            }
            if (r.status === 422) {
                // Expired or submission no longer active — stop trying and
                // let the timer/heartbeat path handle finalising.
                dirtyQuestions[questionId] = true;
                setStatus(questionId, 'failed');
                return;
            }
            if (!r.ok) {
                dirtyQuestions[questionId] = true;
                scheduleRetry(questionId);
                return;
            }
            acknowledgedRevisions[questionId] = Number(data.answer_revision);
            serverAnswers[questionId] = {
                selected_option: value.selected_option,
                answer_text: value.answer_text,
                answer_payload: value.answer_payload,
                answer_revision: Number(data.answer_revision),
                updated_at: data.answer_updated_at,
            };
            if ((answerRevisions[questionId] || 0) !== requestRevision) {
                dirtyQuestions[questionId] = true;
                setStatus(questionId, 'retrying');
                saveQuestion(questionId);
                return;
            }
            dirtyQuestions[questionId] = false;
            retryAttempts[questionId] = 0;
            delete recoveryState[questionId];
            writeRecovery();
            setStatus(questionId, 'saved');
            markNavAnswered(questionId);
        })
        .catch(function () {
            savingQuestions[questionId] = false;
            dirtyQuestions[questionId] = true;
            scheduleRetry(questionId);
        });
    }

    function showRevisionConflict(questionId, server) {
        serverAnswers[questionId] = server;
        revisionConflicts[questionId] = true;
        dirtyQuestions[questionId] = true;
        setStatus(questionId, 'failed');
        var container = document.getElementById('save-status-' + questionId);
        if (!container) return;
        container.textContent = 'Answer conflict. Your draft is kept on this device. ';
        function choice(label, keepLocal) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-secondary ms-1';
            button.textContent = label;
            button.addEventListener('click', function () {
                var revision = Number(server.answer_revision || 0);
                acknowledgedRevisions[questionId] = revision;
                answerRevisions[questionId] = Math.max(answerRevisions[questionId] || 0, revision);
                delete revisionConflicts[questionId];
                if (keepLocal) {
                    if (answerRevisions[questionId] >= 4294967295) {
                        showRevisionConflict(questionId, server);
                        return;
                    }
                    answerRevisions[questionId]++;
                    rememberLocalAnswer(questionId, currentValueFor(questionId));
                    saveQuestion(questionId);
                } else {
                    applyRecoveredValue(questionId, server);
                    answerRevisions[questionId] = revision;
                    dirtyQuestions[questionId] = false;
                    delete recoveryState[questionId];
                    writeRecovery();
                    setStatus(questionId, 'saved');
                }
            });
            container.appendChild(button);
        }
        choice('Use server answer', false);
        choice('Save my draft', true);
    }

    function scheduleRetry(questionId) {
        if (revisionConflicts[questionId]) return;
        if (retryTimers[questionId]) return;
        var attempt = retryAttempts[questionId] || 0;
        var delay = Math.min(30000, 1000 * Math.pow(2, attempt));
        retryAttempts[questionId] = Math.min(attempt + 1, 5);
        setStatus(questionId, 'retrying');
        setOverallStatus('retrying');
        retryTimers[questionId] = setTimeout(function () {
            delete retryTimers[questionId];
            if (dirtyQuestions[questionId] && navigator.onLine !== false) saveQuestion(questionId);
        }, delay);
    }

    document.querySelectorAll('.exam-answer-input').forEach(function (input) {
        var questionId = input.getAttribute('data-question-id');

        if (input.getAttribute('data-answer-type') === 'option') {
            input.addEventListener('change', function () {
                answerRevisions[questionId] = (answerRevisions[questionId] || 0) + 1;
                dirtyQuestions[questionId] = true;
                rememberLocalAnswer(questionId, currentValueFor(questionId));
                setStatus(questionId, 'dirty');
                if (revisionConflicts[questionId]) showRevisionConflict(questionId, serverAnswers[questionId]);
                saveQuestion(questionId);
            });

            if (input.checked) {
                markNavAnswered(questionId);
            }
        } else {
            input.addEventListener('input', function () {
                answerRevisions[questionId] = (answerRevisions[questionId] || 0) + 1;
                dirtyQuestions[questionId] = true;
                rememberLocalAnswer(questionId, currentValueFor(questionId));
                setStatus(questionId, 'dirty');
                if (revisionConflicts[questionId]) showRevisionConflict(questionId, serverAnswers[questionId]);

                clearTimeout(debounceTimers[questionId]);
                debounceTimers[questionId] = setTimeout(function () {
                    saveQuestion(questionId);
                }, 1500);
            });

            if (input.value.trim() !== '') {
                markNavAnswered(questionId);
            }
        }
    });

    document.querySelectorAll('select[data-answer-type="matching"]').forEach(function(select){
        select.addEventListener('change', function(){ var q=select.dataset.questionId; answerRevisions[q]=(answerRevisions[q]||0)+1; dirtyQuestions[q]=true; setStatus(q,'dirty'); saveQuestion(q); });
    });
    document.querySelectorAll('.ordering-list').forEach(function(list){ list.addEventListener('click', function(e){ var b=e.target.closest('button'); if(!b)return; var item=b.closest('[data-order-id]'); if(b.classList.contains('order-up')&&item.previousElementSibling) list.insertBefore(item,item.previousElementSibling); if(b.classList.contains('order-down')&&item.nextElementSibling) list.insertBefore(item.nextElementSibling,item); var q=list.dataset.questionId; answerRevisions[q]=(answerRevisions[q]||0)+1; dirtyQuestions[q]=true; setStatus(q,'dirty'); saveQuestion(q); }); });

    recoverLocalAnswers();

    window.addEventListener('offline', function () { setOverallStatus('offline'); });
    window.addEventListener('online', function () {
        setOverallStatus('connected');
        Object.keys(dirtyQuestions).forEach(function (questionId) {
            if (dirtyQuestions[questionId]) scheduleRetry(questionId);
        });
    });
    if (tabConflict) setOverallStatus('conflict');
    else setOverallStatus(navigator.onLine === false ? 'offline' : 'connected');

    // The 10-second safety-net sweep the brief asked for explicitly —
    // catches anything the change/debounce paths above haven't gotten to
    // yet (e.g. a save that failed and needs retrying).
    setInterval(function () {
        Object.keys(dirtyQuestions).forEach(function (questionId) {
            if (dirtyQuestions[questionId]) {
                saveQuestion(questionId);
            }
        });
    }, 10000);

    function flushPendingSaves() {
        Object.keys(dirtyQuestions).forEach(function (questionId) {
            if (dirtyQuestions[questionId]) saveQuestion(questionId);
        });
        return new Promise(function (resolve) {
            var deadline = Date.now() + 5000;
            (function waitForSaves() {
                var pending = Object.keys(dirtyQuestions).some(function (id) {
                    return dirtyQuestions[id] || savingQuestions[id];
                });
                if (!pending || Date.now() >= deadline) { resolve(!pending); return; }
                setTimeout(waitForSaves, 100);
            })();
        });
    }

    // ── Final submit ─────────────────────────────────────────────────────
    document.getElementById('finalSubmitBtn').addEventListener('click', function () {
        var unansweredCount = document.querySelectorAll('.question-nav-dot:not(.is-answered)').length;

        var confirmMsg = unansweredCount > 0
            ? unansweredCount + " {{ get_phrase('question(s) are still unanswered.') }} {{ get_phrase('Submit anyway? You cannot change answers after submission.') }}"
            : "{{ get_phrase('Submit exam? You cannot change answers after submission.') }}";

        if (!window.confirm(confirmMsg)) {
            return;
        }

        flushPendingSaves().then(function (saved) {
            if (!saved) {
                window.alert("{{ get_phrase('Some answers have not been saved. Please reconnect and try again.') }}");
                return;
            }
            clearInterval(timerInterval);
            submitViaForm('finalSubmitForm');
        });
    });
})();
</script>
@endpush
