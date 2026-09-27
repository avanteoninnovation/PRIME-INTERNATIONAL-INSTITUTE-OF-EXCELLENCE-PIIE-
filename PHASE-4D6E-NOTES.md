# Phase 4D6E — Lecturer Allocation Administration

## Implemented

- Added tenant-local `academic.course_offering.lecturer.view` and `academic.course_offering.lecturer.manage`; manage depends on view.
- Added explicit RBAC mappings for the Offering-scoped lecturer workspace, history, assignment, planned edit, activation, end, cancellation, and replacement routes.
- Added the Offering → Lecturers workspace, preserving the Offering identity and lifecycle context, with separate Current Teaching Team, Planned, and Historical groups.
- Added the assignment form, planned edit, explicit activation, end, cancellation, and replacement controls, including period bounds, inclusive date guidance, and confirmation prompts.
- Added readable lecturer-specific AuditLog history scoped to this tenant and Offering's allocation IDs.
- Reused `CourseOfferingLecturerAllocationService` for mutations and eligible candidate lookup. No new domain rule set or allocation schema was introduced.
- Added responsive table/mobile-card presentation and tests for route maps, permission boundaries, tenant/Offering isolation, eligibility presentation, lifecycle workflows, replacement/history retention, primary conflicts, and terminal Offering read-only behavior.

## Deferred

- Bulk allocation remains deferred pending explicit owner approval.
- Downstream integrations, K12 changes, workload policy, and automatic scheduling integration remain out of scope.
- The optional read-only Staff Profile panel was left out; it can be considered separately without moving allocation creation out of the Offering workspace.

## Assumptions and follow-up

- Existing service lifecycle rules are authoritative: Draft supports planned assignment; activation is allowed only for Open/In Progress Offerings; Completed/Cancelled are read-only.
- Existing pure-K12 Offering visibility is reinforced by denying this workflow for K12 tenants.
- PHP does not load PDO SQLite by default; tests were run with the existing `php_pdo_sqlite.dll` enabled per process (`php -d extension=php_pdo_sqlite.dll`). No php.ini change or MySQL test fallback was used.
- No migrations were run and no schema change was made during 4D6E.
