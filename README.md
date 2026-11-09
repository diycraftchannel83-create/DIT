# STIT Darul Ilmi Academic Portal

Portal akademik terpadu STIT Darul Ilmi Tasikmalaya.

## Architecture
- Android: Kotlin + Jetpack Compose (planned)
- Web Admin / PMB: PHP web application (planned)
- Backend: PHP 8 REST API
- Database: MySQL / MariaDB
- Hosting target: ByetHost Free (development/testing)

## Phase 1
- REST API v1, PDO, login/logout bearer token, profile endpoint, role authorization foundation, initial database schema.

## Setup
1. Create a MySQL database in the hosting panel.
2. Import backend/database/schema.sql.
3. Copy backend/config/database.example.php to database.local.php and fill credentials.
4. Keep secrets outside the public web root; never commit credentials.
5. Enable HTTPS and test /api/v1/health.

## Endpoints
- GET /api/v1/health
- POST /api/v1/auth/login
- POST /api/v1/auth/logout (Bearer)
- GET /api/v1/me (Bearer)

This is a Phase 1 foundation, not yet a complete production SIS. Validate PHP/MySQL compatibility, security, backups, and access controls before real student data is used.
