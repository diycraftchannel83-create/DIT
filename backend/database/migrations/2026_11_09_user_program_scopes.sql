-- Jalankan satu kali pada database STIT Academic Portal yang sudah terpasang.
-- Admin Akademik harus menetapkan satu program studi untuk setiap akun Ketua Prodi.
CREATE TABLE IF NOT EXISTS user_program_scopes (
 user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 study_program_id BIGINT UNSIGNED NOT NULL,
 assigned_by BIGINT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_scope_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 CONSTRAINT fk_scope_program FOREIGN KEY(study_program_id) REFERENCES study_programs(id),
 CONSTRAINT fk_scope_assigner FOREIGN KEY(assigned_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_scope_program(study_program_id)
) ENGINE=InnoDB;
