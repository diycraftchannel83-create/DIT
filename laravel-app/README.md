# Laravel 13 — SIAKAD academic database bridge

This directory contains persistent Laravel integration source. The deployment workflow creates a clean Laravel 13 runtime and overlays these files before packaging.

## Safety contract

- The existing SIAKAD database remains authoritative.
- This first integration phase is read-only.
- Do not run migrations, fresh migrations, database wipes, seeders, or SQL imports against the existing academic database.
- Create a dedicated MySQL account with SELECT-only privileges for the Laravel preview. Do not reuse the legacy application's write-enabled database account.
- Never commit credentials or production environment files.

## Configure the preview environment

Set these values only in the deployed Laravel preview's private environment file after confirming the hosting database host and name in ByetHost:

    ACADEMIC_DB_HOST=
    ACADEMIC_DB_PORT=3306
    ACADEMIC_DB_DATABASE=
    ACADEMIC_DB_USERNAME=
    ACADEMIC_DB_PASSWORD=
    ACADEMIC_DB_CHARSET=utf8mb4
    ACADEMIC_DB_COLLATION=utf8mb4_unicode_ci

The values are intentionally blank in source control. Keep the academic connection separate from Laravel's default connection.

## Read-only verification

From the Laravel project directory, run:

    php artisan academic:inspect

The command checks the connection and counts rows only in a fixed allowlist of known tables. It does not print names, emails, IDs, password hashes, or other personal data, and performs no writes.

## Initial model mapping

Models use existing table names and the dedicated academic connection. The base model disables Laravel timestamps because the legacy schema has mixed timestamp conventions. Extend per-table mappings only after checking the actual SQL schema.
