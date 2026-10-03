# PIIE — Staging Manifest

Branch: `feature/piie-online-exams-governed-workflow`
Base commit: `c5c32a2`
Prepared: read-only audit. **Nothing has been staged, committed, pushed or deployed.**

---

## 1. NEVER STAGE — hard stops

These must not be added to the index under any circumstances. Each is already
covered by `.gitignore`; the rule protects *new* files only, so the discipline has
to be manual.

| Path | Why | Ignore rule |
|---|---|---|
| `.env`, `.env.*` | Production/local secrets, `APP_KEY`, DB password | present |
| `storage/app/google/` | Google OAuth client credentials | `.gitignore:28` |
| `public/assets/uploads/user-images/` | Real user profile photographs | `.gitignore:137` |
| `public/assets/uploads/documents/` | Institution documents | `.gitignore:139` |
| `public/assets/uploads/offline_payment/` | Payment evidence screenshots | `.gitignore:138` |
| `public/assets/uploads/admissions/` | Applicant ID scans, certificates | `.gitignore:86` |
| `public/assets/uploads/application_payments/` | Application payment records | `.gitignore:87` |
| `storage/app/assignment-submissions/` | Student submitted work | `.gitignore:140` |
| `storage/app/course-content/` | Runtime course-content files | `.gitignore:141` |
| `bootstrap/cache/*.php` | Generated cache | `.gitignore:145` |

**Do not run `git add .`, `git add -A`, or `git commit -a` on this branch.**
There are 197 untracked paths and 154 modified paths. A blind add stages all of
them together with anything that appears in the meantime.

---

## 2. ALREADY TRACKED BUT NOW IGNORED — needs a decision

`.gitignore` does **not** untrack. These 27 files are in commit `c5c32a2` and
will keep shipping regardless of the ignore rules.

### 2a. Should be REMOVED from tracking (and from history)

| Path | Count | Reason |
|---|---|---|
| `public/assets/uploads/user-images/*.png\|jpg` | 13 | Real user photographs. Personal data in a public repository is not removable by a later ignore rule — only by a history rewrite. |
| `public/assets/uploads/offline_payment/*` | 8 | Payment screenshots and two PDFs including a dated member list. Financial records. |
| `public/assets/uploads/documents/*.pdf` | 1 | Institution brochure PDF. |

> **This requires your approval and is deliberately NOT actioned.** Removing these
> from history means rewriting `c5c32a2`'s descendants, which invalidates any
> clone and changes every commit hash. For a private repository the pragmatic
> step is to untrack them going forward and accept that they remain in history.

Proposed (non-destructive) command, once approved:

```
git rm --cached -r public/assets/uploads/user-images
git rm --cached -r public/assets/uploads/offline_payment
git rm --cached -r public/assets/uploads/documents
```

### 2b. Should be KEPT tracked — the ignore rule is wrong here

| Path | Why |
|---|---|
| `config/services.php` | Holds the Zoom / Google / Jitsi configuration. **Untracking it removes that configuration from every clone and deploy.** Audited: all secrets come from `env()`. |
| `config/auth.php` | Standard auth config. Audited: no secrets. |
| `config/cache.php` | Standard cache config. Audited: `env()` only. |
| `bootstrap/cache/packages.php`, `bootstrap/cache/services.php` | Generated. Should be untracked — see 2c. |

Proposed `.gitignore` amendment:

```
!/config/services.php
!/config/auth.php
!/config/cache.php
```

### 2c. Should be UNTRACKED (generated, not source)

```
git rm --cached bootstrap/cache/packages.php bootstrap/cache/services.php
```

### 2d. Deleted in the working tree — must NOT be committed as deletions

`git status` reports three tracked files as deleted:

```
public/assets/images/user-img.png
public/assets/images/user.jpeg
public/assets/images/user.png
```

The blobs are present in `c5c32a2`; only the working-tree copies are gone.
`InstallController` seeds `'photo' => "user.png"` as the default user photo.
Committing the deletions removes the default avatar asset from the repository.

**Restore before staging, or make the removal a separate reviewed commit:**

```
git checkout -- public/assets/images/user-img.png public/assets/images/user.jpeg public/assets/images/user.png
```

---

## 3. SAFE TO STAGE — new infrastructure (this phase)

Explicit paths only. No wildcards.

```
git add .github/workflows/piie-ci.yml
git add .github/workflows/piie-deploy.yml
git add STAGING-MANIFEST.md
```

## 3a. ALREADY STAGED

| File | Note |
|---|---|
| `composer.lock` | Staged. 10,485 lines, 107 + 38 packages, `platform-overrides {"php":"8.3.0"}`. Audited: no credential markers, valid JSON. Without this, CI is not reproducible. |

## 3b. SAFE TO STAGE — prior reviewed phases

These are the completed, test-backed work from earlier sessions. Staging them is
a **separate decision**: it commits ~350 files of application change to a branch
that has never been pushed.

```
# Google Meet integration
git add app/Support/Google/
git add app/Support/LiveClasses/MeetingResolution.php
git add app/Support/LiveClasses/GoogleConferenceStatus.php
git add app/Models/GoogleAccountConnection.php
git add app/Http/Controllers/GoogleAuthController.php
git add database/migrations/2026_10_04_000001_create_google_account_connections_table.php
git add database/migrations/2026_10_04_000002_add_google_calendar_fields_to_live_classes.php
git add resources/views/admin/live_class/_google_connection.blade.php
git add tests/Feature/GoogleMeetIntegrationTest.php
git add tests/Feature/GoogleConnectionAudienceTest.php
git add scripts/probe-google-migrations.php

# Student course catalogue
git add app/Support/CourseRegistration/StudentCourseCatalogue.php
git add resources/views/student/my_courses_hei.blade.php
git add resources/views/student/navigation.blade.php
git add public/css/student-courses.css
git add tests/Feature/StudentCourseCatalogueUiTest.php

# Programme cover images
git add app/Support/Images/
git add resources/views/website_management/partials/
git add public/css/piie-site.css
git add public/css/piie-blocks.css
git add tests/Feature/ProgrammeCoverImageTest.php
```

---

## 5. STILL MISSING FROM TRACKING

| File | Consequence |
|---|---|
| `public/mix-manifest.php` | **Blocking for deploy.** Node is absent on production, so the release cannot build assets. Compiled Mix assets and this manifest must be committed, or the deployed site has no CSS/JS. |
| `config/tenant.php`, `config/online_exam_integrity.php` | Present on disk, absent from HEAD. `/config/*.php` is ignored with an allowlist these two are missing from, so they would be **lost on any fresh clone**. |
| `public/assets/images/user*.png` | See 2d — currently deleted in the working tree. |

## 5a. PRODUCTION FACTS (verified read-only over SSH, 2026-10-03)

Recorded so the next session does not re-derive them.

| Fact | Value |
|---|---|
| SSH user / port | `piie` / `25552` |
| Authorised key | **v3 only** — `SHA256:6Hnrk+QwL5lsM2cyqscy1/QSIxx1/AZXpl82Z5nY44U` |
| Revoked keys | v2 `SHA256:kkAq1hmo...`, v1 — both confirmed rejected |
| Server host key (pin this) | ED25519 `SHA256:AJCXGAyIoflDFBnXb6kxSGAALqUI9kOE0Bo4SHsUbTE` |
| **PHP (CLI, intended)** | **`/usr/local/php83/bin/php` = 8.3.12** - always name it explicitly |
| **PHP (LiteSpeed handler)** | `/usr/local/php83/bin/lsphp` -> `lsphp83`, same 8.3 install |
| **`php` on bare PATH** | `/usr/local/bin/php` -> php81 build = **8.2.27** - do not use |
| DirectAdmin `php.ini` selector | `.../users/piie/php/php.ini` -> `alt-php82` |
| Composer | 2.7.9 at `/usr/local/bin/composer` |
| Node / npm | **absent** |
| Web server | **LiteSpeed** (sends no `X-Powered-By` - good practice) |
| Document root | `/home/piie/domains/piie.ac.ug/public_html` - a real directory, **not** a symlink |
| Live layout | `public_html` contains the **application root** (`artisan`, `app/`, `vendor/`, nested `public/`) - not a standard document root |
| Live `.env` | present at `public_html/.env` |
| Release dirs | `releases/`, `shared/`, `backups/` all exist and are empty |
| Live site | `https://piie.ac.ug` -> HTTP 200 behind a "Bot Verification" interstitial |

### PHP 8.3 - resolved, and my earlier report was wrong

An earlier survey reported "PHP 8.2.27" and I inferred that PHP 8.3 was
unavailable, making the `8.3.0` platform pin a deploy blocker.
**That inference was wrong.** The operator's DirectAdmin verification is the
authoritative result, and both versions are in fact installed:

```
/usr/local/php83/bin/php     -> 8.3.12   CLI             <- intended
/usr/local/php83/bin/lsphp   -> lsphp83  LiteSpeed web handler, same install
/usr/local/bin/php           -> 8.2.27   bare PATH      <- what `php` resolves to
```

`/usr/local/php83` carries BOTH the CLI binary and the LiteSpeed handler, so one
8.3 install serves both tiers.

**Why the earlier survey saw 8.2.27.** The non-interactive SSH `PATH` is:

```
/home/piie/.local/bin:/home/piie/bin:/usr/share/Modules/bin:/usr/local/bin:/usr/bin:/usr/local/sbin:/usr/sbin
```

`/usr/local/php83/bin` is **not** on it, so `command -v php` resolves to
`/usr/local/bin/php`. DirectAdmin's terminal prepends the php83 path. Note that an
*interactive* SSH shell (`bash -l`) shows the SAME PATH as non-interactive, so the
divergence is specific to the DirectAdmin terminal session - not to interactivity
in general. That is why both measurements were individually accurate and jointly
misleading.

**What needs doing - and what must NOT be done:**

| | |
|---|---|
| **KEEP** `config.platform.php = 8.3.0` | The code targets 8.3; `composer.lock` is resolved for it |
| **DO NOT** re-lock for 8.2 | That resolves a tree the code was never written against |
| **DO** invoke `/usr/local/php83/bin/php` explicitly | Every `artisan` / `composer` call in a deploy must name it |
| **Operator action** | Set the DirectAdmin PHP selector for this account to 8.3, so cron and the panel also use 8.3 |

The residual risk is real but different from what I first reported: not a missing
runtime, but that **`php` currently resolves to 8.2.27**. Any cron job, queue
worker or deploy command calling bare `php` therefore runs 8.2 against an
8.3-resolved tree - and it fails quietly, because both parsers accept the same
source.

### Web-serving PHP version - still needs one confirmation

The LiteSpeed vhost configuration is root-owned
(`/usr/local/lsws/conf/vhosts/` returns "Permission denied") and the site sends
no `X-Powered-By`, so the web tier version cannot be proven from outside. Please
confirm in the DirectAdmin terminal:

```
grep -r "lsphp" /usr/local/lsws/conf/vhosts/ | head
ls -la /usr/local/directadmin/data/users/piie/php/
```

`/usr/local/php83/bin/lsphp83` existing is good evidence, but good evidence is not
a verified vhost binding.

### Remaining deploy blockers

1. **`public_html` is not a symlink** and holds the application root rather than a
   document root. A release-symlink swap changes the served layout.
2. **No Node on the host** - compiled Mix assets and `public/mix-manifest.php` must
   be committed, or the release ships without CSS/JS.