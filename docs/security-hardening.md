# Security hardening follow-up

## Legacy specialized-staff credential email

The existing Admin, Teacher/Lecturer, Accountant, Librarian, and Warden provisioning and reset flows use `NewUserEmail`, whose legacy template includes a plaintext password. This behavior was intentionally left unchanged while secure password setup was introduced for Generic Staff. A future, separately controlled migration should replace those legacy credential messages with expiring, single-use setup/reset links, with regression coverage for existing staff authentication and carefully coordinated communication to affected institutions.

The Generic Staff password-setup workflow must remain link-based and must not reuse the legacy plaintext-password email template.
