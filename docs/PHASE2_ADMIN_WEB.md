# Phase 2 — Admin Web Portal

## Deliverables
- Admin Web front controller: `admin/public/index.php`
- Apache rewrite: `admin/public/.htaccess`
- Deployment/security notes: `admin/docs/SETUP.md`

## Access
- `super_admin`: all current admin modules.
- `admin_akademik`: academic management and academic data.
- `ketua_prodi`, `ketua_stit`: read-only dashboard/data views.
- `admin_keuangan`: login foundation; finance-specific screens are not yet implemented.

## Current modules
Dashboard; program studi (create/list); academic years (create/list); semesters (create/list); students, lecturers, courses, course classes, and PMB (read-only lists).

## Verification status
Files are committed to the GitHub repository. No live deployment or hosting database test has been performed. Follow `admin/docs/SETUP.md` before testing on ByetHost.
