# Prototipe Android STIT Darul Ilmi

## Cakupan akses

Aplikasi menggunakan pola **public-first**:

- **Publik tanpa login:** profil kampus, visi/misi, program studi aktif, berita/pengumuman yang sudah dipublikasikan, alamat dan informasi kampus.
- **Mahasiswa (login):** rancangan menu KRS, KHS/IPK, jadwal, nilai, tagihan, dan profil.
- **Dosen (login):** rancangan menu jadwal mengajar, daftar kelas, presensi, dan input nilai.
- **Pimpinan (login):** rancangan dashboard dan ringkasan akademik sesuai role API.

Pengunjung publik tidak memiliki akun publik dan tidak bisa mengakses data akademik atau keuangan pribadi. Login menggunakan endpoint bearer token yang sudah ada.

## Teknologi

- Native Android, Kotlin, Jetpack Compose.
- Minimum Android API 24.
- applicationId: id.ac.darulilmi.portal.
- API PHP yang sudah ada; Android tidak pernah mengakses MySQL secara langsung.

## Konfigurasi API

Ubah API_BASE_URL di app/build.gradle.kts menjadi URL root backend yang menggunakan HTTPS, tanpa akhiran /api/v1. Contoh:

```kotlin
buildConfigField("String", "API_BASE_URL", "\"https://api.domain-kampus.ac.id/\"")
```

Aplikasi memanggil:

- GET /api/v1/public/home tanpa token.
- POST /api/v1/auth/login untuk mahasiswa/dosen/pimpinan yang telah dibuat oleh Admin Web.

Endpoint login mengembalikan bearer token. Dalam prototipe awal token hanya disimpan di memori selama aplikasi berjalan; belum ada penyimpanan aman, pemulihan sesi, atau tombol logout yang memanggil endpoint logout. Jangan gunakan ini sebagai rilis produksi.

## Konten yang dapat diedit melalui Admin Web

Konten profil kampus, kontak, program studi aktif, serta berita yang dipublikasikan sudah bersumber dari database melalui endpoint public home. Perubahan profil dan berita di Admin Web akan tampil pada pemuatan ulang aplikasi tanpa rebuild APK.

Logo, warna tema, banner, urutan menu, dan gambar berita belum terhubung ke pengaturan aplikasi pada tahap prototipe ini. Logo DI yang tampil adalah placeholder; bukan logo resmi kampus. Modul pengaturan tampilan aplikasi dan upload aset akan menjadi tahap berikutnya.

## Status prototipe

- UI publik dan pembacaan konten API: implementasi awal.
- Login: terhubung ke API login yang tersedia.
- Menu berdasarkan role: kerangka UI; belum mengambil data KRS/KHS/tagihan/presensi/nilai dari endpoint khusus Android.
- Demo akademik/keuangan: belum dibuat dan tidak menggunakan data palsu.
- Endpoint belum diuji ke database/hosting aktif karena konfigurasi hosting tidak tersedia di repository.
- Build debug dijalankan melalui GitHub Actions workflow .github/workflows/android-build.yml.

## Build lokal

Dengan JDK 17 dan Gradle 8.9:

```bash
gradle assembleDebug
```

APK: app/build/outputs/apk/debug/app-debug.apk.


## Foto gedung kampus yang dapat diedit

- Admin Web > Dashboard > Informasi Kampus menyediakan upload Foto Gedung Kampus.
- Format yang diterima: JPG, PNG, WebP; maksimum 5 MB.
- Admin dapat melihat pratinjau, mengganti foto, atau mencentang opsi hapus untuk kembali ke ilustrasi bawaan.
- Foto disimpan di `admin/public/uploads/campus/`; file upload diberi nama acak dan folder melarang eksekusi skrip PHP melalui Apache `.htaccess`.
- Public API `GET /api/v1/public/home` menyertakan `campus.hero_image_url`.
- Android memuat URL tersebut dengan Coil; perubahan gambar tampil saat aplikasi memuat ulang konten tanpa rebuild APK.

### Database

Database baru: impor versi terbaru `backend/database/schema.sql`.

Database lama: backup terlebih dahulu, lalu jalankan satu kali melalui phpMyAdmin:
`backend/database/migrations/2026_11_09_campus_hero_image.sql`.

Pastikan direktori `admin/public/uploads/campus` dapat ditulisi oleh PHP. Jika hosting memakai Nginx, tambahkan aturan server untuk melarang eksekusi file skrip di direktori upload karena `.htaccess` hanya berlaku pada Apache.
