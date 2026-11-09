-- Upgrade untuk database STIT Academic Portal yang sudah terpasang.
-- Jalankan SATU KALI pada database lama. Jangan jalankan jika kolom-kolom ini sudah ada.
ALTER TABLE students
 ADD COLUMN national_id VARCHAR(32) NULL AFTER address,
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
 ADD COLUMN father_name VARCHAR(160) NULL AFTER postal_code,
 ADD COLUMN mother_name VARCHAR(160) NULL AFTER father_name,
 ADD COLUMN guardian_name VARCHAR(160) NULL AFTER mother_name,
 ADD COLUMN guardian_phone VARCHAR(30) NULL AFTER guardian_name,
 ADD COLUMN emergency_contact_name VARCHAR(160) NULL AFTER guardian_phone,
 ADD COLUMN emergency_contact_phone VARCHAR(30) NULL AFTER emergency_contact_name,
 ADD COLUMN admission_pathway VARCHAR(60) NULL AFTER emergency_contact_phone,
 ADD COLUMN registration_date DATE NULL AFTER admission_pathway,
 ADD COLUMN photo_url VARCHAR(500) NULL AFTER registration_date;
