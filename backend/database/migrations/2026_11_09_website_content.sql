-- Backup database before running this migration.
-- Run once on an existing STIT Darul Ilmi database.
CREATE TABLE IF NOT EXISTS website_content (
 content_key VARCHAR(80) NOT NULL PRIMARY KEY,
 content_value LONGTEXT NOT NULL,
 updated_by BIGINT UNSIGNED NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_website_content_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
