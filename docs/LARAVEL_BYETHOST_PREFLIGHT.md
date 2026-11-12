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

1. Gunakan domain utama yang sama; tidak perlu membuat subdomain.
2. Uji Laravel di jalur terpisah `/laravel-preview/public/` sehingga website utama tetap di root domain.
3. Folder runtime/internal Laravel tetap terpisah dari folder publik legacy; jangan mengubah document root utama pada tahap uji.
4. Simpan `.env` dan kredensial di hosting secara manual, jangan commit atau mengunggahnya dari GitHub Actions.
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

## Status preflight dan gerbang berikutnya

- PHP 8.4.27 dan seluruh ekstensi wajib: PASS berdasarkan hasil uji hosting yang dikirim pengguna.
- PDO MySQL driver: PASS.
- `storage/` dan `bootstrap/cache/`: belum tersedia karena skeleton belum dipasang.
- Tidak menggunakan subdomain; staging akan memakai jalur folder terpisah pada domain utama.
- Workflow `Laravel 13 Build Preflight` disiapkan untuk membuat proyek bersih melalui Composer di GitHub Actions, memvalidasi dependency, dan menjalankan Artisan tanpa menyentuh hosting atau database produksi.

Sebelum deploy staging, HTTPS domain utama dan target FTP terisolasi tetap harus diverifikasi. `.env` harus dibuat manual di hosting dan tidak boleh dikirim ke GitHub.

Referensi: [Laravel 13 deployment requirements](https://laravel.com/docs/13.x/deployment).

## Tahap berikutnya setelah preflight lulus

- Buat skeleton Laravel 13 di `laravel-app/`.
- Atur koneksi ke database lama dalam mode read-only untuk fase pertama.
- Buat model yang mengikuti nama tabel dan primary key existing.
- Uji health check, login, profil, mahasiswa, dosen, kelas, dan mata kuliah dengan akun/data uji.
- Baru rencanakan perpindahan modul melalui feature flag dan backup-restore teruji.

Tidak ada migrasi data atau perubahan route produksi pada tahap ini.


## Deployment staging Laravel 13 (manual approval)

Workflow `Deploy Laravel Preview to ByetHost` membangun skeleton Laravel 13 beserta vendor melalui Composer, membuat APP_KEY staging, mematikan debug, memakai session/cache file, dan mengunggah hanya ke `/htdocs/laravel-preview/`. Workflow meminta konfirmasi manual dan tidak menargetkan root website. Database tidak dikonfigurasi dengan kredensial produksi. URL uji setelah deployment: `https://siakadstitdit.byethost24.com/laravel-preview/public/`.

Folder project dilindungi dengan `.htaccess` deny-all, sementara folder `public` diberi akses web. Deployment tidak menggunakan clean-slate agar tidak menghapus konten hosting. Pastikan hosting mendukung aturan Apache tersebut; bila tidak, hentikan pengujian dan jangan pindahkan document root utama.
