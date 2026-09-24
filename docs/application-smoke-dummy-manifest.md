# PIIE application-wide smoke-test dummy manifest

Database: `piie_main`
School: existing school ID 1
Existing session reused: ID 1 (`2026`)
Existing lecturer reused: user ID 3 (`teacher@piie.test`)
Existing Online Exam ID 1 and all its questions/records were not modified.

Persistent records created (all created 2026-09-19):

| Table | ID | Record / purpose | Parent and relationship IDs | Marker |
|---|---:|---|---|---|
| departments | 2 | PIIE Dummy Academic Department | school 1 | name | 
| programmes | 2 | PIIE Dummy Application Programme; Degree, 3 years, fulltime, fee 0 | department 2, school 1 | name | 
| classes | 40 | PIIE Dummy Cohort | school 1 | name | 
| sections | 2 | Dummy Group | class 40 | name | 
| subjects | 41 | PIIE Dummy Class Course; class-linked | class 40, session 1, school 1 | name | 
| subjects | 42 | PIIE Dummy Programme Course; programme-linked | programme 2, session 1, school 1 | name | 
| teacher_permissions | 5 | Lecturer class/section permission; marks and attendance enabled | teacher 3, class 40, section 2, school 1 | table/manifest | 
| teacher_programme_assignments | 1 | Lecturer programme permission; marks and attendance enabled | teacher 3, programme 2, school 1 | table/manifest | 
| users | 62 | Disposable dummy student account | role 7, school 1 | name, address | 
| student_profiles | 1 | Dummy student profile | user 62, programme 2, school 1 | next-of-kin address | 
| enrollment | 16 | Dummy student academic enrollment | user 62, class 40, section 2, department 2, session 1, school 1 | table/manifest | 

Dummy login:

- Email: `piie-dummy-student-2026@example.test`
- Password: `PiieDummy@2026!`
- Database password verified as a normal Laravel bcrypt hash; plaintext is not stored.

No existing rows were updated or deleted. The temporary transaction failure during the first attempt rolled back fully before the successful insert.

To remove only these records later, use the recorded IDs in dependency order (enrollment, student_profiles, teacher permissions, course rows, section, class, programme permission, programme, department, then user). Do this only with explicit approval; no cleanup has been performed.
