-- Menyimpan gambar EKTM yang diunggah admin untuk ditampilkan pada portal mahasiswa.
ALTER TABLE campus_profile
  ADD COLUMN ektm_image_url VARCHAR(500) NULL AFTER logo_image_url;
