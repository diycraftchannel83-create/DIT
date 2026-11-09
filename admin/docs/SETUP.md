# Admin Web Portal — Setup

## Struktur
- Aplikasi admin berada di `admin/public`.
- Admin memakai database MySQL yang sama dengan REST API Phase 1.
- Kredensial dibaca dari `backend/config/database.local.php`; jangan menyalin kredensial ke file publik atau Git.
- Disarankan pasang subdomain khusus, misalnya `admin.domain-kampus.ac.id`, dengan document root diarahkan ke `admin/public`.

## Persyaratan
- PHP 8.0+ dengan PDO MySQL.
- MySQL/MariaDB yang sudah diisi schema `backend/database/schema.sql`.
- HTTPS aktif.
- Apache dengan mod_rewrite jika memakai aturan `.htaccess`.

## Deploy
1. Upload seluruh repository ke hosting.
2. Buat database MySQL melalui panel hosting.
3. Import `backend/database/schema.sql` melalui phpMyAdmin.
4. Salin `backend/config/database.example.php` menjadi `backend/config/database.local.php`.
5. Isi host, nama database, username, password, dan app_url sesuai hosting.
6. Arahkan document root subdomain admin ke folder `admin/public`. Jangan jadikan folder `backend/config` sebagai folder publik.
7. Aktifkan HTTPS lalu buka domain admin.
8. Buat akun super admin dengan prosedur pada `backend/docs/ADMIN_SETUP.md`; tidak ada username/password bawaan.

Jika hosting tidak mendukung document root subfolder, minta penyedia hosting mengatur subdomain atau gunakan konfigurasi virtual host yang mengarah ke `admin/public`. Hindari memindahkan file konfigurasi rahasia ke public_html.

## Fitur tahap ini
- Login berbasis akun tabel `users` dan verifikasi `password_hash`.
- Session cookie HttpOnly, SameSite=Lax, Secure saat HTTPS.
- Token CSRF untuk semua form POST.
- Session ID diregenerasi setelah login.
- Dashboard ringkasan mahasiswa, dosen, program studi, mata kuliah, kelas, dan PMB.
- Daftar data program studi, tahun akademik, semester, mahasiswa, dosen, mata kuliah, kelas kuliah, dan PMB.
- Form tambah program studi, tahun akademik, dan semester.
- Akses menu pengelolaan akademik dibatasi untuk `super_admin` dan `admin_akademik`.

## Batasan yang masih harus diselesaikan
- Belum ada fitur edit/hapus dan pagination untuk semua modul.
- Belum ada CRUD mahasiswa, dosen, mata kuliah, kelas, jadwal, nilai, pembayaran, atau verifikasi PMB.
- Audit log belum diimplementasikan pada Admin Web.
- Belum ada rate limiting login; pasang proteksi tambahan di hosting/WAF sebelum produksi.
- Belum diuji pada akun ByetHost dan belum dilakukan pengujian end-to-end.
- Periksa kembali dukungan versi PHP dan tipe kolom JSON pada MySQL hosting.

Jangan gunakan portal ini untuk data mahasiswa riil sebelum uji keamanan, otorisasi, backup-restore, dan deployment HTTPS selesai.
