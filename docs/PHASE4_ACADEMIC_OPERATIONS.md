# Phase 4 — KRS, Nilai, KHS, IPK, dan Presensi

## Fitur yang ditambahkan
- Admin akademik dapat membuat pengajuan KRS untuk mahasiswa dan menambahkan beberapa kelas/mata kuliah ke KRS yang sama selama status masih submitted/draft.
- Admin akademik dapat menyetujui atau menolak KRS yang masih submitted.
- Dosen dapat login ke Admin Web dan hanya dapat menginput nilai serta presensi pada kelas yang ditugaskan kepadanya.
- Input nilai hanya diizinkan untuk mahasiswa yang terdaftar melalui KRS berstatus approved.
- Nilai dihitung menjadi huruf dan bobot awal, disimpan sebagai draft, lalu dapat dipublikasikan admin akademik.
- Presensi per mahasiswa, kelas, dan pertemuan; pencatatan ulang memperbarui status pada pertemuan yang sama.
- KHS menampilkan total SKS dan IP per semester dari nilai yang telah dipublikasikan.
- Rekap IPK menghitung rerata berbobot SKS dari seluruh nilai yang telah dipublikasikan.

## Skala nilai sementara
| Nilai angka | Huruf | Bobot |
|---|---|---:|
| 85–100 | A | 4.0 |
| 80–84.99 | AB | 3.5 |
| 75–79.99 | B | 3.0 |
| 70–74.99 | BC | 2.5 |
| 65–69.99 | C | 2.0 |
| 50–64.99 | D | 1.0 |
| 0–49.99 | E | 0.0 |

Skala ini hanya nilai awal implementasi. Pihak kampus harus mengonfirmasi dan mengubah rentang nilai/bobot sesuai pedoman akademik resmi sebelum dipakai untuk transkrip atau keputusan akademik.

## Batasan penting
- KRS pada Admin Web dibuat oleh petugas akademik sebagai alur administrasi awal; antarmuka pengajuan mandiri mahasiswa akan dibuat di Android Student App.
- Saat ini satu KRS dapat memiliki beberapa kelas melalui pengajuan berulang, tetapi belum ada batas maksimum SKS, prasyarat mata kuliah, validasi benturan jadwal, maupun validasi kapasitas kelas.
- Input nilai dilakukan per mahasiswa, belum berupa lembar nilai massal.
- Presensi dicatat per mahasiswa, belum tersedia daftar hadir massal atau rekap persentase kehadiran.
- KHS/IPK menghitung hanya nilai yang sudah dipublikasikan.
- Belum ada uji deployment ByetHost, uji beban, atau audit keamanan produksi.

## Pengujian staging
1. Buat semester, kelas kuliah, dosen pengampu, mahasiswa.
2. Buat KRS mahasiswa dan tambahkan lebih dari satu kelas pada semester yang sama.
3. Setujui KRS; pastikan KRS yang sudah disetujui tidak bisa ditambah mata kuliah lagi.
4. Login sebagai dosen pengampu; coba input nilai mahasiswa pada kelas sendiri dan kelas dosen lain.
5. Pastikan mahasiswa tanpa KRS approved ditolak saat input nilai dan presensi.
6. Publikasikan nilai sebagai admin akademik.
7. Verifikasi KHS menghitung IP semester dengan bobot SKS.
8. Verifikasi IPK menghitung semua nilai publikasi berbobot SKS.
9. Catat presensi pada pertemuan 1, lalu ubah status pertemuan yang sama; pastikan hanya satu baris tercatat.
10. Uji akses tiap role dan CSRF pada semua form.
