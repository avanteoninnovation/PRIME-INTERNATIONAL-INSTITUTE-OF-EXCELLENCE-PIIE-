# Contributing Guide

This project should be changed with production-safe, reviewable steps.

## 1. Golden Rules

- Do not edit files under `vendor/` manually.
- Treat `vendor/composer/platform_check.php` as generated output only.
- Make dependency changes in `composer.json` and commit `composer.lock` with them.
- Keep changes small and focused per pull request.
- Run validation before opening a PR.

## 2. Adding or Updating Composer Packages

1. Check platform first:
   - `composer check:platform`
2. Add a package:
   - `composer require vendor/package`
3. Add a dev package:
   - `composer require --dev vendor/package`
4. Validate dependencies:
   - `composer deps:validate`
5. Check security advisories:
   - `composer deps:audit`

If your package requires a newer PHP version, either:
- upgrade local/runtime PHP, or
- select a package version compatible with current PHP.

## 3. Professional Change Structure

When implementing features, use this structure:

- Routes: `routes/web.php` or `routes/api.php`
- Validation: Form Request classes in `app/Http/Requests/`
- Business logic: service-style classes or dedicated methods in controllers
- Persistence: Eloquent models in `app/Models/`
- Views/UI: Blade files in `resources/views/`
- Reusable helpers: `app/Helpers/CommonHelper.php` (only for truly generic utilities)

## 4. Quality Gate Before Commit

Run this minimum gate locally:

- `composer check:platform`
- `composer deps:validate`
- `php artisan route:list`
- `php artisan config:clear`
- `php artisan cache:clear`

If tests exist for changed area, run them before commit.

## 5. Commit and PR Hygiene

- Use clear commit titles, e.g. `feat(admissions): add student transfer endpoint`.
- Include migration impact and rollback notes in PR description.
- Mention any env/config changes explicitly.

## 6. About Current PHP Compatibility

Current project config pins Composer platform to PHP 8.1.25.
This protects dependency resolution from accidentally pulling PHP 8.2-only packages while the server is still on PHP 8.1.

When upgrading infrastructure to PHP 8.2+, update `composer.json` platform config and re-resolve dependencies intentionally.
