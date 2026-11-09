# Homepage Kampus dan Berita (Phase 5A)

## Fitur
- Homepage publik: profil kampus, visi, misi, program studi aktif, berita terbit, kontak dan tautan PMB.
- Admin Web: form informasi kampus di Dashboard dan menu Berita untuk membuat, mengedit, menerbitkan, atau menarik berita.
- REST API publik: `GET /api/v1/public/home` mengembalikan profil kampus, program studi aktif, dan maksimal 10 berita yang sudah diterbitkan.

## Database
## Instalasi database baru (satu kali import)
1. Buat database MySQL/MariaDB kosong di panel hosting.
2. Pilih database tersebut di phpMyAdmin.
3. Import **`backend/database/schema.sql`**. File ini sudah mencakup seluruh tabel akademik, profil kampus, dan berita. Tidak perlu import file migration terpisah.
4. Login Admin Web sebagai `super_admin` atau `admin_akademik`.
5. Isi profil kampus di Dashboard.
6. Tambahkan berita melalui menu Berita, lalu klik Terbitkan.

## Database lama yang sudah berjalan
Jika database lama sudah dipakai dan berisi data, jangan import ulang `schema.sql` karena tabel akan berbenturan. Gunakan migration lama yang sesuai hanya untuk menambahkan tabel profil kampus dan berita.

Migration membuat tabel `campus_profile` dan `news_posts`. Baris profil awal hanya berisi nama/tagline umum; alamat, kontak, visi-misi dan data resmi harus diverifikasi dan diisi pihak kampus. Tidak ada berita fiktif yang di-seed.

## Deploy homepage
Folder `website` berisi homepage PHP. Deploy folder ini sebagai document root website publik, atau sesuaikan jalur konfigurasi jika struktur hosting berbeda. Folder tersebut membaca konfigurasi database bersama dari `backend/config/database.local.php`. Admin Web tetap di document root `admin/public`, API di `backend/public`.

## API
`GET /api/v1/public/home` tidak memerlukan bearer token. Hanya berita dengan `published_at` yang sudah terisi dan waktunya telah tiba yang dikirim. Endpoint hanya mengeluarkan kolom konten publik.

## Catatan
- URL gambar berita harus URL HTTPS/HTTP tepercaya dan tetap di-host oleh pihak kampus/CDN.
- Sebelum produksi, tambahkan kebijakan moderasi konten, validasi URL gambar yang lebih ketat, backup, HTTPS, dan pengujian hosting.
