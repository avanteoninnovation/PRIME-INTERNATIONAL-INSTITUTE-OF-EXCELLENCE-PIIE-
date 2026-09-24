# Batch 2B: dedicated MariaDB verification preparation

## Status

NOT RUN. `.env` points to the normal `piie_main` database. There is no `.env.testing`, MariaDB test connection in `phpunit.xml`, or dedicated DB/MariaDB test environment override. No test database was created or dropped. No destructive tests or migration were run against `piie_main`.

The only live-database operation was read-only `SHOW CREATE TABLE online_exam_answers`. It confirmed InnoDB and the existing unique answer pair.

## Required configuration

Have an administrator provision an **empty, disposable, dedicated** database and a user restricted to that database. Provisioning is not authorized by this batch. Example configuration for the future dedicated runner:

```dotenv
APP_ENV=testing
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=piie_online_exams_test
DB_USERNAME=piie_online_exams_test
DB_PASSWORD=<dedicated-test-user-password>
DB_SOCKET=
DATABASE_URL=
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
ONLINE_EXAMS_MARIADB_TEST_DATABASE=piie_online_exams_test
ONLINE_EXAMS_ALLOW_DESTRUCTIVE_TESTS=1
```

The last two values are proposed explicit safety gates for the future harness, not implemented application settings. The dedicated user needs SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP and INDEX privileges **only on that test database**, with no access to `piie_main` or production databases. Use InnoDB and the deployment's MariaDB version/isolation settings. Store real credentials outside committed files.

The current `OnlineExamTestHelper` deliberately forces SQLite `:memory:`. Changing environment variables alone will **not** make the existing suite a MariaDB concurrency test. A dedicated runner must validate the database name, explicit destructive-test opt-in, `APP_ENV=testing`, and actual `SELECT DATABASE()` before any schema or fixture writes. Do not use `migrate:fresh` against an unverified connection.

## Verification matrix for that runner

Use independent PHP worker processes and independent database connections. Coordinate with barriers, capture worker exceptions, and use bounded waits. A sequential endpoint replay does not count as a concurrency test.

1. **Concurrent existing answer saves:** seed revision 4; worker B saves 6 while A attempts 5. Hold the submission lock in B until A has attempted acquisition, then release. Assert B=200, A=409/stale, final revision=6 and B's payload. Repeat reversed acquisition order: both may succeed, final revision must be 6.
2. **Concurrent first creation:** no answer row; two workers save the same submission/question. Assert one row, no uncaught uniqueness exception, and highest revision wins. Repeat equal revisions with identical payload (one success plus idempotent) and different payloads (one success plus conflict).
3. **Stale rejection:** repeat reads/writes across connections after committed revision 6. Revision 5 must produce 409 with only the owner's authoritative answer. Confirm no answer timestamp change.
4. **Duplicate attempt creation:** two start requests for the same student/exam released together. Assert only one active attempt and the existing rejection contract for the losing request.
5. **Submit versus timeout:** release both finalizers together; assert one final state/score, no reopening or downgrade. Repeat with an answer save waiting behind finalization: reject without answer mutation. Repeat with save holding the lock first: finalization must grade the committed saved answer.
6. **Migration round trip:** create legacy schema using the historical answer migration in the dedicated database, insert representative objective/written/finalized rows, capture `SHOW CREATE TABLE` and rows, run only the new migration up, verify unsigned/non-null/default 0 and unchanged indexes/data, then down and compare prior shape/data. Never run this on the normal database.

SQLite tests in this batch establish endpoint behavior, uniqueness and migration reversibility on SQLite. They do **not** establish MariaDB row-lock behavior, deadlock handling, concurrent first-write correctness in the deployed engine, or concurrent finalization behavior.
