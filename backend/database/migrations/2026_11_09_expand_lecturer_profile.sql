-- Perluasan profil dosen untuk formulir biodata dan data akademik.
-- Backup database terlebih dahulu, lalu jalankan satu kali pada database produksi.
ALTER TABLE lecturers
  ADD COLUMN national_id VARCHAR(32) NULL AFTER photo_url,
  ADD COLUMN birth_place VARCHAR(100) NULL AFTER national_id,
  ADD COLUMN birth_date DATE NULL AFTER birth_place,
  ADD COLUMN gender ENUM('male','female') NULL AFTER birth_date,
  ADD COLUMN religion VARCHAR(40) NULL AFTER gender,
  ADD COLUMN marital_status VARCHAR(30) NULL AFTER religion,
  ADD COLUMN province VARCHAR(100) NULL AFTER marital_status,
  ADD COLUMN city VARCHAR(100) NULL AFTER province,
  ADD COLUMN district VARCHAR(100) NULL AFTER city,
  ADD COLUMN village VARCHAR(100) NULL AFTER district,
  ADD COLUMN postal_code VARCHAR(10) NULL AFTER village,
  ADD COLUMN address TEXT NULL AFTER postal_code,
  ADD COLUMN emergency_contact_name VARCHAR(160) NULL AFTER address,
  ADD COLUMN emergency_contact_phone VARCHAR(30) NULL AFTER emergency_contact_name,
  ADD COLUMN study_program_id BIGINT UNSIGNED NULL AFTER emergency_contact_phone,
  ADD COLUMN academic_rank VARCHAR(100) NULL AFTER study_program_id,
  ADD COLUMN functional_position VARCHAR(120) NULL AFTER academic_rank,
  ADD COLUMN employment_status VARCHAR(40) NULL AFTER functional_position,
  ADD COLUMN employment_type VARCHAR(40) NULL AFTER employment_status,
  ADD COLUMN employment_start_date DATE NULL AFTER employment_type,
  ADD COLUMN highest_education VARCHAR(40) NULL AFTER employment_start_date,
  ADD COLUMN expertise VARCHAR(255) NULL AFTER highest_education,
  ADD COLUMN certification_number VARCHAR(100) NULL AFTER expertise,
  ADD CONSTRAINT fk_lecturers_study_program
    FOREIGN KEY (study_program_id) REFERENCES study_programs(id);
