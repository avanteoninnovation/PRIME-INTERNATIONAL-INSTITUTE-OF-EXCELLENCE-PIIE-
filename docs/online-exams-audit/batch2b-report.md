# Online Exams Phase 1 — Batch 2B

Batch 2B only. No commit, deployment, live migration, historical migration edit, role change, or next-batch implementation.

## A. Schema before Batch 2B

Reviewed `batch2a-schema-review.md` and verified the current database using read-only `SHOW CREATE TABLE online_exam_answers`:

| Field / constraint | Actual schema |
| --- | --- |
| id | unsigned bigint, NOT NULL, auto-increment, primary key |
| submission_id / question_id | unsigned bigint, NOT NULL, no default |
| selected_option | varchar(10), nullable, default NULL |
| answer_text | longtext, nullable, default NULL |
| awarded_marks | decimal(8,2), nullable, default NULL |
| is_correct | tinyint(1), nullable, default NULL |
| marked_by | unsigned bigint, nullable, default NULL |
| marked_at | datetime, nullable, default NULL |
| teacher_comment | text, nullable, default NULL |
| created_at / updated_at | timestamp, nullable, default NULL |
| unique | `oea_submission_question_unique (submission_id, question_id)` |
| secondary indexes | individual submission_id, question_id, marked_by indexes |
| foreign keys | none declared on this table |
| storage | InnoDB, utf8mb4_unicode_ci |

No revision existed. Existing pair lookup/uniqueness is sufficient; adding a revision index would not help this access path.

## B. New migration

`2026_09_19_000004_add_answer_revision_to_online_exam_answers.php` adds one `unsignedInteger('answer_revision')->default(0)` (non-null). Existing rows receive 0. No data or other column/index changes. Down removes only answer_revision. The migration has **not** been applied to the normal PIIE database. Deploy the migration before enabling the new save protocol; old browser pages must refresh because revision is required.

## C. Revision protocol

Scope: submission + question. Required integer range 0–4294967295. An absent row accepts any valid first revision, including 0. A higher revision replaces the answer; gaps are allowed. Existing revision 0 remains a real stored version, not an unconditional overwrite permission. Server timestamps are metadata only.

Success: HTTP 200, status `success` or `idempotent`, submission_id, question_id, answer_revision, answer_updated_at, server_time and expires_at. Client timestamps never order writes.

## D. Atomic save

The existing transaction takes `FOR UPDATE` on the submission, rechecks owner/school/exam school, active status and expiry, checks question membership, then locking-reads the answer. Comparison and write remain in the same transaction. The parent row exists before the first answer and serializes competing first writes. The unique answer-pair constraint remains. Finalization uses the same submission lock. Real MariaDB verification remains outstanding.

## E. Equal revisions

Strict equality of both stored selected_option and answer_text against validated payload is required. Existing Laravel trimming/empty-string-to-null middleware applies before comparison; omitted fields represent null, not a partial patch. Identical retry returns HTTP 200/idempotent without modifying the answer or its timestamp. Different payload returns HTTP 409/conflict and does not write.

## F. Stale writes

Lower revision always returns HTTP 409/stale, including when the payload happens to match. Response includes only status, submission_id, question_id, authoritative revision and the owner's selected_option/answer_text. No scoring, correct answer, teacher metadata or another student's data is returned.

## G. Browser reconciliation

Initialize local and acknowledged revisions from the server. Each edit increments its question revision and sends it with the payload. Parse the server acknowledgement before clearing recovery; an older acknowledgement preserves and flushes the current draft. Recovery records include revision, acknowledged_revision and payload, under the existing student/exam/submission key and question entry. Timestamp metadata remains diagnostic only.

On 409, retain the draft, mark unsaved, block automatic retries and show explicit “Use server answer” / “Save my draft” choices. Keeping the draft explicitly creates a revision greater than both known local and authoritative versions. A later competing server update can still reject it safely. Legacy timestamp-only recovery requires this explicit choice. Acknowledgement cleanup checks the stored draft so it cannot erase a different draft written by another tab.

## H. Multiple tabs

Batch 2A lease warning remains advisory. Tested B revision 6 arriving before A revision 5: A gets 409, server retains B, and browser A stays unsaved until reconciliation. No lockdown claim. Server revisions provide final write protection.

## I. Finalization

Active/expiry checks run again under the shared lock. Late stale and higher revisions cannot mutate finalized attempts. Manual submission waits for pending acknowledgements; its timer now keeps running when flush fails. Timeout still attempts the bounded flush and finalizes server-saved answers. Heartbeat expiry and submit/timeout retries are covered. Simultaneous MariaDB finalization is unverified.

## J. Legacy answers

Historical rows default to 0; finalized records need no rewrite. Active legacy answers require a higher revision to change, while identical revision 0 retries are idempotent. Invalid/finalized attempts are rejected before revision comparison.

## K. Existing files modified by this batch

- app/Http/Controllers/OnlineExamController.php
- app/Http/Requests/OnlineExam/SaveOnlineExamAnswerRequest.php
- app/Models/OnlineExamAnswer.php
- resources/views/student/online_exam/take.blade.php
- tests/Feature/Support/OnlineExamTestHelper.php
- tests/Feature/OnlineExamBatch1RegressionTest.php
- tests/Feature/OnlineExamBatch2AReliabilityTest.php
- tests/Feature/OnlineExamControllerSecurityTest.php

Prior tests now send required revisions; their regression assertions remain. The helper applies the actual new migration to its in-memory schema.

## L. Files created

- database/migrations/2026_09_19_000004_add_answer_revision_to_online_exam_answers.php
- tests/Feature/OnlineExamBatch2BRevisionTest.php
- tests/online-exam-revisions.test.cjs
- docs/online-exams-audit/batch2b-report.md
- docs/online-exams-audit/batch2b-mariadb-verification.md
- docs/online-exams-audit/batch2b-git-status-before.txt and batch2b-git-status-after.txt
- docs/online-exams-audit/batch2b-tests.txt and batch2b-tests.xml
- docs/online-exams-audit/batch2b-browser-tests.txt
- docs/online-exams-audit/batch2b-diff.patch
- docs/online-exams-audit/rollback-batch2b/ (pre-edit copies of affected application files, the test helper and existing Online Exams tests)

## M. Tests added

14 PHP tests: first/higher revision; reversed arrival/tab stale rejection; equal identical/timestamp stability; equal different/lower revision; first-create replay plus unique constraint; student/school isolation and conflict privacy; finalized statuses/expiry; blank clearing; written-payload identity; mismatched exam school; heartbeat/timeout late writes; validation/question/route boundaries; manual submit/timeout late writes; isolated migration round trip/legacy revision 0.

7 executable Node tests run the actual Blade autosave functions with deterministic DOM/network/storage doubles: delayed acknowledgement, multi-tab stale conflict, equal conflict/server choice, revision recovery/legacy draft preservation, finalized/expired rejection, pending flush and cross-tab recovery cleanup. These are not full browser end-to-end tests.

Concurrent first creation and other true simultaneous engine scenarios are **deferred**, not represented as proven by the sequential replay/unique-constraint test. See MariaDB preparation document.

## N. Exact test results

Final commands and results are recorded in the adjacent test artifacts. PHP: 85 tests, 289 assertions, 0 failures, 0 errors, 0 skipped. Node: 7 tests, 7 passed, 0 failures, 0 errors, 0 skipped; Node does not report assertion totals.

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --filter OnlineExam --log-junit docs/online-exams-audit/batch2b-tests.xml
node --test --experimental-test-isolation=none tests/online-exam-revisions.test.cjs
git diff --check
```

Initial PHP invocation failed because SQLite extensions were disabled in CLI PHP. Installed extensions were enabled per command; no PHP configuration changes. Initial Node isolated runner was blocked by sandbox process spawning; the approved run passed, and subsequent runs used supported in-process isolation. PHP syntax checks pass for all nine modified/created PHP code files. Routes did not change, so no route inspection was required.

## O. Migration verification

Dedicated SQLite `:memory:` only. Independently ran the new migration down to obtain the pre-revision fixture, inserted legacy answer data, captured column/index definitions, ran up, verified column existence/default 0/data survival, checked revision-0 compatibility, ran down, and compared full prior column/index shape and answer data. Passed. MariaDB migration round trip not run. No live rollback or live migration performed.

## P. MariaDB status

No safe dedicated test database configured; stopped that portion. `piie_main` was not used for destructive tests. Exact proposed configuration, safety gates and five concurrency scenarios plus migration verification are in `batch2b-mariadb-verification.md`. No database created/dropped. SQLite is not MariaDB locking proof.

## Q. Security findings

Ownership, role, submission school, exam school, route/body identity and question membership remain independent of revisions. Locked checks reject finalized/expired attempts. Revision cannot reset backwards. Conflict responses expose only the authorized owner's answer. Tests cover these boundaries. No global role changes.

## R. Remaining reliability gaps

- MariaDB real-process concurrency/first-create/finalization and engine-specific migration verification are pending a dedicated database and runner.
- localStorage is best-effort and has no cross-tab atomic compare-and-swap; simultaneous draft edits can still contend for local recovery storage. Server answer ordering remains protected. Storage denial/quota failure cannot guarantee offline recovery.
- An offline answer at expiry cannot be promised accepted; finalization uses acknowledged server state.
- Browser tests use doubles, not full multi-tab browser automation.
- Revision space is finite. At the unsigned integer maximum the server accepts identical retries but cannot accept a higher revision; the conflict UI will not wrap/reset it.
- Deployment must migrate before serving this code, and active old pages must refresh to send revisions. No deployment occurred here.

## S. Other PIIE modules / safety

Initial `git status --short` is recorded. Pre-edit scoped rollback copies preserve Batch 1/2A. Review Batch 2B against those copies, not HEAD, because the working tree already contained extensive accepted and unrelated changes. No historical migrations, routes, unrelated modules or global roles were edited by Batch 2B. No destructive Git commands and no commit. `git diff --check` passes.

## T. Recommended next batch

Only after approval: provision/authorize the dedicated MariaDB test environment and execute the prepared concurrency and migration verification matrix. Do not infer that broader Online Exams features are authorized. Stopped after Batch 2B.
