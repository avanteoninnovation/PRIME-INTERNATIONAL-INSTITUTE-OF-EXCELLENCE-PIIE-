# Candidate generated records in `piie_main` (do not delete in this task)

These are read-only candidates identified by the generated `6aaef...` suffix, synchronized creation timestamps, and linked fixture graph. They are reported for later approval only. No rows were deleted or updated.

| Table | Candidate IDs | Identifying values | Related records |
|---|---:|---|---|
| `classes` | 41–59 | `Class 6aaef...`, school 1, created 2026-09-19 20:56:02–20:56:05 | Subjects 43–61; enrollments 17–23 for a subset |
| `subjects` | 43–61 | `Subject 6aaef...`, classes 41–59, sessions 40–58, school 1 | Parent generated classes/sessions |
| `sessions` | 40–58 | `Session 6aaef...`, school 1, created in the same five-second window | Subjects 43–61 and generated enrollments |
| `users` | 63–90 | `User <role> 6aaef...`, `u6aaef...@example.test`, school 1 | Enrollments 17–23 for generated student users; role IDs 2/3/6/7 |
| `enrollment` | 17–23 | Generated users 72/74/76/78/83/85/87 linked to generated classes 49/50/51/53/57/58/59 and sessions 48/49/50/52/56/57/58 | Parent generated user/class/session rows |

The complete read-only row capture is in `storage/app/candidate-generated-records.json`. The pattern is consistent with the fixture helpers in the broad Feature run and is separate from the authorized records that must be preserved (`departments.id=2`, `programmes.id=2`, `classes.id=40`, `sections.id=2`, `subjects.id=41/42`, `teacher_permissions.id=2/5`, `teacher_programme_assignments.id=1`, `users.id=62`, `student_profiles.id=1`, `enrollment.id=8/16`, and Online Exam 1).
