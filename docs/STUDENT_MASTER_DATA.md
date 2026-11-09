# Pengembangan Data Master Mahasiswa

## Field tambahan
Data mahasiswa kini mencakup:
- Identitas akademik: NIM, program studi, tahun masuk, status.
- Kontak: telepon, alamat, provinsi, kabupaten/kota, kecamatan, desa/kelurahan, kode pos.
- Identitas pribadi: NIK opsional, tempat/tanggal lahir, jenis kelamin, agama opsional, status perkawinan.
- Keluarga: nama ayah, nama ibu, wali dan telepon wali.
- Kontak darurat: nama dan nomor telepon.
- Administrasi: jalur masuk, tanggal registrasi, URL foto.

NIK dan data keluarga bersifat opsional. Kampus sebaiknya hanya mengumpulkan data yang benar-benar dibutuhkan, membatasi akses petugas, dan menghindari menampilkan NIK atau data keluarga pada daftar umum.

## Instalasi database
- Database baru: import ulang `backend/database/schema.sql` ke database kosong. Semua kolom baru sudah termasuk.
- Database lama yang sudah berisi data: backup terlebih dahulu, lalu jalankan `backend/database/migrations/2026_11_09_expand_student_profile.sql` satu kali. Jangan import ulang schema penuh ke database aktif.

## Admin Web
Pada menu Mahasiswa, formulir pembuatan mahasiswa menyediakan bagian data pribadi/alamat/keluarga opsional. Pembuatan akun dan profil mahasiswa tetap dilakukan dalam satu transaksi.

## API
Endpoint `GET /api/v1/me` menyertakan objek `student` dengan field profil tambahan jika akun yang login merupakan mahasiswa. Field sensitif tidak disertakan dalam daftar publik.

## Batasan saat ini
- Foto disimpan sebagai URL HTTP/HTTPS; unggah file foto belum dibuat.
- Belum tersedia fitur edit profil, validasi referensi wilayah, impor Excel, atau pengaturan field wajib sesuai kebijakan kampus.
- Sebelum produksi, tetapkan dasar pengumpulan, retensi, hak akses, dan perlindungan data pribadi.


## Fitur pengelolaan Admin Web
- Cari mahasiswa berdasarkan NIM, nama, atau email.
- Buka tautan Edit pada daftar mahasiswa untuk memperbarui akun dan data profil.
- Ekspor daftar mahasiswa ke CSV yang dapat dibuka di Excel atau LibreOffice.
- Foto dapat diunggah sebagai JPG, PNG, atau WebP maksimal 2 MB. Nama file disimpan secara acak pada `admin/public/uploads/students/`; direktori ini menonaktifkan listing dan akses eksekusi file PHP melalui aturan Apache.
- Foto lama dapat dipertahankan dengan tidak memilih unggahan baru. Kolom URL foto juga menerima URL HTTP/HTTPS.

## Cara memakai
1. Login sebagai `super_admin` atau `admin_akademik`.
2. Buka menu Mahasiswa.
3. Gunakan pencarian untuk menemukan mahasiswa, lalu pilih Edit.
4. Perbarui field yang dibutuhkan dan simpan.
5. Untuk unduh data, pilih Ekspor CSV.

## Catatan keamanan foto
Batas unggah aplikasi 2 MB dan MIME diperiksa menggunakan Fileinfo. Pastikan hosting menyediakan ekstensi PHP Fileinfo, mengizinkan upload, dan folder upload dapat ditulis oleh PHP. Aturan `.htaccess` berlaku pada Apache; untuk Nginx, pasang aturan server yang setara agar file skrip tidak dapat dieksekusi. Jangan unggah dokumen identitas ke folder foto publik.
