# STIT Darul Ilmi Academic Portal

Portal akademik terpadu STIT Darul Ilmi Tasikmalaya.

## Architecture
- Android: Kotlin + Jetpack Compose (planned)
- Web Admin / PMB: PHP web application
- Backend: PHP 8 REST API
- Database: MySQL / MariaDB
- Hosting target: ByetHost Free (development/testing)

## Phase 1 — Backend foundation
- REST API v1, PDO, bearer token login/logout, profile endpoint, role authorization foundation, initial database schema.
- See `docs/API.md`, `docs/ARCHITECTURE.md`, and `docs/PHASE1_CHECKLIST.md`.

## Phase 2 — Admin Web foundation
- Admin login using the shared users table.
- Session-based browser authentication, CSRF protection, session ID rotation, and role-based access.
- Dashboard counts for students, lecturers, programs, courses, classes, and PMB.
- Academic data views; create forms for study programs, academic years, and semesters.
- Deployment guide: `admin/docs/SETUP.md`.

## Setup
1. Create a MySQL database in the hosting panel.
2. Import `backend/database/schema.sql`.
3. Copy `backend/config/database.example.php` to `backend/config/database.local.php` and fill credentials.
4. Keep secrets outside the public web root; never commit credentials.
5. Enable HTTPS. Deploy Admin Web with document root pointing to `admin/public`.
6. Follow `admin/docs/SETUP.md` and test `/api/v1/health`.

This is an evolving development foundation, not yet a complete production SIS. The Admin Web has not yet been deployed or tested on ByetHost. Validate PHP/MySQL compatibility, security, backups, and access controls before real student data is used.
