-- Jalankan satu kali pada database STIT Academic Portal yang sudah terpasang.
-- Tabel ini menyimpan penghitung percobaan login Admin Web, bukan password.
CREATE TABLE IF NOT EXISTS admin_login_attempts (
 attempt_key CHAR(64) NOT NULL PRIMARY KEY,
 attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 window_started_at DATETIME NOT NULL,
 blocked_until DATETIME NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_admin_login_blocked(blocked_until)
) ENGINE=InnoDB;
