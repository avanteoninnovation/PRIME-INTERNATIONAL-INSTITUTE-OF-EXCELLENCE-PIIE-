# Batch 2A schema boundary

The current `online_exam_answers` table has a unique `(submission_id, question_id)` pair and timestamps, but no server-side answer revision/version.

Existing timestamps are insufficient for safe stale-write ordering: two requests can be created from the same answer state, arrive out of order, and have equal or ambiguous timestamp precision. A client clock cannot be trusted, and `updated_at` alone cannot identify which client revision is newer.

For a later approved migration, add an unsigned `answer_revision` (default `0`) to `online_exam_answers`, with an index alongside `submission_id` and `question_id` as appropriate. The save contract should carry a submission/question-scoped client revision; the server should accept only a strictly newer revision, return the authoritative revision, and treat equal revisions idempotently. A migration must backfill existing rows to `0`, be additive and nullable/defaulted for rollback compatibility, and must not alter historical exam data.

Batch 2A does not create this migration. Browser recovery, server timestamps, locking, and ownership checks are implemented without claiming server-side stale-write ordering is solved.
