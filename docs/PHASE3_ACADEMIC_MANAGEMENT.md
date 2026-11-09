# Phase 3 — Academic Management

## Added workflows
- Create student and associated login account in one database transaction.
- Create lecturer and associated login account in one database transaction.
- Create administrative accounts for approved operational roles.
- Create courses linked to a study program.
- Create course classes linked to course, semester, and optional lecturer.
- Create class schedules with weekday and time validation.
- List users, students, lecturers, courses, classes, and schedules in the Admin Web.

## Security and data integrity
- Passwords are stored using PHP `password_hash(..., PASSWORD_DEFAULT)`; passwords are never stored as plain text.
- User-provided SQL values are passed through PDO prepared statements.
- Student/lecturer account and profile creation is transactional: a failed profile insert rolls back the user account.
- Form submissions use the existing CSRF token check.
- Academic write actions are restricted to `super_admin` and `admin_akademik`.
- The account creation form intentionally cannot create another `super_admin`; use the documented controlled setup procedure for that role.

## Current limitations
- These are create-and-list workflows, not complete CRUD. Edit, archive/delete, bulk import, advanced search, and pagination remain to be implemented.
- CSV/Excel import is not included in this increment.
- Schedule overlap detection is not implemented; the operator must verify room and lecturer conflicts.
- No live ByetHost deployment or database integration test has been performed.
- Audit logging, login throttling, and account password reset flows remain required before production use.

## Recommended test plan
1. Use a non-production database and create one study program and academic year.
2. Create a semester, then a course, then a class and schedule.
3. Create a student and lecturer; verify that both profile rows and user rows were created.
4. Test duplicate username, email, NIM, course code, and lecturer number; the transaction should fail without leaving orphan user rows.
5. Log in with each role and verify write permissions.
6. Test with HTTPS and verify session cookie flags.
