-- Phase 5A: Profil Kampus dan Berita Publik
-- Jalankan satu kali pada database yang sudah mengimpor backend/database/schema.sql.
CREATE TABLE IF NOT EXISTS campus_profile (
 id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
 tagline VARCHAR(220) NULL,
 about TEXT NULL,
 vision TEXT NULL,
 mission TEXT NULL,
 address VARCHAR(500) NULL,
 phone VARCHAR(60) NULL,
 email VARCHAR(190) NULL,
 website VARCHAR(255) NULL,
 pmb_url VARCHAR(255) NULL,
 updated_by BIGINT UNSIGNED NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_campus_profile_user FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO campus_profile(id,tagline,about)
VALUES(1,'Portal Informasi dan Akademik STIT Darul Ilmi Tasikmalaya','STIT Darul Ilmi Tasikmalaya menyediakan layanan informasi kampus dan akademik melalui portal terpadu. Lengkapi profil ini dengan sejarah, keunggulan, dan informasi resmi kampus.')
ON DUPLICATE KEY UPDATE id=VALUES(id);

CREATE TABLE IF NOT EXISTS news_posts (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 excerpt VARCHAR(400) NULL,
 body LONGTEXT NOT NULL,
 category VARCHAR(60) NOT NULL DEFAULT 'Berita',
 image_url VARCHAR(500) NULL,
 published_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_news_publication(published_at,created_at),
 CONSTRAINT fk_news_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
