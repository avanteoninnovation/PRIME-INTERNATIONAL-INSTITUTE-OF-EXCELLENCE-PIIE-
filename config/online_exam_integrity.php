<?php

/*
 * EXAMINATION INTEGRITY — WHAT IS ENFORCED, AND HOW MUCH.
 *
 * ── THE HONEST FRAMING, FIRST ────────────────────────────────────────────────
 *
 * None of this makes cheating impossible and nothing here claims it does. A web page
 * cannot stop a student minimising a window, switching to another application,
 * photographing the screen with a phone, opening the same paper on a second device, or
 * using the operating system's own facilities. Those are OS and hardware behaviours.
 * A student with developer tools can read anything the browser is willing to show
 * them.
 *
 * What this configuration DOES buy: it closes the casual routes — selecting a question
 * with the mouse and copying it from the browser's own menu — records attempts, and
 * gives an examiner something to review. It is DETERRENCE AND EVIDENCE, not a security
 * boundary. The attempt page says so to the student in as many words, because a
 * control that implies a guarantee it cannot keep discredits everything else the page
 * tells them.
 *
 * Genuine lockdown needs a dedicated secure browser or a desktop application that
 * owns the screen. That is a separate programme of work and is deliberately not faked
 * here.
 *
 * ── WHY THESE ARE SETTINGS AND NOT CONSTANTS ────────────────────────────────
 *
 * Three reasons, each of which has already cost somebody something:
 *
 *   1. An institution's policy differs. Some invigilate open-book; some do not.
 *   2. A control that cannot be relaxed cannot be used for a student who NEEDS it
 *      relaxed. That is the accommodation column, and a threshold nobody can set is a
 *      threshold that is wrong for somebody.
 *   3. A value buried in a script can only be changed by a developer, which means the
 *      first person who needs it changed in a hurry cannot change it.
 */

return [

    /*
     * THE MASTER SWITCH.
     *
     * Off means the attempt page behaves like every other page in PIIE: no clipboard
     * restriction, no context-menu restriction, no integrity events. Default ON, because
     * an examination that silently stopped protecting itself is the dangerous default.
     */
    'enabled' => env('PIIE_EXAM_INTEGRITY_ENABLED', true),

    'restricted_interaction' => [
        /*
         * Refuse copy, cut, paste and drag INSIDE the protected exam area.
         *
         * This is the control that was reported as not working. It did work for the
         * answer fields and did NOT work for the question statement, because the module
         * decided what to protect by listing element TYPES and a question is a `<p>`.
         * The fix is a containment test against the root the page marks as protected;
         * this switch turns that behaviour on.
         */
        'block_clipboard' => env('PIIE_EXAM_INTEGRITY_CLIPBOARD', true),

        'block_context_menu' => env('PIIE_EXAM_INTEGRITY_CONTEXT_MENU', true),

        /*
         * Refuse the shortcuts that take the paper out of the page: F5, Ctrl/Cmd+R,
         * Ctrl/Cmd+P, Ctrl/Cmd+W, and the back/forward accelerators.
         *
         * What is NOT in here, and cannot be: Ctrl+T, Ctrl+N, Ctrl+Tab, Alt+Tab. Page
         * script cannot intercept those, and code that pretended to would simply break a
         * student's keyboard.
         */
        'block_navigation_shortcuts' => env('PIIE_EXAM_INTEGRITY_NAVIGATION', true),
    ],

    /*
     * WARNINGS AND THRESHOLDS.
     *
     * `focus_loss_grace_ms` absorbs the `blur` + `visibilitychange` pair that a single
     * Alt+Tab produces, so one interruption is one record rather than two.
     *
     * `warning_threshold` is how many recorded incidents produce a visible notice to
     * the student before it is suppressed. 1 means "tell them the first time, then
     * stop repeating it" — repeating a warning every time is how a warning stops being
     * read.
     *
     * `persistent_banner_after` is where the page stops using a transient message and
     * shows a banner that stays until the paper is submitted. A student who has lost
     * focus repeatedly needs to be able to see that it is being recorded, and the
     * institution needs that to be true without anyone asserting it.
     */
    'warning_threshold' => (int) env('PIIE_EXAM_INTEGRITY_WARNING_THRESHOLD', 1),
    'persistent_banner_after' => (int) env('PIIE_EXAM_INTEGRITY_BANNER_AFTER', 3),
    'focus_loss_grace_ms' => (int) env('PIIE_EXAM_INTEGRITY_FOCUS_GRACE_MS', 1500),

    /*
     * APPROVED ACCOMMODATIONS — `online_exams.integrity_accommodation`.
     *
     * The COLUMN values are named here so the policy is in one place. They are read by
     * `OnlineExam::integritySettings()` and rendered into the attempt page.
     *
     * `null`  — full default restrictions. What every existing exam has.
     * `clipboard_exempt` — copy/paste and the context menu are permitted inside the
     *                   protected area, because an approved assistive tool or a screen
     *                   reader needs them. Focus, navigation and PRINT remain
     *                   restricted and every incident is still RECORDED, so the
     *                   examination is not silently unmonitored.
     * `read_only_enforced` — every clipboard and navigation control stays on, but the
     *                   student is not warned for focus loss alone; the record is kept.
     *                   For a student whose adjustment involves a second window or a
     *                   reader that moves focus legitimately.
     * `off`   — no restrictions. Recorded on the attempt, so an examiner can always
     *           see that this paper was unmonitored and why.
     *
     * WHAT AN ACCOMMODATION NEVER DOES:
     *   • silently stop recording events;
     *   • change how an answer is marked;
     *   • terminate, submit or fail the attempt.
     */
    'accommodations' => [
        'clipboard_exempt' => [
            'block_clipboard' => false,
            'block_context_menu' => false,
            'block_navigation_shortcuts' => true,
            'warn_on_focus_loss' => true,
        ],
        'read_only_enforced' => [
            'block_clipboard' => true,
            'block_context_menu' => true,
            'block_navigation_shortcuts' => true,
            'warn_on_focus_loss' => false,
        ],
        'off' => [
            'block_clipboard' => false,
            'block_context_menu' => false,
            'block_navigation_shortcuts' => false,
            'warn_on_focus_loss' => false,
        ],
    ],

    /*
     * WHAT IS NOT POLICY, AND IS NOT HERE.
     *
     * Nothing in this file can end a student's attempt, deduct a mark, or fail a paper.
     * Integrity events are evidence for a human to act on deliberately. Treating an
     * automated consequence for leaving a tab as a verdict is exactly the failure mode
     * a proctoring log is supposed to avoid: it punishes a dropped connection, a
     * phone call and an operating-system notification identically.
     */
];