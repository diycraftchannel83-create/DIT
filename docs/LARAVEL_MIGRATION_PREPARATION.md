# Persiapan Migrasi Laravel — DIT

Status: tahap pra-implementasi. Dokumen ini tidak mengubah aplikasi PHP lama, database, atau Android.

## Keputusan arsitektur

- Laravel ditempatkan terpisah di `laravel-app/`; folder Android `app/` tetap utuh.
- Migrasi dilakukan bertahap. `admin/`, `backend/`, `website/`, dan `portal/` tetap menjadi sistem aktif sampai modul pengganti lulus pengujian.
- Laravel nantinya memakai database MySQL yang sama, tetapi pada fase awal hanya membaca skema lama. Jangan menjalankan migration yang membuat ulang tabel `users`, `students`, `lecturers`, PMB, atau tabel akademik.
- Rahasia koneksi Laravel disimpan di environment hosting / `.env`, tidak di Git.
- Android tetap memakai REST API; perubahan base URL hanya dilakukan setelah endpoint Laravel kompatibel dan sudah diuji.

## Temuan audit repository

1. Belum ditemukan `composer.json`, `composer.lock`, atau instalasi Laravel.
2. Backend saat ini PHP native dengan PDO dan API di `backend/public/index.php`.
3. Dashboard admin berada di `admin/public/index.php` dan menggunakan tabel database yang sama.
4. Skema inti dan perubahan database berada di `backend/database/schema.sql` serta `backend/database/migrations/*.sql`.
5. Folder root `app/` adalah proyek Android Gradle. Karena itu Laravel tidak boleh dipasang di root.
6. Workflow deploy saat ini mengunggah repository ke `/htdocs/` melalui FTP. Workflow sync dari hosting juga menyalin isi `/htdocs/` kembali ke repository. Keduanya harus diperbarui sebelum Laravel berisi kode rahasia atau artefak runtime.
7. Document root untuk Laravel harus diarahkan khusus ke `laravel-app/public` (idealnya subdomain terpisah). Jangan menjadikan root proyek Laravel sebagai document root dan jangan memindahkan `public/index.php` ke root proyek.

## Kompatibilitas ByetHost: belum terverifikasi pada akun aktif

Informasi publik ByetHost yang ditemukan menyebut PHP 8.3, MySQL 8, dan akses FTP/file manager. Itu belum membuktikan bahwa akun DIT memiliki seluruh ekstensi PHP yang diperlukan, kemampuan mengatur document root subdomain, izin tulis direktori, atau akses Composer/SSH/CLI.

Catatan percakapan sebelumnya menyebut PHP hosting pernah melaporkan 8.4.27. Versi tersebut memenuhi syarat versi Laravel 13, tetapi tetap perlu diuji pada domain/subdomain yang akan menjalankan aplikasi.

Laravel 13 membutuhkan PHP >= 8.3 serta ekstensi Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, dan XML. Koneksi database membutuhkan PDO MySQL.

## Urutan pemeriksaan sebelum memasang framework

1. Upload `laravel-app/public/preflight.php` hanya ke staging/subdomain, bukan mengganti website aktif.
2. Buka URL preflight melalui HTTPS dan pastikan semua pemeriksaan PHP/ekstensi/PDO MySQL lulus.
3. Pastikan panel ByetHost mengizinkan document root subdomain ke `/htdocs/laravel-app/public` atau sediakan direktori aplikasi di luar web root.
4. Pastikan `storage/` dan `bootstrap/cache/` dapat ditulis oleh PHP.
5. Pastikan dependensi Composer dapat dibangun di GitHub Actions/komputer lokal. Jangan mengandalkan Composer di hosting sebelum akses CLI terbukti tersedia.
6. Siapkan backup database dan uji restore sebelum Laravel diberi akses tulis.

## Gerbang keputusan

- **Lanjut Laravel 13 di ByetHost** hanya jika PHP >= 8.3, semua ekstensi wajib aktif, PDO MySQL tersedia, document root aman dapat diarahkan, dan direktori runtime dapat ditulis.
- **Jika document root/izin/ekstensi tidak mendukung**, jangan memaksakan deploy di folder publik. Pertahankan PHP native tetap aktif dan evaluasi VPS/hosting lain untuk Laravel.
- **Tidak ada migrasi data atau perubahan route produksi pada tahap pra-implementasi ini.**

## Rencana fase implementasi setelah preflight lulus

1. Buat skeleton Laravel 13 di `laravel-app/` melalui Composer, lalu build dependensi di CI.
2. Buat konfigurasi koneksi read-only ke database lama dan baseline migration tanpa membuat ulang tabel.
3. Implementasikan endpoint kompatibilitas (health, login, profile) dan uji terhadap data uji.
4. Migrasikan modul satu per satu dengan feature flag / route terpisah.
5. Alihkan Admin, PMB, website, dan Android hanya setelah uji regresi dan backup-restore berhasil.
