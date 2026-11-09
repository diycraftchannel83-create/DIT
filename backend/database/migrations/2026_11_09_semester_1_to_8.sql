-- Migrasi format semester STIT Darul Ilmi: Ganjil/Genap menjadi Semester 1-8.
-- Backup database sebelum menjalankan. Jalankan satu kali melalui phpMyAdmin.
-- Antara (semester pendek) dipertahankan sebagai data historis; form baru tidak lagi membuatnya.
ALTER TABLE semesters
  MODIFY name VARCHAR(20) NOT NULL;

UPDATE semesters
SET name = CASE
  WHEN name = 'Ganjil' THEN 'Semester 1'
  WHEN name = 'Genap' THEN 'Semester 2'
  ELSE name
END
WHERE name IN ('Ganjil', 'Genap');

-- recommended_semester hanya menerima rentang 1-8 pada aplikasi.
