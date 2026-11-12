# Persiapan Laravel 13 — STIT Darul Ilmi

Status: preflight staging. Perubahan ini tidak mengganti website aktif dan tidak mengubah skema atau isi database.

## Hasil audit repository

- Aplikasi saat ini menggunakan PHP native, PDO MySQL, dan REST API sendiri.
- Admin Web, Website, Portal Mahasiswa/Dosen, dan Android masih aktif pada struktur masing-masing.
- Belum ada `composer.json` atau `composer.lock` Laravel pada branch utama saat audit.
- Folder root `app/` merupakan proyek Android Gradle. Laravel harus ditempatkan di `laravel-app/`, bukan root.
- Skema akademik berada di `backend/database/schema.sql`; perubahan bertahap berada di `backend/database/migrations/`.
- Admin Web telah memiliki registry `schema_migrations`. Jangan menggunakan `migrate:fresh`, `db:wipe`, atau mengimpor ulang schema penuh pada database aktif.

## Arsitektur staging yang disepakati

1. Buat subdomain staging di panel ByetHost.
2. Atur document root subdomain langsung ke `laravel-app/public` (bukan `laravel-app`).
3. Website utama tetap memakai document root dan deployment lama.
4. Simpan `.env` dan kode internal Laravel di luar document root publik.
5. Laravel pertama kali hanya membaca database lama. Tidak ada operasi tulis atau migrasi skema selama tahap kompatibilitas.
6. Android dan REST API lama tidak dialihkan sampai endpoint pengganti lulus pengujian.

## Pemeriksaan hosting

File sementara `laravel-app/public/preflight.php` memeriksa versi PHP dan ekstensi Laravel tanpa membaca konfigurasi rahasia atau menghubungkan ke database.

Langkah pemeriksaan manual:
1. Buat subdomain staging dan pastikan document root menunjuk ke `laravel-app/public`.
2. Unggah file preflight ke document root subdomain sebagai `preflight.php`.
3. Buka URL HTTPS `https://SUBDOMAIN-ANDA/preflight.php`.
4. Pastikan PHP minimal 8.3, seluruh ekstensi wajib aktif, dan PDO MySQL tersedia.
5. Setelah skeleton Laravel dipasang, periksa izin tulis `storage/` dan `bootstrap/cache/`.
6. Pastikan Composer dapat dijalankan melalui SSH/CLI atau dependensi dapat dibangun di GitHub Actions dan diunggah secara aman.
7. Hapus `preflight.php` setelah pemeriksaan selesai.

Jangan mengirim username, password, isi `.env`, atau kredensial FTP ke percakapan.

## Proteksi deployment legacy

Workflow FTP lama tetap mengirim aplikasi PHP aktif ke `/htdocs/`, tetapi sekarang mengecualikan `laravel-app/`. Workflow sinkronisasi dari hosting juga mengecualikan folder tersebut agar source staging tidak tertimpa hasil unduhan website lama.

Belum ada workflow deployment Laravel otomatis. Itu baru dibuat setelah lokasi root subdomain staging dan target FTP dikonfirmasi agar tidak menimpa website utama.

## Gerbang sebelum membuat skeleton Laravel

Lanjutkan hanya setelah:
- PHP >= 8.3 dan semua ekstensi Laravel 13 tersedia.
- PDO MySQL tersedia.
- Document root subdomain benar-benar dapat diarahkan ke folder `public`.
- HTTPS aktif.
- `storage/` dan `bootstrap/cache/` dapat ditulis.
- Cara membangun/memasang Composer dependencies telah dipastikan.

Referensi: [Laravel 13 deployment requirements](https://laravel.com/docs/13.x/deployment).

## Tahap berikutnya setelah preflight lulus

- Buat skeleton Laravel 13 di `laravel-app/`.
- Atur koneksi ke database lama dalam mode read-only untuk fase pertama.
- Buat model yang mengikuti nama tabel dan primary key existing.
- Uji health check, login, profil, mahasiswa, dosen, kelas, dan mata kuliah dengan akun/data uji.
- Baru rencanakan perpindahan modul melalui feature flag dan backup-restore teruji.

Tidak ada migrasi data atau perubahan route produksi pada tahap ini.
