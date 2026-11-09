-- Jalankan satu kali pada database lama setelah backup.
ALTER TABLE campus_profile
  ADD COLUMN hero_image_url VARCHAR(500) NULL AFTER pmb_url;
