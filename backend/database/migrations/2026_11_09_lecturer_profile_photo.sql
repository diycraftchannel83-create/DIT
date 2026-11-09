-- Tambahkan foto profil untuk data dosen.
-- Jalankan satu kali pada database produksi setelah backup.
ALTER TABLE lecturers
  ADD COLUMN photo_url VARCHAR(500) NULL AFTER phone;
