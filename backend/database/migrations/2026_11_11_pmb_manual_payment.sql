-- PMB pembayaran manual: admin menentukan nominal dan memverifikasi pembayaran.
-- Jalankan satu kali melalui Admin > Migrasi Database.

ALTER TABLE pmb_applicants
 ADD COLUMN registration_fee_amount DECIMAL(12,2) NOT NULL DEFAULT 200000 AFTER document_status,
 ADD COLUMN payment_status ENUM('unpaid','proof_submitted','verified','rejected','waived') NOT NULL DEFAULT 'unpaid' AFTER registration_fee_amount,
 ADD COLUMN payment_method ENUM('bank_transfer','cash','other') NULL AFTER payment_status,
 ADD COLUMN payment_reference VARCHAR(120) NULL AFTER payment_method,
 ADD COLUMN payment_note VARCHAR(500) NULL AFTER payment_reference,
 ADD COLUMN payment_verified_by BIGINT UNSIGNED NULL AFTER payment_note,
 ADD COLUMN payment_verified_at DATETIME NULL AFTER payment_verified_by,
 ADD CONSTRAINT fk_pmb_payment_verified_by FOREIGN KEY(payment_verified_by) REFERENCES users(id) ON DELETE SET NULL;

CREATE TABLE pmb_payment_records (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 applicant_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 payment_method ENUM('bank_transfer','cash','other') NOT NULL,
 reference_number VARCHAR(120) NULL,
 proof_storage_path VARCHAR(500) NULL,
 proof_original_name VARCHAR(255) NULL,
 proof_mime_type VARCHAR(100) NULL,
 proof_file_size BIGINT UNSIGNED NULL,
 status ENUM('submitted','verified','rejected') NOT NULL DEFAULT 'submitted',
 admin_note VARCHAR(500) NULL,
 submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 verified_by BIGINT UNSIGNED NULL,
 verified_at DATETIME NULL,
 CONSTRAINT fk_pmb_payment_records_applicant FOREIGN KEY(applicant_id) REFERENCES pmb_applicants(id) ON DELETE CASCADE,
 CONSTRAINT fk_pmb_payment_records_verifier FOREIGN KEY(verified_by) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_pmb_payment_applicant_status(applicant_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
