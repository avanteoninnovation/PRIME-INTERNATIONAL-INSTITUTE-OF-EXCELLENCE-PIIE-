@extends('admin.navigation')
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
                @endphp
                <div class="card mb-3" data-question-id="{{ $q->id }}" id="question-block-{{ $q->id }}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <p class="mb-2"><strong>Q{{ $qi + 1 }}.</strong> {{ $q->question }}
                               <span class="badge bg-secondary ms-2">{{ $q->marks }} {{ get_phrase('mark(s)') }}</span></p>
                            <span class="save-status" id="save-status-{{ $q->id }}"></span>
                        </div>

                        @if($q->type === 'mcq')
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
    window.addEventListener('beforeunload', function (e) {
        if (isSubmitting) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // ── Timer, seeded from the server, not restarted on refresh ────────
    var remainingSeconds = {{ (int) $remainingSeconds }};
    var timerEl = document.getElementById('timer');

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
            submitViaForm('timeoutForm');
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
                submitViaForm('timeoutForm');
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
    var debounceTimers = {};

    function setStatus(questionId, state) {
        var el = document.getElementById('save-status-' + questionId);
        if (!el) return;
        el.classList.remove('is-saving', 'is-saved', 'is-dirty');
        if (state === 'saving') { el.textContent = "{{ get_phrase('Saving…') }}"; el.classList.add('is-saving'); }
        else if (state === 'saved') { el.textContent = "{{ get_phrase('Saved') }}"; el.classList.add('is-saved'); }
        else if (state === 'dirty') { el.textContent = "{{ get_phrase('Unsaved') }}"; el.classList.add('is-dirty'); }
        else { el.textContent = ''; }
    }

    function currentValueFor(questionId) {
        var checked = document.querySelector('input.exam-answer-input[data-question-id="' + questionId + '"]:checked');
        if (checked) {
            return { selected_option: checked.value, answer_text: null };
        }
        var textarea = document.querySelector('textarea.exam-answer-input[data-question-id="' + questionId + '"]');
        if (textarea) {
            return { selected_option: null, answer_text: textarea.value };
        }
        return null;
    }

    function markNavAnswered(questionId) {
        var dot = document.getElementById('nav-dot-' + questionId);
        if (dot) dot.classList.add('is-answered');
    }

    function saveQuestion(questionId) {
        if (savingQuestions[questionId]) {
            return; // a save is already in flight; the periodic sweep will retry if it's still dirty after
        }

        var value = currentValueFor(questionId);
        if (!value) return;

        savingQuestions[questionId] = true;
        setStatus(questionId, 'saving');

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
                selected_option: value.selected_option,
                answer_text: value.answer_text,
            }),
        })
        .then(function (r) {
            savingQuestions[questionId] = false;
            if (r.status === 422) {
                // Expired or submission no longer active — stop trying and
                // let the timer/heartbeat path handle finalising.
                dirtyQuestions[questionId] = false;
                setStatus(questionId, null);
                return;
            }
            if (!r.ok) {
                dirtyQuestions[questionId] = true;
                setStatus(questionId, 'dirty');
                return;
            }
            dirtyQuestions[questionId] = false;
            setStatus(questionId, 'saved');
            markNavAnswered(questionId);
        })
        .catch(function () {
            savingQuestions[questionId] = false;
            dirtyQuestions[questionId] = true;
            setStatus(questionId, 'dirty');
        });
    }

    document.querySelectorAll('.exam-answer-input').forEach(function (input) {
        var questionId = input.getAttribute('data-question-id');

        if (input.getAttribute('data-answer-type') === 'option') {
            input.addEventListener('change', function () {
                dirtyQuestions[questionId] = true;
                setStatus(questionId, 'dirty');
                saveQuestion(questionId);
            });

            if (input.checked) {
                markNavAnswered(questionId);
            }
        } else {
            input.addEventListener('input', function () {
                dirtyQuestions[questionId] = true;
                setStatus(questionId, 'dirty');

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

    // ── Final submit ─────────────────────────────────────────────────────
    document.getElementById('finalSubmitBtn').addEventListener('click', function () {
        var unansweredCount = document.querySelectorAll('.question-nav-dot:not(.is-answered)').length;

        var confirmMsg = unansweredCount > 0
            ? unansweredCount + " {{ get_phrase('question(s) are still unanswered.') }} {{ get_phrase('Submit anyway? You cannot change answers after submission.') }}"
            : "{{ get_phrase('Submit exam? You cannot change answers after submission.') }}";

        if (!window.confirm(confirmMsg)) {
            return;
        }

        clearInterval(timerInterval);
        submitViaForm('finalSubmitForm');
    });
})();
</script>
@endpush
