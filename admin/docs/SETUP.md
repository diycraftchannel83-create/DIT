# Admin Web Portal — Setup

## Struktur dan persyaratan
- Aplikasi admin berada di `admin/public` dan memakai database MySQL/MariaDB yang sama dengan REST API.
- Kredensial dibaca dari `backend/config/database.local.php`; jangan commit kredensial ke Git.
- PHP 8+ dengan PDO MySQL dan Fileinfo (untuk unggah foto).
- HTTPS aktif. Disarankan subdomain khusus dengan document root diarahkan ke `admin/public`.

## Deploy
1. Upload repository ke hosting.
2. Buat database MySQL melalui panel hosting.
3. Untuk database baru yang kosong, import `backend/database/schema.sql` satu kali.
4. Untuk database lama, backup terlebih dahulu dan jalankan hanya migration yang belum diterapkan. Perluasan profil mahasiswa menggunakan `backend/database/migrations/2026_11_09_expand_student_profile.sql`; jalankan satu kali hanya jika kolom tambahan belum tersedia.
5. Salin `backend/config/database.example.php` menjadi `backend/config/database.local.php`, lalu isi host, database, username, password, dan URL aplikasi.
6. Pastikan folder konfigurasi tidak dapat diakses publik. Arahkan document root admin ke `admin/public`.
7. Aktifkan HTTPS, buat akun super admin mengikuti `backend/docs/ADMIN_SETUP.md`, lalu uji login dan akses per role.

Jangan mengimpor ulang schema penuh ke database aktif karena dapat menimbulkan konflik tabel dan berisiko terhadap data.

## Fitur yang tersedia
- Login berbasis tabel `users` dan `password_hash`, session cookie HttpOnly/SameSite serta Secure saat HTTPS terdeteksi.
- CSRF token untuk form POST dan regenerasi session ID setelah login.
- Dashboard ringkasan data kampus.
- Pengelolaan profil kampus dan berita publik (draft/publikasi).
- Pengelolaan program studi, tahun akademik, semester, mahasiswa, dosen, akun operasional, mata kuliah, kelas dan jadwal.
- Mahasiswa: tambah, edit profil, pencarian NIM/nama/email, ekspor CSV dan unggah foto JPG/PNG/WebP maksimal 2 MB.
- Operasi KRS, persetujuan KRS, input/publikasi nilai, presensi, KHS dan IPK sesuai batas role yang diterapkan aplikasi.

## Batasan dan keamanan yang masih perlu dituntaskan
- Belum ada pengujian end-to-end pada hosting/ByetHost atau verifikasi database produksi.
- Login belum memiliki rate limiting atau proteksi brute-force bawaan.
- Audit log belum terhubung ke seluruh aksi admin.
- Belum semua modul memiliki edit/hapus, pagination, impor massal atau pencarian.
- Validasi KRS belum mencakup seluruh aturan akademik seperti batas SKS, prasyarat, bentrok jadwal dan kapasitas kelas.
- Kebijakan IPK untuk mata kuliah yang diulang harus disahkan kampus.
- Otorisasi dan pembatasan data per program studi harus diuji dengan akun setiap role.
- Untuk Nginx, pasang aturan setara dengan `.htaccess` agar skrip di folder unggahan tidak dapat dieksekusi.
- Terapkan backup dan uji restore, masa sesi, reset password, serta hardening hosting sebelum data riil digunakan.

NIK, agama, data keluarga, dan kontak darurat bersifat sensitif atau pribadi. Kumpulkan hanya bila diperlukan dan batasi aksesnya sesuai tugas. Jangan gunakan portal untuk data mahasiswa riil sebelum pemeriksaan keamanan, otorisasi, backup-restore, dan deployment HTTPS selesai.
