-- Import setelah memilih database tujuan di phpMyAdmin.
CREATE TABLE roles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(40) NOT NULL UNIQUE,
 description VARCHAR(160) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 role_id BIGINT UNSIGNED NOT NULL,
 username VARCHAR(100) NOT NULL UNIQUE,
 full_name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 status ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
 last_login_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_users_role FOREIGN KEY(role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

CREATE TABLE personal_access_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_tokens_user_expiry(user_id,expires_at),
 CONSTRAINT fk_tokens_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE study_programs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(30) NOT NULL UNIQUE,
 name VARCHAR(160) NOT NULL,
 degree_level VARCHAR(20) NOT NULL DEFAULT 'S1',
 is_active BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE students (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 nim VARCHAR(40) NOT NULL UNIQUE,
 study_program_id BIGINT UNSIGNED NULL,
 entry_year SMALLINT UNSIGNED NULL,
 phone VARCHAR(30) NULL,
 address TEXT NULL,
 national_id VARCHAR(32) NULL,
 birth_place VARCHAR(100) NULL,
 birth_date DATE NULL,
 gender ENUM('male','female') NULL,
 religion VARCHAR(40) NULL,
 marital_status VARCHAR(30) NULL,
 province VARCHAR(100) NULL,
 city VARCHAR(100) NULL,
 district VARCHAR(100) NULL,
 village VARCHAR(100) NULL,
 postal_code VARCHAR(10) NULL,
 father_name VARCHAR(160) NULL,
 mother_name VARCHAR(160) NULL,
 guardian_name VARCHAR(160) NULL,
 guardian_phone VARCHAR(30) NULL,
 emergency_contact_name VARCHAR(160) NULL,
 emergency_contact_phone VARCHAR(30) NULL,
 admission_pathway VARCHAR(60) NULL,
 registration_date DATE NULL,
 photo_url VARCHAR(500) NULL,
 status ENUM('active','leave','graduated','withdrawn') NOT NULL DEFAULT 'active',
 CONSTRAINT fk_students_user FOREIGN KEY(user_id) REFERENCES users(id),
 CONSTRAINT fk_students_program FOREIGN KEY(study_program_id) REFERENCES study_programs(id)
) ENGINE=InnoDB;

CREATE TABLE lecturers (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL UNIQUE,
 lecturer_number VARCHAR(40) NULL UNIQUE,
 phone VARCHAR(30) NULL,
 CONSTRAINT fk_lecturers_user FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE academic_years (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(20) NOT NULL UNIQUE,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 is_active BOOLEAN NOT NULL DEFAULT FALSE
) ENGINE=InnoDB;

CREATE TABLE semesters (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 academic_year_id BIGINT UNSIGNED NOT NULL,
 name ENUM('Ganjil','Genap','Antara') NOT NULL,
 start_date DATE NOT NULL,
 end_date DATE NOT NULL,
 is_active BOOLEAN NOT NULL DEFAULT FALSE,
 CONSTRAINT fk_semesters_year FOREIGN KEY(academic_year_id) REFERENCES academic_years(id),
 UNIQUE KEY uq_semester_year_name(academic_year_id,name)
) ENGINE=InnoDB;

CREATE TABLE courses (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 study_program_id BIGINT UNSIGNED NOT NULL,
 code VARCHAR(30) NOT NULL UNIQUE,
 name VARCHAR(180) NOT NULL,
 credits TINYINT UNSIGNED NOT NULL,
 recommended_semester TINYINT UNSIGNED NULL,
 is_active BOOLEAN NOT NULL DEFAULT TRUE,
 CONSTRAINT fk_courses_program FOREIGN KEY(study_program_id) REFERENCES study_programs(id)
) ENGINE=InnoDB;

CREATE TABLE course_classes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_id BIGINT UNSIGNED NOT NULL,
 semester_id BIGINT UNSIGNED NOT NULL,
 lecturer_id BIGINT UNSIGNED NULL,
 class_name VARCHAR(40) NOT NULL,
 capacity SMALLINT UNSIGNED NULL,
 CONSTRAINT fk_class_course FOREIGN KEY(course_id) REFERENCES courses(id),
 CONSTRAINT fk_class_semester FOREIGN KEY(semester_id) REFERENCES semesters(id),
 CONSTRAINT fk_class_lecturer FOREIGN KEY(lecturer_id) REFERENCES lecturers(id)
) ENGINE=InnoDB;

CREATE TABLE class_schedules (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 course_class_id BIGINT UNSIGNED NOT NULL,
 day_of_week TINYINT UNSIGNED NOT NULL COMMENT '1=Senin sampai 7=Minggu',
 start_time TIME NOT NULL,
 end_time TIME NOT NULL,
 room VARCHAR(100) NULL,
 CONSTRAINT fk_schedule_class FOREIGN KEY(course_class_id) REFERENCES course_classes(id)
) ENGINE=InnoDB;

CREATE TABLE study_plans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id BIGINT UNSIGNED NOT NULL,
 semester_id BIGINT UNSIGNED NOT NULL,
 status ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
 submitted_at DATETIME NULL,
 approved_by BIGINT UNSIGNED NULL,
 approved_at DATETIME NULL,
 CONSTRAINT fk_plans_student FOREIGN KEY(student_id) REFERENCES students(id),
 CONSTRAINT fk_plans_semester FOREIGN KEY(semester_id) REFERENCES semesters(id),
 CONSTRAINT fk_plans_approver FOREIGN KEY(approved_by) REFERENCES users(id),
 UNIQUE KEY uq_student_semester_plan(student_id,semester_id)
) ENGINE=InnoDB;

CREATE TABLE study_plan_details (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 study_plan_id BIGINT UNSIGNED NOT NULL,
 course_class_id BIGINT UNSIGNED NOT NULL,
 CONSTRAINT fk_plan_details_plan FOREIGN KEY(study_plan_id) REFERENCES study_plans(id) ON DELETE CASCADE,
 CONSTRAINT fk_plan_details_class FOREIGN KEY(course_class_id) REFERENCES course_classes(id),
 UNIQUE KEY uq_plan_class(study_plan_id,course_class_id)
) ENGINE=InnoDB;

CREATE TABLE grades (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id BIGINT UNSIGNED NOT NULL,
 course_class_id BIGINT UNSIGNED NOT NULL,
 numeric_score DECIMAL(5,2) NULL,
 letter_grade VARCHAR(2) NULL,
 grade_points DECIMAL(3,2) NULL,
 published_at DATETIME NULL,
 CONSTRAINT fk_grades_student FOREIGN KEY(student_id) REFERENCES students(id),
 CONSTRAINT fk_grades_class FOREIGN KEY(course_class_id) REFERENCES course_classes(id),
 UNIQUE KEY uq_grade_student_class(student_id,course_class_id)
) ENGINE=InnoDB;

CREATE TABLE attendance (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id BIGINT UNSIGNED NOT NULL,
 course_class_id BIGINT UNSIGNED NOT NULL,
 meeting_number TINYINT UNSIGNED NOT NULL,
 attendance_status ENUM('present','sick','permission','absent') NOT NULL,
 recorded_at DATETIME NOT NULL,
 recorded_by BIGINT UNSIGNED NULL,
 CONSTRAINT fk_attendance_student FOREIGN KEY(student_id) REFERENCES students(id),
 CONSTRAINT fk_attendance_class FOREIGN KEY(course_class_id) REFERENCES course_classes(id),
 CONSTRAINT fk_attendance_recorder FOREIGN KEY(recorded_by) REFERENCES users(id),
 UNIQUE KEY uq_attendance_meeting(student_id,course_class_id,meeting_number)
) ENGINE=InnoDB;

CREATE TABLE tuition_bills (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id BIGINT UNSIGNED NOT NULL,
 semester_id BIGINT UNSIGNED NULL,
 bill_code VARCHAR(50) NOT NULL UNIQUE,
 description VARCHAR(180) NOT NULL,
 amount DECIMAL(14,2) NOT NULL,
 due_date DATE NULL,
 status ENUM('unpaid','partial','paid','cancelled') NOT NULL DEFAULT 'unpaid',
 CONSTRAINT fk_bills_student FOREIGN KEY(student_id) REFERENCES students(id),
 CONSTRAINT fk_bills_semester FOREIGN KEY(semester_id) REFERENCES semesters(id)
) ENGINE=InnoDB;

CREATE TABLE payments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tuition_bill_id BIGINT UNSIGNED NOT NULL,
 amount DECIMAL(14,2) NOT NULL,
 payment_method VARCHAR(50) NULL,
 proof_path VARCHAR(255) NULL,
 status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
 paid_at DATETIME NULL,
 verified_by BIGINT UNSIGNED NULL,
 verified_at DATETIME NULL,
 CONSTRAINT fk_payments_bill FOREIGN KEY(tuition_bill_id) REFERENCES tuition_bills(id),
 CONSTRAINT fk_payments_verifier FOREIGN KEY(verified_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE announcements (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(200) NOT NULL,
 body TEXT NOT NULL,
 audience ENUM('all','student','lecturer','admin') NOT NULL DEFAULT 'all',
 published_at DATETIME NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_announcements_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 body TEXT NOT NULL,
 read_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_notifications_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_notifications_user_read(user_id,read_at)
) ENGINE=InnoDB;

CREATE TABLE academic_documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 student_id BIGINT UNSIGNED NOT NULL,
 document_type VARCHAR(60) NOT NULL,
 storage_path VARCHAR(255) NOT NULL,
 uploaded_by BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_documents_student FOREIGN KEY(student_id) REFERENCES students(id),
 CONSTRAINT fk_documents_uploader FOREIGN KEY(uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE pmb_applicants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 registration_number VARCHAR(40) NOT NULL UNIQUE,
 full_name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL,
 phone VARCHAR(30) NULL,
 study_program_id BIGINT UNSIGNED NULL,
 status ENUM('registered','documents_review','selection','accepted','rejected','re_registered') NOT NULL DEFAULT 'registered',
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_pmb_program FOREIGN KEY(study_program_id) REFERENCES study_programs(id)
) ENGINE=InnoDB;

CREATE TABLE admin_login_attempts (
 attempt_key CHAR(64) NOT NULL PRIMARY KEY,
 attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 window_started_at DATETIME NOT NULL,
 blocked_until DATETIME NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_admin_login_blocked(blocked_until)
) ENGINE=InnoDB;

CREATE TABLE user_program_scopes (
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

CREATE TABLE audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(100) NOT NULL,
 entity_type VARCHAR(80) NULL,
 entity_id BIGINT UNSIGNED NULL,
 ip_address VARCHAR(45) NULL,
 user_agent VARCHAR(255) NULL,
 metadata JSON NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL,
 INDEX idx_audit_created(created_at),
 INDEX idx_audit_user(user_id)
) ENGINE=InnoDB;

INSERT INTO roles(name,description) VALUES
('super_admin','Akses penuh seluruh sistem'),
('admin_akademik','Pengelolaan administrasi akademik'),
('admin_keuangan','Pengelolaan tagihan dan pembayaran'),
('dosen','Akses layanan dosen'),
('mahasiswa','Akses layanan mahasiswa'),
('ketua_prodi','Monitoring program studi'),
('ketua_stit','Monitoring pimpinan kampus');

-- ============================================================
-- MODUL HOMEPAGE: PROFIL KAMPUS DAN BERITA
-- Termasuk dalam instalasi database baru; tidak perlu migration.
-- Informasi resmi kampus diisi/diperbarui melalui Admin Web.
-- ============================================================

CREATE TABLE campus_profile (
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

-- Baris awal hanya placeholder yang perlu dilengkapi pihak kampus.
INSERT INTO campus_profile(id,tagline,about,vision,mission,address,phone,email,website,pmb_url)
VALUES(1,'Portal Informasi dan Akademik STIT Darul Ilmi Tasikmalaya',
'Lengkapi bagian ini dengan sejarah, profil, dan keunggulan resmi kampus melalui Admin Web.',
'Isi visi resmi kampus melalui Admin Web.',
'Isi misi resmi kampus melalui Admin Web.',
NULL,NULL,NULL,NULL,NULL);

CREATE TABLE news_posts (
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

-- Berita tidak diisi otomatis. Admin membuat dan menerbitkan berita resmi.

