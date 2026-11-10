# Notifikasi Email Ganti Password via Google Apps Script

## 1. Buat project Apps Script
1. Buka https://script.google.com/ lalu buat project baru.
2. Salin isi file `password-change-notification.gs` ke editor Apps Script.
3. Di **Project Settings > Script properties**, tambahkan:
   - Property: `SIAKAD_NOTIFY_TOKEN`
   - Value: token acak panjang (minimal 32 karakter). Simpan baik-baik; jangan masukkan token ke repository publik.

## 2. Deploy Web App
1. Klik **Deploy > New deployment**.
2. Pilih type **Web app**.
3. Execute as: **Me**.
4. Who has access: **Anyone** (endpoint dilindungi token rahasia).
5. Deploy, izinkan akses pengiriman email, lalu salin URL Web App yang berakhiran `/exec`.

Gunakan akun Google yang memang akan menjadi pengirim notifikasi. Kuota pengiriman email mengikuti batas harian Google Apps Script/MailApp.

## 3. Atur konfigurasi PHP di hosting
Edit file yang hanya ada di hosting dan tidak di-commit ke Git:
`backend/config/database.local.php`

URL GAS sudah disetel sebagai nilai bawaan di kode portal. Anda cukup menambahkan token berikut ke array konfigurasi:
```php
'password_notification_gas_token' => 'TOKEN_ACAK_YANG_SAMA',
```

Jangan taruh URL/token rahasia di file publik, JavaScript browser, atau repository. Pastikan file konfigurasi tetap di luar akses publik dan izin file-nya dibatasi.

## 4. Uji
- Pastikan kolom email akun mahasiswa berisi alamat yang benar.
- Login portal, buka Akun > Ganti Password, lalu ganti password.
- PHP menyimpan hash password dahulu, kemudian meminta GAS mengirim notifikasi.
- Jika email gagal, password tetap berhasil diganti dan portal menampilkan pemberitahuan bahwa email belum terkirim. Password tidak pernah dikirim ke GAS/email.

## Catatan
- Script memeriksa token bersama sebelum mengirim email.
- Endpoint tidak menerima password lama maupun password baru.
- Jika URL Web App diperbarui, perbarui nilai di konfigurasi hosting.
