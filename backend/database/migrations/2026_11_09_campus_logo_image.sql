-- Backup database before running this migration.
ALTER TABLE campus_profile
  ADD COLUMN logo_image_url VARCHAR(500) NULL AFTER hero_image_url;
