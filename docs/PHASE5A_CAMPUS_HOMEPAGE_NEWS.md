# Homepage Kampus dan Berita (Phase 5A)

## Fitur
- Homepage publik: profil kampus, visi, misi, program studi aktif, berita terbit, kontak dan tautan PMB.
- Admin Web: form informasi kampus di Dashboard dan menu Berita untuk membuat, mengedit, menerbitkan, atau menarik berita.
- REST API publik: `GET /api/v1/public/home` mengembalikan profil kampus, program studi aktif, dan maksimal 10 berita yang sudah diterbitkan.

## Database
Database lama perlu dimigrasikan satu kali:
1. Backup database.
2. Import `backend/database/migrations/2026_11_09_campus_profile_and_news.sql` melalui phpMyAdmin.
3. Login Admin Web sebagai `super_admin` atau `admin_akademik`.
4. Isi profil kampus di Dashboard.
5. Tambahkan berita melalui menu Berita, lalu klik Terbitkan.

Migration membuat tabel `campus_profile` dan `news_posts`. Baris profil awal hanya berisi nama/tagline umum; alamat, kontak, visi-misi dan data resmi harus diverifikasi dan diisi pihak kampus. Tidak ada berita fiktif yang di-seed.

## Deploy homepage
Folder `website` berisi homepage PHP. Deploy folder ini sebagai document root website publik, atau sesuaikan jalur konfigurasi jika struktur hosting berbeda. Folder tersebut membaca konfigurasi database bersama dari `backend/config/database.local.php`. Admin Web tetap di document root `admin/public`, API di `backend/public`.

## API
`GET /api/v1/public/home` tidak memerlukan bearer token. Hanya berita dengan `published_at` yang sudah terisi dan waktunya telah tiba yang dikirim. Endpoint hanya mengeluarkan kolom konten publik.

## Catatan
- URL gambar berita harus URL HTTPS/HTTP tepercaya dan tetap di-host oleh pihak kampus/CDN.
- Sebelum produksi, tambahkan kebijakan moderasi konten, validasi URL gambar yang lebih ketat, backup, HTTPS, dan pengujian hosting.
