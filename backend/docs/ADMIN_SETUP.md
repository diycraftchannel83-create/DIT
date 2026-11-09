# Membuat akun Super Admin dengan aman

Jangan commit akun admin default atau password.

1. Import schema database.
2. Buat hash password secara lokal: `php -r "echo password_hash('PASSWORD_KUAT', PASSWORD_DEFAULT), PHP_EOL;"`
3. Jalankan SQL berikut di phpMyAdmin setelah mengganti username, email, dan hash:

```sql
INSERT INTO users (role_id,username,full_name,email,password_hash,status)
SELECT id,'adminstit','Administrator STIT','admin@domain-kampus.ac.id','HASH_PASSWORD','active'
FROM roles WHERE name='super_admin';
```

Gunakan password unik. Jangan bagikan password atau hash. Aktifkan MFA pada akun hosting dan GitHub.
