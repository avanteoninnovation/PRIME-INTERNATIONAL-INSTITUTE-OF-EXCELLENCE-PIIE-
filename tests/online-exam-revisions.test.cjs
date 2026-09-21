// Executes the actual Blade autosave functions with deterministic browser/network doubles.
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync('resources/views/student/online_exam/take.blade.php', 'utf8');
const start = source.indexOf('    var dirtyQuestions = {}');
const end = source.indexOf("    document.querySelectorAll('.exam-answer-input')", start);
const tick = () => new Promise(resolve => setImmediate(resolve));

function browser(server = {}) {
    const requests = [], storage = new Map(), elements = new Map();
    const textarea = { value: 'draft' };
    const element = () => ({ textContent: '', classList: { add() {}, remove() {} }, children: [],
        appendChild(child) { this.children.push(child); }, addEventListener(_, fn) { this.click = fn; } });
    const ctx = {
        serverAnswers: server, recoveryKey: 'student.exam.submission', recoveryState: {},
        tabLeaseKey: 'lease', tabId: 'tab-a', tabConflict: false,
        retryTimers: {}, retryAttempts: {}, submissionId: 10, csrfToken: 'csrf', saveAnswerUrl: '/save',
        navigator: { onLine: true }, setInterval() {}, setTimeout() {},
        window: { addEventListener() {}, localStorage: {
            getItem(k) { return storage.get(k) || null; },
            setItem(k, v) { storage.set(k, v); }, removeItem(k) { storage.delete(k); },
        } },
        document: {
            getElementById(id) { if (!elements.has(id)) elements.set(id, element()); return elements.get(id); },
            createElement: element, querySelectorAll() { return []; },
            querySelector(s) { return s.startsWith('textarea') ? textarea : null; },
        },
        fetch(_, options) { return new Promise(resolve => requests.push({ body: JSON.parse(options.body), resolve })); },
    };
    vm.createContext(ctx);
    vm.runInContext(source.slice(start, end), ctx);
    return { ctx, requests, textarea, storage, elements,
        respond(i, status, data) { requests[i].resolve({ status, ok: status === 200, json: async () => data }); },
    };
}

test('edits use server revision; a delayed acknowledgement preserves and sends the current draft', async () => {
    const b = browser({ 1: { answer_revision: 4 } });
    const c = b.ctx;
    c.answerRevisions[1]++;
    c.dirtyQuestions[1] = true;
    c.saveQuestion(1);
    assert.equal(b.requests[0].body.answer_revision, 5);
    c.answerRevisions[1]++;
    b.textarea.value = 'new draft';
    c.rememberLocalAnswer(1, c.currentValueFor(1));
    b.respond(0, 200, { answer_revision: 5, answer_updated_at: 'server' });
    await tick();
    assert.equal(c.acknowledgedRevisions[1], 5);
    assert.equal(c.dirtyQuestions[1], true);
    assert.equal(c.recoveryState[1].revision, 6);
    assert.equal(b.requests[1].body.answer_revision, 6);
    b.respond(1, 200, { answer_revision: 6 });
    await tick();
    assert.equal(c.dirtyQuestions[1], false);
    assert.equal(c.recoveryState[1], undefined);
});

test('tab A revision five rejected after tab B six stays unsaved and never retries automatically', async () => {
    const b = browser({ 1: { answer_revision: 4 } });
    b.ctx.answerRevisions[1] = 5;
    b.ctx.saveQuestion(1);
    b.respond(0, 409, { status: 'stale', answer_revision: 6, answer_text: 'tab B' });
    await tick();
    assert.equal(b.ctx.dirtyQuestions[1], true);
    assert.equal(b.ctx.recoveryState[1].answer_text, 'draft');
    b.ctx.saveQuestion(1);
    assert.equal(b.requests.length, 1);
    const buttons = b.elements.get('save-status-1').children;
    buttons[1].click();
    assert.equal(b.requests[1].body.answer_revision, 7);
});

test('equal-revision conflict allows explicit server choice without writing', async () => {
    const b = browser();
    b.ctx.answerRevisions[1] = 2;
    b.ctx.saveQuestion(1);
    b.respond(0, 409, { status: 'conflict', answer_revision: 2, answer_text: 'server answer' });
    await tick();
    b.elements.get('save-status-1').children[0].click();
    assert.equal(b.textarea.value, 'server answer');
    assert.equal(b.ctx.dirtyQuestions[1], false);
    assert.equal(b.requests.length, 1);
});

test('recovery uses acknowledged revision, not timestamp, and preserves legacy/conflicting drafts', () => {
    const b = browser({ 1: { answer_revision: 4, updated_at: 'different timestamp' } });
    b.storage.set(b.ctx.recoveryKey, JSON.stringify({ 1: { revision: 5, acknowledged_revision: 4, answer_text: 'recover', server_updated_at: 'old' } }));
    b.ctx.recoverLocalAnswers();
    assert.equal(b.requests[0].body.answer_revision, 5);
    assert.equal(b.textarea.value, 'recover');
    for (const local of [{ revision: 5, acknowledged_revision: 3 }, { revision: 1, server_updated_at: 'old' }]) {
        const conflict = browser({ 1: { answer_revision: 4 } });
        conflict.storage.set(conflict.ctx.recoveryKey, JSON.stringify({ 1: { ...local, answer_text: 'kept' } }));
        conflict.ctx.recoverLocalAnswers();
        assert.equal(conflict.requests.length, 0);
        assert.equal(conflict.ctx.revisionConflicts[1], true);
        assert.equal(conflict.ctx.recoveryState[1].answer_text, 'kept');
    }
});

test('finalized or expired rejection retains recovery and never acknowledges the answer', async () => {
    const b = browser();
    b.ctx.answerRevisions[1] = 1;
    b.ctx.saveQuestion(1);
    b.respond(0, 422, { message: 'Submission is not active' });
    await tick();
    assert.equal(b.ctx.dirtyQuestions[1], true);
    assert.equal(b.ctx.recoveryState[1].revision, 1);
    assert.equal(b.ctx.acknowledgedRevisions[1], undefined);
});

test('pending save flush waits for acknowledgement', async () => {
    const b = browser();
    let callbacks = [];
    b.ctx.setTimeout = fn => callbacks.push(fn);
    const flush = source.slice(source.indexOf('    function flushPendingSaves()'), source.indexOf("    document.getElementById('finalSubmitBtn')"));
    vm.runInContext(flush, b.ctx);
    b.ctx.answerRevisions[1] = 1;
    b.ctx.dirtyQuestions[1] = true;
    const flushed = b.ctx.flushPendingSaves();
    b.respond(0, 200, { answer_revision: 1 });
    await tick();
    callbacks.shift()();
    assert.equal(await flushed, true);
});

test('an acknowledgement cannot remove a different draft written by another tab', async () => {
    const b = browser();
    b.ctx.answerRevisions[1] = 5;
    b.ctx.saveQuestion(1);
    b.storage.set(b.ctx.recoveryKey, JSON.stringify({ 1: { revision: 6, acknowledged_revision: 4, answer_text: 'tab B pending' } }));
    b.respond(0, 200, { answer_revision: 5 });
    await tick();
    assert.equal(JSON.parse(b.storage.get(b.ctx.recoveryKey))[1].revision, 6);
});
