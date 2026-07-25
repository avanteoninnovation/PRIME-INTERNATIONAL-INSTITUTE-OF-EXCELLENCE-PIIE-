# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Laravel 9 (PHP 8.1) multi-tenant School/HEI (Higher Education Institution) management system,
publicly branded "Prime International Institute of Excellence (PIIE)" but internally still
referencing its origin as "Twinehs Divine Integrated Institute of Business and Technology (TDIIBT)"
in places (e.g. `README.md`) — this is a rebrand of a CodeCanyon-style installable school-management
product, not a from-scratch app. It ships its own web installer (`InstallController`) and in-app
updater (`Updater`), so expect self-install/license patterns rather than a typical `composer create-project` app.

## Commands

```bash
# Install PHP deps
composer install

# Install & build frontend assets (Laravel Mix, Bootstrap 5, jQuery-era stack — not a SPA)
npm install
npm run dev        # or: npm run watch / npm run hot / npm run prod

# Run the app
php artisan serve

# Config/cache housekeeping (do this after touching .env, config/*, or routes/*)
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
php artisan route:list

# Tests (Feature suite only — see phpunit.xml, no Unit suite is configured)
php artisan test
vendor/bin/phpunit
vendor/bin/phpunit --filter test_permission_service_accepts_legacy_and_canonical_menu_keys
vendor/bin/phpunit tests/Feature/OnlineExamPermissionTest.php
```

There are no `composer check:platform` / `deps:validate` / `deps:audit` scripts actually defined in
`composer.json` despite `CONTRIBUTING.md` referencing them — treat those as aspirational/stale unless
you add the scripts yourself. `composer.json` pins the platform to PHP `8.1.25` (`config.platform.php`)
specifically so dependency resolution doesn't silently pull PHP 8.2-only packages; bump that
intentionally, together with a fresh `composer.lock`, if the runtime is upgraded.

## Architecture

### Single-database multi-tenancy

All schools share one set of tables; there is no per-tenant database/schema. Tenant isolation is by
a `school_id` foreign key on rows (`users.school_id`, and `school_id` columns on most feature tables
like `OnlineExam`, `LiveClass`, etc.). Authorization code must always pair a permission check with a
same-school check — see `App\Support\Permissions\OnlineExamAuthorizer::sameSchool()` for the pattern
used across the exam/assignment/live-class modules. `School` (schools table) and `Package`/`Subscription`
model the tenant + billing plan; `SuperAdminController` manages schools/packages across the whole
platform, while `AdminController` (and the other staff controllers) operate within one school.

### Roles are numeric `role_id`, not a permissions package

There is no Spatie-style roles/permissions package. `users.role_id` is a plain integer
(1 = Super Admin, 2 = Admin, 3 = Teacher, 7 = Student, etc. — see role IDs used throughout the
`*Middleware` classes and `tests/Feature/OnlineExamPermissionTest.php`). Per-role, per-school menu/feature
permissions are stored as JSON blobs in the generic `global_settings` key/value table under keys like
`role_perm_{role_id}` (seeded/merged by seeders such as `OnlineExamPermissionSeeder`, which merges new
default permissions into existing JSON without clobbering customizations — `!permission_name` in the
array means an explicit deny). Per-user overrides live in `users.menu_permission` (JSON) and both legacy
and canonical permission key spellings must be accepted when reading it (see
`OnlineExamPermissionService`). Route access is gated by one-role-per-file middleware in
`app/Http/Middleware/*Middleware.php`, registered as route-middleware aliases in `app/Http/Kernel.php`
(`admin`, `teacher`, `student`, `parent`, `librarian`, `accountant`, `warden`, `registrar`, `bursar`,
`hod`, `director`, `hr_manager`, `procurement`, `store_keeper`, `receptionist`, `examinations`,
`admissions_staff`, `staff`, `superAdmin`, plus `admin_permission`/`role_id` for finer-grained checks).
When adding a feature that needs authorization beyond "is this role", follow the
`App\Support\Permissions` pattern (a `*PermissionService` for raw permission lookups plus a
`*Authorizer` that composes permission + same-school + ownership checks) rather than inlining checks
in controllers.

### Routes are one flat file per HTTP verb group, segmented by role prefix

`routes/web.php` (~1400 lines) is not split into per-domain files. Routes are grouped with
`Route::controller(X::class)->middleware(...)->group(...)` blocks, one block per role area
(`superadmin/...`, `admin/...`, `teacher/...`, `student/...`, `parent/...`, etc.), each named
`{area}.{resource}.{action}`. When adding routes, find the existing block for that role/module and
extend it rather than creating a new top-level pattern. `routes/api.php` is minimal (Sanctum-backed).

### Controllers are broad, one per domain, not one per resource

`app/Http/Controllers` has ~37 controllers covering entire domains (e.g. `SuperAdminController`,
`TeacherController`, `StudentController` each handle many unrelated actions for that role/area, rather
than Laravel's typical one-controller-per-model convention). `WebsiteManagementController` is shared
between the `superadmin/website-management` and `admin/website-management` route groups (platform-wide
CMS vs. per-school CMS) — check which middleware group a route is under before assuming which tenant
scope a website-management action applies to.

### Website/CMS is a fully dynamic, no-deploy content system

The public marketing site is not hardcoded Blade — it's driven by four tables (models
`WebsitePage`, `WebsiteSection`, `WebsiteItem`, `WebsiteSetting`, plus `WebsiteSeoSetting`), documented in
`CMS_GUIDE.md`. Pages have `page_key`/`slug`; sections belong to a page via `page_key` and have a
`section_key` that a rendering layer (`App\Helpers\WebsiteRenderingHelper`) maps to a Blade component
(hero, portals, programs, team, faqs, news, testimonials, gallery, steps, or a generic fallback for
unrecognized `section_key`s). Items belong to a section via `section_key`. Adding a new "kind" of content
block usually means adding a new `section_key` → component mapping in the rendering helper, not a new
route/controller/table. `HomeController@websitePage` serves these dynamically by slug.

### Feature areas worth knowing before touching them

- **Online Exams**: newest, most rigorously tested module (`tests/Feature/OnlineExam*.php`,
  `App\Support\Permissions\OnlineExamAuthorizer`/`OnlineExamPermissionService`). Treat this as the
  reference implementation for how authorization *should* be done elsewhere in the app.
- **Live Classes**: supports Jitsi (no external API needed), Zoom (Server-to-Server OAuth), and Google
  Meet (Google Calendar API). Provider credentials are `.env`-driven (`ZOOM_*`, `GOOGLE_*`, see
  `LIVE_CLASS_API_SETUP.md`); if a meeting URL is left blank, the backend calls out to the configured
  provider to create one and stores the result — missing/invalid credentials should surface as a
  validation error, not a silent failure.
- **Payments**: multiple gateway integrations coexist (Omnipay/PayPal, Stripe, Razorpay, Paytm) — check
  `PaymentMethods`/`PaymentHistory`/`Payments` models and `services.php` config before assuming which
  gateway a given flow uses.

### Repo hygiene notes

- Root-level `*_GUIDE.md` / `*_REPORT.md` / `*_COMPLETE.md` files are point-in-time implementation
  writeups from past work sessions (CMS, website management, live classes, DB integrity, etc.) — useful
  as historical design context for the areas they cover, but not living documentation; verify against
  current code before relying on specifics.
- Never edit anything under `vendor/`; dependency changes go through `composer.json` +
  `composer.lock` together.
- `extension-amaq/` in the repo root is an unrelated, standalone VS Code extension project (not part of
  the Laravel app) — ignore it unless a task explicitly concerns it.
