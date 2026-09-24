# PHPUnit test-database isolation

## Guard

`tests/CreatesApplication.php` now forces the application created under `APP_ENV=testing` to use SQLite `:memory:`. The configured MySQL database is replaced for that test process with `__piie_phpunit_blocked__`; an explicit MySQL access therefore fails safely instead of reaching `piie_main`. The normal web/Artisan environment is unchanged because this branch runs only for the test application.

`LiveClassModuleTest` was the confirmed exception: it built its own schema and fixtures without selecting SQLite. Its setup now purges/reconnects the SQLite connection before the schema guard and fixture methods run.

## Verification

- Preflight database: `piie_main` (99 tables).
- Selected pre-test counts were saved in `storage/app/piie-counts-before-isolation.json`.
- Academic/Live Class/Programme/Staff selection: **5 tests, 16 assertions, 0 failures, 0 errors**. The Live Class tests safely skipped when their required schema was absent rather than writing to MySQL.
- Online Exams: **120 tests, 538 assertions, 0 failures, 0 errors**.
- Browser revision tests: **7 passed, 0 failed**.
- Admissions representative selection: **5 tests, 10 assertions, 1 existing failure** (`AuthController::register()` calls `array_column()` with null at line 63). This task did not repair Admissions.
- Post-test counts across all 99 tables: no differences (`diff: []`). Protected snapshots for Online Exam 1, its questions, teacher permissions 2/5, and enrollments 8/16 are unchanged.
- No migration was created or executed; `.env` was not changed.
