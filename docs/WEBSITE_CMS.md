# Website Resmi STIT Darul Ilmi Tasikmalaya

## Perubahan desain

Website publik telah disusun ulang dengan identitas hijau muda:
- Header sticky, logo resmi, navigasi desktop dan menu hamburger mobile.
- Hero dengan foto gedung kampus dari `campus_profile.hero_image_url`.
- Statistik kampus (hanya tampil jika diisi admin).
- Profil, sejarah, visi-misi.
- Program studi dari tabel `study_programs`.
- Keunggulan kampus.
- Informasi PMB.
- Berita terbit dari `news_posts`.
- Galeri dan testimoni (hanya tampil jika diisi admin).
- CTA, kontak, WhatsApp dan tautan sosial.
- SEO metadata, Open Graph, structured data, robots.txt, sitemap.php.
- Responsive layout desktop, tablet, dan ponsel.

## Pengaturan melalui Admin Web

Login Admin Web, buka Dashboard:
1. **Informasi Kampus**: tagline, profil, visi, misi, alamat, telepon, email, website, link PMB dan upload foto gedung.
2. **Konten Website Resmi**: sejarah, judul/informasi PMB, WhatsApp, tautan sosial, statistik, keunggulan, galeri dan testimoni.

Konten daftar (statistik, keunggulan, galeri, testimoni) saat ini menggunakan JSON array. Contoh:

```json
[
  {"label":"Mahasiswa","value":"Isi angka resmi"},
  {"label":"Dosen","value":"Isi angka resmi"}
]
```

Keunggulan:
```json
[
  {"icon":"✦","title":"Pendidikan Berbasis Nilai Islam","text":"Deskripsi resmi yang disetujui kampus"}
]
```

Galeri:
```json
[
  {"url":"https://domain-kampus.ac.id/uploads/gedung.jpg","alt":"Gedung STIT Darul Ilmi","caption":"Gedung kampus"}
]
```

Testimoni (hanya dengan persetujuan dan verifikasi):
```json
[
  {"quote":"Testimoni yang sudah disetujui untuk dipublikasikan","name":"Nama yang menyetujui","role":"Alumni"}
]
```

## Database

Database baru: gunakan versi terbaru `backend/database/schema.sql`.

Database yang sudah berjalan: backup terlebih dahulu, lalu jalankan satu kali:
`backend/database/migrations/2026_11_09_website_content.sql`.

Foto gedung dikelola dari form Informasi Kampus yang telah tersedia. Direktori `admin/public/uploads/campus` harus writable oleh PHP. Jika hosting menggunakan Nginx, larang eksekusi skrip di folder upload melalui konfigurasi Nginx karena .htaccess hanya berlaku pada Apache.

## Catatan keakuratan

Jangan isi statistik, jadwal/biaya PMB, akreditasi, prestasi, sejarah, maupun testimoni dengan data rekaan. Bagian statistik dan testimoni otomatis tidak ditampilkan jika belum ada konten yang diverifikasi. Periksa seluruh informasi resmi dan tautan PMB sebelum website diumumkan.
