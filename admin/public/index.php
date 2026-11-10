<?php
declare(strict_types=1);

/*
 * STIT Darul Ilmi Academic Portal - Admin Web
 * PHP 8+, PDO MySQL. Deploy this directory as a separate web root/subdomain.
 */
$backend = dirname(__DIR__, 2) . '/backend';
$configFile = $backend . '/config/database.local.php';
if (!is_file($configFile)) {
    http_response_code(503);
    exit('Backend belum dikonfigurasi. Buat backend/config/database.local.php terlebih dahulu.');
}
$cfg = require $configFile;
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['database'], $cfg['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    error_log('[STIT ADMIN DB] ' . $e->getMessage());
    http_response_code(503);
    exit('Koneksi database gagal. Periksa konfigurasi server.');
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_name('STITADMIN');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $page='dashboard'): never { header('Location: ?page=' . rawurlencode($page)); exit; }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function checkCsrf(): void {
    $sent = (string)($_POST['_csrf'] ?? '');
    if (!$sent || !hash_equals((string)($_SESSION['csrf'] ?? ''), $sent)) {
        http_response_code(419); exit('Sesi formulir kedaluwarsa. Muat ulang halaman dan coba lagi.');
    }
}
function user(): ?array { return $_SESSION['admin_user'] ?? null; }
function requireLogin(): array {
    if (!user()) go('login');
    return user();
}
function allow(array $roles): void {
    $u = requireLogin();
    if (!in_array($u['role'], $roles, true)) { http_response_code(403); exit('Anda tidak memiliki izin untuk membuka halaman ini.'); }
}
function flash(string $message, string $type='success'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function saveCampusHeroUpload(string $field='hero_image_file'): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    $file=$_FILES[$field];
    if (($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK || ($file['size']??0)>5*1024*1024) throw new RuntimeException('Foto gedung gagal diunggah atau melebihi 5 MB.');
    $tmp=(string)$file['tmp_name'];
    if (!is_uploaded_file($tmp)) throw new RuntimeException('File foto gedung tidak valid.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if (!$ext) throw new RuntimeException('Foto gedung harus JPG, PNG, atau WebP.');
    $dir=__DIR__.'/uploads/campus';
    if (!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir)) throw new RuntimeException('Folder upload foto gedung tidak tersedia.');
    $name='hero-'.bin2hex(random_bytes(16)).'.'.$ext;
    if (!move_uploaded_file($tmp,$dir.'/'.$name)) throw new RuntimeException('Foto gedung tidak dapat disimpan.');
    $https=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off');
    $scheme=$https?'https':'http';
    $base=rtrim(dirname((string)($_SERVER['SCRIPT_NAME']??'/')),'/');
    return $scheme.'://'.($_SERVER['HTTP_HOST']??'localhost').$base.'/uploads/campus/'.$name;
}
function saveCampusAboutUpload(string $field='about_image_file'): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    $file=$_FILES[$field];
    if (($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK || ($file['size']??0)>5*1024*1024) throw new RuntimeException('Foto kedua gagal diunggah atau melebihi 5 MB.');
    $tmp=(string)$file['tmp_name'];
    if (!is_uploaded_file($tmp)) throw new RuntimeException('File foto kedua tidak valid.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if (!$ext) throw new RuntimeException('Foto kedua harus JPG, PNG, atau WebP.');
    $dir=__DIR__.'/uploads/campus';
    if (!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir)) throw new RuntimeException('Folder upload foto kampus tidak tersedia.');
    $name='about-'.bin2hex(random_bytes(16)).'.'.$ext;
    if (!move_uploaded_file($tmp,$dir.'/'.$name)) throw new RuntimeException('Foto kedua tidak dapat disimpan.');
    $scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
    $base=rtrim(dirname((string)($_SERVER['SCRIPT_NAME']??'/')),'/');
    return $scheme.'://'.($_SERVER['HTTP_HOST']??'localhost').$base.'/uploads/campus/'.$name;
}
function saveStudentPhotoUpload(string $field='photo_file', string $folder='students'): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    $file=$_FILES[$field];if(($file['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK||($file['size']??0)>2*1024*1024)throw new RuntimeException('Foto gagal diunggah atau melebihi 2 MB.');
    $tmp=(string)$file['tmp_name'];if(!is_uploaded_file($tmp))throw new RuntimeException('File foto tidak valid.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;if(!$ext)throw new RuntimeException('Format foto harus JPG, PNG, atau WebP.');
    $folder=in_array($folder,['students','lecturers'],true)?$folder:'students';$dir=__DIR__.'/uploads/'.$folder;if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('Folder upload foto tidak tersedia.');
    $name=bin2hex(random_bytes(16)).'.'.$ext;if(!move_uploaded_file($tmp,$dir.'/'.$name))throw new RuntimeException('Foto tidak dapat disimpan.');
    return 'uploads/'.$folder.'/'.$name;
}
function auditLog(PDO $pdo, string $action, ?string $entityType=null, ?int $entityId=null, array $metadata=[]): void {
    try {
        $actor = user();
        $q = $pdo->prepare('INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,user_agent,metadata) VALUES(:uid,:action,:type,:entity,:ip,:agent,:metadata)');
        $q->execute([
            'uid' => $actor ? (int)$actor['id'] : null,
            'action' => substr($action, 0, 100),
            'type' => $entityType !== null ? substr($entityType, 0, 80) : null,
            'entity' => $entityId,
            'ip' => substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45) ?: null,
            'agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255) ?: null,
            'metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
        ]);
    } catch (Throwable $e) {
        error_log('[STIT AUDIT] ' . $e->getMessage());
    }
}
function loginRateKey(string $username): string {
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash('sha256', strtolower(trim($username)) . '|' . $ip);
}
function loginBlockedUntil(PDO $pdo, string $key): ?string {
    $q = $pdo->prepare('SELECT blocked_until FROM admin_login_attempts WHERE attempt_key=:key LIMIT 1');
    $q->execute(['key'=>$key]);
    $until = $q->fetchColumn();
    return $until && strtotime((string)$until) > time() ? (string)$until : null;
}
function recordLoginFailure(PDO $pdo, string $key): void {
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT IGNORE INTO admin_login_attempts(attempt_key,attempts,window_started_at) VALUES(:key,0,UTC_TIMESTAMP())')->execute(['key'=>$key]);
        $q=$pdo->prepare('SELECT attempts,window_started_at FROM admin_login_attempts WHERE attempt_key=:key FOR UPDATE');
        $q->execute(['key'=>$key]);$row=$q->fetch();
        $expired=!$row || strtotime((string)$row['window_started_at']) < time()-900;
        $attempts=$expired?1:((int)$row['attempts']+1);
        $blocked=$attempts>=5?gmdate('Y-m-d H:i:s',time()+900):null;
        $u=$pdo->prepare('UPDATE admin_login_attempts SET attempts=:attempts,window_started_at=IF(:expired=1,UTC_TIMESTAMP(),window_started_at),blocked_until=:blocked WHERE attempt_key=:key');
        $u->execute(['attempts'=>$attempts,'expired'=>$expired?1:0,'blocked'=>$blocked,'key'=>$key]);
        $pdo->commit();
    } catch(Throwable $e) {
        if($pdo->inTransaction())$pdo->rollBack();
        error_log('[STIT LOGIN LIMIT] '.$e->getMessage());
    }
}
function clearLoginFailures(PDO $pdo, string $key): void {
    $q=$pdo->prepare('DELETE FROM admin_login_attempts WHERE attempt_key=:key');
    $q->execute(['key'=>$key]);
}
function tableCount(PDO $pdo, string $table): int {
    $allowed=['users','students','lecturers','study_programs','courses','course_classes','pmb_applicants','tuition_bills','payments','news_posts'];
    if (!in_array($table,$allowed,true)) return 0;
    try { return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn(); } catch(Throwable) { return 0; }
}
function ensureMigrationRegistry(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration_name VARCHAR(190) NOT NULL UNIQUE,
        checksum CHAR(64) NOT NULL,
        executed_by BIGINT UNSIGNED NULL,
        executed_at DATETIME NOT NULL,
        INDEX idx_schema_migrations_executed_at(executed_at),
        CONSTRAINT fk_schema_migrations_user FOREIGN KEY(executed_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function availableMigrationFiles(string $backend): array {
    $dir=realpath($backend.'/database/migrations');
    if($dir===false || !is_dir($dir)) return [];
    $items=[];
    foreach(glob($dir.'/*.sql') ?: [] as $path) {
        $real=realpath($path);
        $name=basename($path);
        if($real===false || dirname($real)!==$dir || !preg_match('/^202[0-9]_\\d{2}_\\d{2}_[a-z0-9_]+\\.sql$/',$name)) continue;
        $items[$name]=['path'=>$real,'checksum'=>hash_file('sha256',$real)];
    }
    ksort($items,SORT_STRING);
    return $items;
}
function executeMigrationFile(PDO $pdo, string $path): void {
    $sql=file_get_contents($path);
    if($sql===false || trim($sql)==='') throw new RuntimeException('File migrasi kosong atau tidak dapat dibaca.');
    // Migration repository hanya menggunakan SQL standar tanpa delimiter/procedure.
    $sql=preg_replace('/^\\s*--.*(?:\\R|$)/m','',$sql) ?? $sql;
    $statements=preg_split('/;\\s*(?:\\R|$)/',$sql) ?: [];
    $executed=0;
    foreach($statements as $statement) {
        $statement=trim($statement);
        if($statement==='') continue;
        if(stripos($statement,'DELIMITER ')===0) throw new RuntimeException('Perintah DELIMITER tidak didukung untuk migrasi aplikasi.');
        $pdo->exec($statement);
        $executed++;
    }
    if($executed===0) throw new RuntimeException('Tidak ditemukan perintah SQL yang dapat dijalankan.');
}
function migrationAlreadyPresent(PDO $pdo, string $name): bool {
    $columnSets=[
        '2026_11_09_campus_hero_image.sql'=>['campus_profile'=>['hero_image_url']],
        '2026_11_09_campus_logo_image.sql'=>['campus_profile'=>['logo_image_url']],
        '2026_11_09_expand_lecturer_profile.sql'=>['lecturers'=>['national_id','birth_place','birth_date','gender','religion','marital_status','province','city','district','village','postal_code','address','emergency_contact_name','emergency_contact_phone','study_program_id','academic_rank','functional_position','employment_status','employment_type','employment_start_date','highest_education','expertise','certification_number']],
        '2026_11_09_expand_student_profile.sql'=>['students'=>['national_id','birth_place','birth_date','gender','religion','marital_status','province','city','district','village','postal_code','father_name','mother_name','guardian_name','guardian_phone','emergency_contact_name','emergency_contact_phone','admission_pathway','registration_date','photo_url']],
        '2026_11_09_lecturer_profile_photo.sql'=>['lecturers'=>['photo_url']],
    ];
    if($name==='2026_11_09_admin_login_attempts.sql' || $name==='2026_11_09_user_program_scopes.sql' || $name==='2026_11_09_website_content.sql') {
        $table=['2026_11_09_admin_login_attempts.sql'=>'admin_login_attempts','2026_11_09_user_program_scopes.sql'=>'user_program_scopes','2026_11_09_website_content.sql'=>'website_content'][$name];
        $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:table');
        $q->execute(['table'=>$table]);
        return (int)$q->fetchColumn()>0;
    }
    if(isset($columnSets[$name])) {
        foreach($columnSets[$name] as $table=>$columns) {
            foreach($columns as $column) {
                $q=$pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:table AND column_name=:column');
                $q->execute(['table'=>$table,'column'=>$column]);
                if((int)$q->fetchColumn()===0) return false;
            }
        }
        return true;
    }
    if($name==='2026_11_09_semester_1_to_8.sql') {
        $q=$pdo->query("SELECT COUNT(*) FROM semesters WHERE name IN ('Ganjil','Genap')");
        return (int)$q->fetchColumn()===0;
    }
    return false;
}

$page = (string)($_GET['page'] ?? (user() ? 'dashboard' : 'login'));
$academicRoles=['super_admin','admin_akademik'];
$viewRoles=['super_admin','admin_akademik','ketua_prodi','ketua_stit'];

// Export ditempatkan setelah deklarasi role agar otorisasi selalu memakai daftar yang valid.
if ($page === 'students' && ($_GET['export'] ?? '') === 'csv') {
    allow($academicRoles);
    $q = $pdo->query('SELECT s.nim,u.full_name,u.email,p.name AS program,s.entry_year,s.phone,s.birth_place,s.birth_date,s.gender,s.city,s.district,s.admission_pathway,s.status FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN study_programs p ON p.id=s.study_program_id ORDER BY s.nim');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="data-mahasiswa.csv"');
    $out = fopen('php://output', 'w');
    if ($out === false) {
        http_response_code(500);
        exit('Gagal membuat ekspor CSV.');
    }
    // UTF-8 BOM agar karakter Indonesia terbaca baik oleh Excel.
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['NIM','Nama','Email','Program Studi','Tahun Masuk','Telepon','Tempat Lahir','Tanggal Lahir','Jenis Kelamin','Kota','Kecamatan','Jalur Masuk','Status']);
    while ($row = $q->fetch(PDO::FETCH_NUM)) {
        $safeRow = array_map(static function ($value) {
            if (!is_string($value)) return $value;
            // Cegah spreadsheet menafsirkan data pengguna sebagai formula.
            if (preg_match('/^[\\x00-\\x20]*[=+\\-@]/', $value)) return "'" . $value;
            return $value;
        }, $row);
        fputcsv($out, $safeRow);
    }
    fclose($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    checkCsrf();
    $action=(string)($_POST['action']??'');
    if ($action==='login') {
        $username=trim((string)($_POST['username']??'')); $password=(string)($_POST['password']??'');
        $rateKey=loginRateKey($username);
        if(loginBlockedUntil($pdo,$rateKey)!==null){
            auditLog($pdo,'admin.login_blocked','user',null,['reason'=>'rate_limit']);
            flash('Terlalu banyak percobaan login. Coba kembali setelah 15 menit.','error'); go('login');
        }
        $q=$pdo->prepare('SELECT u.id,u.username,u.full_name,u.email,u.password_hash,u.status,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=:username LIMIT 1');
        $q->execute(['username'=>$username]); $u=$q->fetch();
        if (!$u || $u['status']!=='active' || !password_verify($password,$u['password_hash'])) {
            recordLoginFailure($pdo,$rateKey);
            auditLog($pdo,'admin.login_failed','user',null,['reason'=>'invalid_credentials']);
            flash('Username atau password tidak valid.','error'); go('login');
        }
        if (!in_array($u['role'],['super_admin','admin_akademik','admin_keuangan','ketua_prodi','ketua_stit'],true)) {
            recordLoginFailure($pdo,$rateKey);
            auditLog($pdo,'admin.login_denied','user',(int)$u['id'],['reason'=>'role_not_allowed']);
            flash('Akun mahasiswa dan dosen harus menggunakan Portal Mahasiswa/Dosen.','error'); go('login');
        }
        clearLoginFailures($pdo,$rateKey);
        session_regenerate_id(true);
        $_SESSION['admin_user']=['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'role'=>$u['role']];
        $_SESSION['csrf']=bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE users SET last_login_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$u['id']]);
        auditLog($pdo,'admin.login_success','user',(int)$u['id']);
        go('dashboard');
    }
    if ($action==='logout') { auditLog($pdo,'admin.logout','user',(int)(user()['id']??0)); $_SESSION=[]; session_regenerate_id(true); go('login'); }
    $u=requireLogin();
    if ($action==='run_database_migrations') {
        allow(['super_admin']);
        $selected=$_POST['migrations']??[];
        if(!is_array($selected) || !$selected) { flash('Pilih minimal satu file migrasi terlebih dahulu.','error'); go('migrations'); }
        try {
            ensureMigrationRegistry($pdo);
            $available=availableMigrationFiles($backend);
            $done=0;
            foreach(array_values(array_unique($selected)) as $name) {
                if(!is_string($name) || !isset($available[$name])) throw new RuntimeException('File migrasi yang dipilih tidak dikenal atau tidak tersedia.');
                $check=$pdo->prepare('SELECT checksum FROM schema_migrations WHERE migration_name=:name LIMIT 1');
                $check->execute(['name'=>$name]);
                $existing=$check->fetchColumn();
                if($existing!==false) {
                    if(!hash_equals((string)$existing,(string)$available[$name]['checksum'])) throw new RuntimeException('Checksum file '.$name.' berubah setelah pernah diterapkan. Periksa manual sebelum melanjutkan.');
                    continue;
                }
                executeMigrationFile($pdo,$available[$name]['path']);
                $insert=$pdo->prepare('INSERT INTO schema_migrations(migration_name,checksum,executed_by,executed_at) VALUES(:name,:checksum,:actor,UTC_TIMESTAMP())');
                $insert->execute(['name'=>$name,'checksum'=>$available[$name]['checksum'],'actor'=>(int)$u['id']]);
                auditLog($pdo,'admin.database_migration_applied','schema_migration',null,['migration'=>$name,'checksum'=>$available[$name]['checksum']]);
                $done++;
            }
            flash($done>0 ? $done.' file migrasi berhasil diterapkan.' : 'Tidak ada migrasi baru yang dijalankan. File terpilih sudah diterapkan.');
        } catch(Throwable $e) {
            error_log('[STIT DB MIGRATION] '.$e->getMessage());
            flash('Migrasi dihentikan: '.$e->getMessage().' Periksa kondisi database sebelum mencoba ulang.','error');
        }
        go('migrations');
    }
    if ($action==='update_account') {
        allow(['super_admin','admin_akademik']);
        $id=(int)($_POST['user_id']??0);$fullName=trim((string)($_POST['full_name']??''));$email=trim((string)($_POST['email']??''));$roleName=(string)($_POST['role']??'');$status=(string)($_POST['status']??'active');$password=(string)($_POST['password']??'');
        $allowedRoles=['admin_akademik','admin_keuangan','ketua_prodi','ketua_stit','dosen','mahasiswa'];
        if($id<1||$fullName===''||strlen($fullName)>160||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||!in_array($roleName,$allowedRoles,true)||!in_array($status,['active','inactive'],true)||($password!==''&&(strlen($password)<10||strlen($password)>200))){flash('Data akun tidak valid. Password baru harus minimal 10 karakter.','error');go('accounts');}
        try {
            $check=$pdo->prepare('SELECT u.id,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=:id');$check->execute(['id'=>$id]);$existing=$check->fetch();
            if(!$existing)throw new RuntimeException('Akun tidak ditemukan.');
            if($existing['role']==='super_admin'&&$u['role']!=='super_admin')throw new RuntimeException('Hanya Super Admin yang dapat mengubah akun Super Admin.');
            $role=$pdo->prepare('SELECT id FROM roles WHERE name=:role LIMIT 1');$role->execute(['role'=>$roleName]);$roleId=$role->fetchColumn();if(!$roleId)throw new RuntimeException('Role tidak tersedia.');
            if($password!==''){$q=$pdo->prepare('UPDATE users SET full_name=:name,email=:email,role_id=:role,status=:status,password_hash=:hash WHERE id=:id');$q->execute(['name'=>$fullName,'email'=>$email?:null,'role'=>$roleId,'status'=>$status,'hash'=>password_hash($password,PASSWORD_DEFAULT),'id'=>$id]);}
            else{$q=$pdo->prepare('UPDATE users SET full_name=:name,email=:email,role_id=:role,status=:status WHERE id=:id');$q->execute(['name'=>$fullName,'email'=>$email?:null,'role'=>$roleId,'status'=>$status,'id'=>$id]);}
            auditLog($pdo,'admin.account_updated','user',$id,['role'=>$roleName,'status'=>$status,'password_changed'=>$password!=='']);flash('Data akun berhasil diperbarui.');
        } catch(Throwable $e){error_log('[STIT UPDATE ACCOUNT] '.$e->getMessage());flash('Gagal memperbarui akun: '.$e->getMessage(),'error');}
        go('accounts');
    }
    if ($action==='delete_account') {
        allow(['super_admin','admin_akademik']);$id=(int)($_POST['user_id']??0);
        try {
            if($id<1||$id===(int)$u['id'])throw new RuntimeException('Akun yang sedang digunakan tidak dapat dihapus.');
            $check=$pdo->prepare('SELECT u.id,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=:id');$check->execute(['id'=>$id]);$target=$check->fetch();
            if(!$target)throw new RuntimeException('Akun tidak ditemukan.');
            if($target['role']==='super_admin')throw new RuntimeException('Akun Super Admin dilindungi dan tidak dapat dihapus dari menu ini.');
            $linked=$pdo->prepare('SELECT (SELECT COUNT(*) FROM students WHERE user_id=:student_uid)+(SELECT COUNT(*) FROM lecturers WHERE user_id=:lecturer_uid)');$linked->execute(['student_uid'=>$id,'lecturer_uid'=>$id]);
            if((int)$linked->fetchColumn()>0)throw new RuntimeException('Akun terhubung dengan data mahasiswa/dosen. Nonaktifkan akun agar riwayat akademik tetap aman.');
            $pdo->beginTransaction();$pdo->prepare('DELETE FROM user_program_scopes WHERE user_id=:id')->execute(['id'=>$id]);$pdo->prepare('DELETE FROM users WHERE id=:id')->execute(['id'=>$id]);$pdo->commit();
            auditLog($pdo,'admin.account_deleted','user',$id,['role'=>$target['role']]);flash('Akun berhasil dihapus.');
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('[STIT DELETE ACCOUNT] '.$e->getMessage());flash('Akun tidak dapat dihapus: '.$e->getMessage(),'error');}
        go('accounts');
    }
    if ($action==='assign_prodi_scope') {
        allow(['super_admin','admin_akademik']);
        $targetUser=(int)($_POST['user_id']??0);
        $programId=(int)($_POST['study_program_id']??0);
        try {
            $check=$pdo->prepare("SELECT u.id FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=:uid AND r.name='ketua_prodi' AND u.status='active'");
            $check->execute(['uid'=>$targetUser]);
            if(!$check->fetchColumn()) throw new RuntimeException('Akun Ketua Prodi aktif tidak ditemukan.');
            $programCheck=$pdo->prepare('SELECT id FROM study_programs WHERE id=:id AND is_active=1');
            $programCheck->execute(['id'=>$programId]);
            if(!$programCheck->fetchColumn()) throw new RuntimeException('Program studi aktif tidak ditemukan.');
            $q=$pdo->prepare('INSERT INTO user_program_scopes(user_id,study_program_id,assigned_by) VALUES(:uid,:pid,:actor) ON DUPLICATE KEY UPDATE study_program_id=VALUES(study_program_id),assigned_by=VALUES(assigned_by),updated_at=CURRENT_TIMESTAMP');
            $q->execute(['uid'=>$targetUser,'pid'=>$programId,'actor'=>$u['id']]);
            auditLog($pdo,'admin.prodi_scope_assigned','user',$targetUser,['study_program_id'=>$programId]);
            flash('Cakupan akses Ketua Prodi berhasil diperbarui.');
        } catch(Throwable $e) {
            error_log('[STIT PRODI SCOPE] '.$e->getMessage());
            flash('Gagal menetapkan program studi. Periksa akun dan program studi.','error');
        }
        go('accounts');
    }
    if ($action==='save_website_content') {
        allow(['super_admin','admin_akademik']);
        $keys=['history','pmb_title','pmb_info','whatsapp','facebook','instagram','youtube','stats_json','why_cards','gallery_json','testimonials_json'];
        $values=[];
        foreach($keys as $key){$values[$key]=trim((string)($_POST[$key]??''));}
        try {
            $uploadedAbout=saveCampusAboutUpload('about_image_file');
            if ($uploadedAbout!==null) $values['about_image_url']=$uploadedAbout;
            elseif (!empty($_POST['remove_about_image'])) $values['about_image_url']='';
        } catch(Throwable $uploadError) {
            flash($uploadError->getMessage(),'error'); go('dashboard');
        }
        foreach(['stats_json','why_cards','gallery_json','testimonials_json'] as $jsonKey){
            if($values[$jsonKey]!==''){json_decode($values[$jsonKey],true);if(json_last_error()!==JSON_ERROR_NONE||!is_array(json_decode($values[$jsonKey],true))){flash('Format JSON pada '.$jsonKey.' tidak valid.','error');go('dashboard');}}
        }
        foreach(['facebook','instagram','youtube'] as $urlKey){
            if($values[$urlKey]!==''&&(!filter_var($values[$urlKey],FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($values[$urlKey],PHP_URL_SCHEME)),['http','https'],true))){flash('URL media sosial tidak valid.','error');go('dashboard');}
        }
        try{
            $q=$pdo->prepare('INSERT INTO website_content(content_key,content_value,updated_by) VALUES(:k,:v,:uid) ON DUPLICATE KEY UPDATE content_value=VALUES(content_value),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP');
            foreach($values as $key=>$value)$q->execute(['k'=>$key,'v'=>$value,'uid'=>$u['id']]);
            auditLog($pdo,'admin.website_content_updated','website',1,['keys'=>array_keys($values)]);
            flash('Konten website berhasil diperbarui.');
        }catch(Throwable $e){error_log('[STIT WEBSITE CONTENT] '.$e->getMessage());flash('Konten website gagal disimpan. Pastikan migrasi website_content sudah dijalankan.','error');}
        go('dashboard');
    }
    if ($action==='save_campus_profile') {
        allow(['super_admin','admin_akademik']);
        $fields=['tagline'=>trim((string)($_POST['tagline']??'')),'about'=>trim((string)($_POST['about']??'')),'vision'=>trim((string)($_POST['vision']??'')),'mission'=>trim((string)($_POST['mission']??'')),'address'=>trim((string)($_POST['address']??'')),'phone'=>trim((string)($_POST['phone']??'')),'email'=>trim((string)($_POST['email']??'')),'website'=>trim((string)($_POST['website']??'')),'pmb_url'=>trim((string)($_POST['pmb_url']??''))];
        try {
            $uploadedHero=saveCampusHeroUpload('hero_image_file');
            $uploadedLogo=saveCampusHeroUpload('logo_image_file');
            $currentMedia=$pdo->query('SELECT hero_image_url,logo_image_url FROM campus_profile WHERE id=1')->fetch()?:[];
            $heroUrl=$uploadedHero??(($currentMedia['hero_image_url']??'')?:null);
            $logoUrl=$uploadedLogo??(($currentMedia['logo_image_url']??'')?:null);
            if (!empty($_POST['remove_hero_image'])) $heroUrl=null;
            if (!empty($_POST['remove_logo_image'])) $logoUrl=null;
        } catch(Throwable $uploadError) { flash($uploadError->getMessage(),'error');go('dashboard'); }
        if((($fields['website']!==''&&!in_array(strtolower((string)parse_url($fields['website'],PHP_URL_SCHEME)),['http','https'],true))||($fields['pmb_url']!==''&&!in_array(strtolower((string)parse_url($fields['pmb_url'],PHP_URL_SCHEME)),['http','https'],true)))||strlen($fields['tagline'])>220||strlen($fields['address'])>500||strlen($fields['phone'])>60||strlen($fields['email'])>190||strlen($fields['website'])>255||strlen($fields['pmb_url'])>255||($fields['email']!==''&&!filter_var($fields['email'],FILTER_VALIDATE_EMAIL))){flash('Periksa panjang data dan format email kampus.','error');go('dashboard');}
        $q=$pdo->prepare('INSERT INTO campus_profile(id,tagline,about,vision,mission,address,phone,email,website,pmb_url,hero_image_url,logo_image_url,updated_by) VALUES(1,:tagline,:about,:vision,:mission,:address,:phone,:email,:website,:pmb_url,:hero,:logo,:uid) ON DUPLICATE KEY UPDATE tagline=VALUES(tagline),about=VALUES(about),vision=VALUES(vision),mission=VALUES(mission),address=VALUES(address),phone=VALUES(phone),email=VALUES(email),website=VALUES(website),pmb_url=VALUES(pmb_url),hero_image_url=VALUES(hero_image_url),logo_image_url=VALUES(logo_image_url),updated_by=VALUES(updated_by)');
        $q->execute($fields+['hero'=>$heroUrl,'logo'=>$logoUrl,'uid'=>$u['id']]);auditLog($pdo,'admin.campus_hero_updated','campus',1,['has_hero_image'=>(bool)$heroUrl]);flash('Informasi kampus dan gambar gedung berhasil diperbarui.');go('dashboard');
    }
    if ($action==='save_news') {
        allow(['super_admin','admin_akademik']);
        $id=(int)($_POST['id']??0);$title=trim((string)($_POST['title']??''));$excerpt=trim((string)($_POST['excerpt']??''));$body=trim((string)($_POST['body']??''));$category=trim((string)($_POST['category']??'Berita'));$image=trim((string)($_POST['image_url']??''));
        if($title===''||$body===''||strlen($title)>200||strlen($excerpt)>400||strlen($category)>60||strlen($image)>500||($image!==''&&(!filter_var($image,FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($image,PHP_URL_SCHEME)),['http','https'],true)))){flash('Judul, isi, kategori, atau URL gambar berita tidak valid.','error');go('news');}
        try {if($id>0){$q=$pdo->prepare('UPDATE news_posts SET title=:title,excerpt=:excerpt,body=:body,category=:category,image_url=:image WHERE id=:id');$q->execute(['title'=>$title,'excerpt'=>$excerpt,'body'=>$body,'category'=>$category,'image'=>$image?:null,'id'=>$id]);}
        else{$q=$pdo->prepare('INSERT INTO news_posts(title,excerpt,body,category,image_url,created_by) VALUES(:title,:excerpt,:body,:category,:image,:uid)');$q->execute(['title'=>$title,'excerpt'=>$excerpt,'body'=>$body,'category'=>$category,'image'=>$image?:null,'uid'=>$u['id']]);}
        flash('Berita berhasil disimpan sebagai draft.');}catch(Throwable $e){error_log('[STIT NEWS] '.$e->getMessage());flash('Berita gagal disimpan.','error');}go('news');
    }
    if ($action==='publish_news') {
        allow(['super_admin','admin_akademik']);$id=(int)($_POST['id']??0);$publish=(int)($_POST['publish']??0);
        $q=$pdo->prepare('UPDATE news_posts SET published_at='.($publish?'UTC_TIMESTAMP()':'NULL').' WHERE id=:id');$q->execute(['id'=>$id]);flash('Status publikasi berita diperbarui.');go('news');
    }
    if ($action==='save_program') {
        allow($academicRoles);
        $id=(int)($_POST['id']??0); $code=strtoupper(trim((string)($_POST['code']??''))); $name=trim((string)($_POST['name']??'')); $degree=trim((string)($_POST['degree_level']??'S1'));
        if ($code==='' || $name==='' || strlen($code)>30 || strlen($name)>160) { flash('Kode dan nama program studi wajib diisi.','error'); go('programs'); }
        if ($id>0) $q=$pdo->prepare('UPDATE study_programs SET code=:code,name=:name,degree_level=:degree WHERE id=:id');
        else $q=$pdo->prepare('INSERT INTO study_programs(code,name,degree_level) VALUES(:code,:name,:degree)');
        $params=['code'=>$code,'name'=>$name,'degree'=>$degree]; if($id>0)$params['id']=$id;
        try {$q->execute($params); flash('Program studi berhasil disimpan.');} catch(Throwable $e) { flash('Gagal menyimpan. Pastikan kode program studi belum digunakan.','error'); }
        go('programs');
    }
    if ($action==='save_year') {
        allow($academicRoles);
        $id=(int)($_POST['id']??0); $name=trim((string)($_POST['name']??'')); $start=(string)($_POST['start_date']??''); $end=(string)($_POST['end_date']??'');
        if ($name==='' || !preg_match('/^\d{4}\/\d{4}$/',$name) || !$start || !$end || $start>$end) { flash('Periksa nama dan rentang tanggal tahun akademik.','error'); go('years'); }
        try {
            $pdo->beginTransaction();
            if (!empty($_POST['is_active'])) $pdo->exec('UPDATE academic_years SET is_active=0');
            if($id>0) {$q=$pdo->prepare('UPDATE academic_years SET name=:name,start_date=:start,end_date=:end,is_active=:active WHERE id=:id');$q->execute(['name'=>$name,'start'=>$start,'end'=>$end,'active'=>isset($_POST['is_active'])?1:0,'id'=>$id]);}
            else {$q=$pdo->prepare('INSERT INTO academic_years(name,start_date,end_date,is_active) VALUES(:name,:start,:end,:active)');$q->execute(['name'=>$name,'start'=>$start,'end'=>$end,'active'=>isset($_POST['is_active'])?1:0]);}
            $pdo->commit(); flash('Tahun akademik berhasil disimpan.');
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); flash('Gagal menyimpan tahun akademik. Periksa apakah nama sudah digunakan.','error'); }
        go('years');
    }
    if ($action==='update_student') {
        allow(['super_admin','admin_akademik']);
        try { $uploadedPhoto=saveStudentPhotoUpload(); } catch(Throwable $e) { flash($e->getMessage(),'error'); go('students'); }
        
        $id=(int)($_POST['student_id']??0);$uid=(int)($_POST['user_id']??0);
        $name=trim((string)($_POST['full_name']??''));$email=trim((string)($_POST['email']??''));$nim=trim((string)($_POST['nim']??''));$program=(int)($_POST['study_program_id']??0);$entry=(int)($_POST['entry_year']??0);
        if($id<1||$uid<1||$name===''||$nim===''||$program<1||$entry<2000||$entry>2100||strlen($name)>160||strlen($nim)>40||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))){flash('Data utama mahasiswa tidak valid.','error');go('students');}
        $fields=['phone'=>30,'address'=>5000,'national_id'=>32,'birth_place'=>100,'religion'=>40,'marital_status'=>30,'province'=>100,'city'=>100,'district'=>100,'village'=>100,'postal_code'=>10,'father_name'=>160,'mother_name'=>160,'guardian_name'=>160,'guardian_phone'=>30,'emergency_contact_name'=>160,'emergency_contact_phone'=>30,'admission_pathway'=>60,'photo_url'=>500];
        $values=[];foreach($fields as $field=>$max){$v=trim((string)($_POST[$field]??''));if(strlen($v)>$max){flash('Panjang field '.$field.' melebihi batas.','error');go('students');}$values[$field]=$v===''?null:$v;}
        foreach(['birth_date','registration_date'] as $field){$v=trim((string)($_POST[$field]??''));if($v!==''&&!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$v)){flash('Format tanggal tidak valid.','error');go('students');}$values[$field]=$v===''?null:$v;}
        $gender=(string)($_POST['gender']??'');if(!in_array($gender,['','male','female'],true)){flash('Jenis kelamin tidak valid.','error');go('students');}$values['gender']=$gender?:null;
        if($values['national_id']!==null&&!preg_match('/^\\d{16}$/',$values['national_id'])){flash('NIK harus 16 digit.','error');go('students');}
        if($values['photo_url']!==null&&!str_starts_with($values['photo_url'],'uploads/students/')&&(!filter_var($values['photo_url'],FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($values['photo_url'],PHP_URL_SCHEME)),['http','https'],true))){flash('URL foto tidak valid.','error');go('students');}
        if($uploadedPhoto!==null)$values['photo_url']=$uploadedPhoto;
        try{$pdo->beginTransaction();$q=$pdo->prepare('UPDATE users SET full_name=:name,email=:email WHERE id=:uid');$q->execute(['name'=>$name,'email'=>$email?:null,'uid'=>$uid]);
        $sql='UPDATE students SET nim=:nim,study_program_id=:program,entry_year=:entry,phone=:phone,address=:address,national_id=:national_id,birth_place=:birth_place,birth_date=:birth_date,gender=:gender,religion=:religion,marital_status=:marital_status,province=:province,city=:city,district=:district,village=:village,postal_code=:postal_code,father_name=:father_name,mother_name=:mother_name,guardian_name=:guardian_name,guardian_phone=:guardian_phone,emergency_contact_name=:emergency_contact_name,emergency_contact_phone=:emergency_contact_phone,admission_pathway=:admission_pathway,registration_date=:registration_date,photo_url=:photo_url WHERE id=:id AND user_id=:uid';
        $q=$pdo->prepare($sql);$q->execute(['nim'=>$nim,'program'=>$program,'entry'=>$entry]+$values+['id'=>$id,'uid'=>$uid]);if($q->rowCount()===0){$check=$pdo->prepare('SELECT id FROM students WHERE id=:id AND user_id=:uid');$check->execute(['id'=>$id,'uid'=>$uid]);if(!$check->fetchColumn())throw new RuntimeException('Mahasiswa tidak ditemukan.');}
        $pdo->commit();flash('Data mahasiswa berhasil diperbarui.');}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('[STIT UPDATE STUDENT] '.$e->getMessage());flash('Gagal memperbarui. Periksa NIM/email duplikat dan data yang dimasukkan.','error');}go('students');
    }
    if ($action==='save_student' || $action==='save_lecturer' || $action==='save_account') {
        allow(['super_admin','admin_akademik']);
        $fullName=trim((string)($_POST['full_name']??'')); $username=trim((string)($_POST['username']??'')); $email=trim((string)($_POST['email']??'')); $password=(string)($_POST['password']??'');
        if($fullName==='' || $username==='' || strlen($fullName)>160 || strlen($username)>100 || strlen($password)<10 || strlen($password)>200 || ($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL))) {
            flash('Nama, username, password minimal 10 karakter, atau email tidak valid.','error'); go($action==='save_student'?'students':($action==='save_lecturer'?'lecturers':'accounts'));
        }
        $roleName=$action==='save_student'?'mahasiswa':($action==='save_lecturer'?'dosen':(string)($_POST['role']??''));
        $allowedRoles=['admin_akademik','admin_keuangan','ketua_prodi','ketua_stit'];
        if($action==='save_account' && !in_array($roleName,$allowedRoles,true)){flash('Role akun tidak valid.','error');go('accounts');}
        try {
            $pdo->beginTransaction();
            $rq=$pdo->prepare('SELECT id FROM roles WHERE name=:role LIMIT 1');$rq->execute(['role'=>$roleName]);$roleId=$rq->fetchColumn();
            if(!$roleId) throw new RuntimeException('Role tidak ditemukan.');
            $iq=$pdo->prepare('INSERT INTO users(role_id,username,full_name,email,password_hash,status) VALUES(:role,:username,:name,:email,:hash,\'active\')');
            $iq->execute(['role'=>$roleId,'username'=>$username,'name'=>$fullName,'email'=>$email!==''?$email:null,'hash'=>password_hash($password,PASSWORD_DEFAULT)]);
            $userId=(int)$pdo->lastInsertId();
            if($action==='save_student'){
                $nim=trim((string)($_POST['nim']??''));$program=(int)($_POST['study_program_id']??0);$entry=(int)($_POST['entry_year']??date('Y'));$phone=trim((string)($_POST['phone']??''));
                $uploadedPhoto=saveStudentPhotoUpload();
                $studentFields=['national_id'=>trim((string)($_POST['national_id']??'')),'birth_place'=>trim((string)($_POST['birth_place']??'')),'birth_date'=>trim((string)($_POST['birth_date']??'')),'gender'=>(string)($_POST['gender']??''),'religion'=>trim((string)($_POST['religion']??'')),'marital_status'=>trim((string)($_POST['marital_status']??'')),'province'=>trim((string)($_POST['province']??'')),'city'=>trim((string)($_POST['city']??'')),'district'=>trim((string)($_POST['district']??'')),'village'=>trim((string)($_POST['village']??'')),'postal_code'=>trim((string)($_POST['postal_code']??'')),'father_name'=>trim((string)($_POST['father_name']??'')),'mother_name'=>trim((string)($_POST['mother_name']??'')),'guardian_name'=>trim((string)($_POST['guardian_name']??'')),'guardian_phone'=>trim((string)($_POST['guardian_phone']??'')),'emergency_contact_name'=>trim((string)($_POST['emergency_contact_name']??'')),'emergency_contact_phone'=>trim((string)($_POST['emergency_contact_phone']??'')),'admission_pathway'=>trim((string)($_POST['admission_pathway']??'')),'registration_date'=>trim((string)($_POST['registration_date']??'')),'photo_url'=>trim((string)($_POST['photo_url']??''))];
                if($nim===''||strlen($nim)>40||$program<1||$entry<2000||$entry>2100)throw new RuntimeException('NIM, program studi, atau tahun masuk tidak valid.');
                if($studentFields['national_id']!==''&&!preg_match('/^\\d{16}$/',$studentFields['national_id']))throw new RuntimeException('NIK harus 16 digit atau dikosongkan.');
                if(!in_array($studentFields['gender'],['','male','female'],true))throw new RuntimeException('Jenis kelamin tidak valid.');
                if($studentFields['birth_date']!==''&&!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$studentFields['birth_date']))throw new RuntimeException('Tanggal lahir tidak valid.');
                if($studentFields['registration_date']!==''&&!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$studentFields['registration_date']))throw new RuntimeException('Tanggal registrasi tidak valid.');
                $limits=['birth_place'=>100,'religion'=>40,'marital_status'=>30,'province'=>100,'city'=>100,'district'=>100,'village'=>100,'postal_code'=>10,'father_name'=>160,'mother_name'=>160,'guardian_name'=>160,'guardian_phone'=>30,'emergency_contact_name'=>160,'emergency_contact_phone'=>30,'admission_pathway'=>60,'photo_url'=>500];
                foreach($limits as $field=>$max)if(strlen($studentFields[$field])>$max)throw new RuntimeException('Panjang data '.$field.' melebihi batas.');
                if($uploadedPhoto!==null)$studentFields['photo_url']=$uploadedPhoto;
                if($studentFields['photo_url']!==''&&!str_starts_with($studentFields['photo_url'],'uploads/students/')&&(!filter_var($studentFields['photo_url'],FILTER_VALIDATE_URL)||!in_array(strtolower((string)parse_url($studentFields['photo_url'],PHP_URL_SCHEME)),['http','https'],true)))throw new RuntimeException('URL foto harus menggunakan HTTP/HTTPS.');
                $q=$pdo->prepare('INSERT INTO students(user_id,nim,study_program_id,entry_year,phone,national_id,birth_place,birth_date,gender,religion,marital_status,province,city,district,village,postal_code,father_name,mother_name,guardian_name,guardian_phone,emergency_contact_name,emergency_contact_phone,admission_pathway,registration_date,photo_url) VALUES(:uid,:nim,:program,:entry,:phone,:national_id,:birth_place,:birth_date,:gender,:religion,:marital_status,:province,:city,:district,:village,:postal_code,:father_name,:mother_name,:guardian_name,:guardian_phone,:emergency_contact_name,:emergency_contact_phone,:admission_pathway,:registration_date,:photo_url)');
                $q->execute(['uid'=>$userId,'nim'=>$nim,'program'=>$program,'entry'=>$entry,'phone'=>$phone?:null]+array_map(static fn($v)=>$v===''?null:$v,$studentFields));
            } elseif($action==='save_lecturer'){
                $number=trim((string)($_POST['lecturer_number']??''));$phone=trim((string)($_POST['phone']??''));
                $uploadedPhoto=saveStudentPhotoUpload('photo_file','lecturers');
                $fields=['national_id'=>trim((string)($_POST['national_id']??'')),'birth_place'=>trim((string)($_POST['birth_place']??'')),'birth_date'=>trim((string)($_POST['birth_date']??'')),'gender'=>(string)($_POST['gender']??''),'religion'=>trim((string)($_POST['religion']??'')),'marital_status'=>trim((string)($_POST['marital_status']??'')),'province'=>trim((string)($_POST['province']??'')),'city'=>trim((string)($_POST['city']??'')),'district'=>trim((string)($_POST['district']??'')),'village'=>trim((string)($_POST['village']??'')),'postal_code'=>trim((string)($_POST['postal_code']??'')),'address'=>trim((string)($_POST['address']??'')),'emergency_contact_name'=>trim((string)($_POST['emergency_contact_name']??'')),'emergency_contact_phone'=>trim((string)($_POST['emergency_contact_phone']??'')),'study_program_id'=>(int)($_POST['study_program_id']??0),'academic_rank'=>trim((string)($_POST['academic_rank']??'')),'functional_position'=>trim((string)($_POST['functional_position']??'')),'employment_status'=>trim((string)($_POST['employment_status']??'')),'employment_type'=>trim((string)($_POST['employment_type']??'')),'employment_start_date'=>trim((string)($_POST['employment_start_date']??'')),'highest_education'=>trim((string)($_POST['highest_education']??'')),'expertise'=>trim((string)($_POST['expertise']??'')),'certification_number'=>trim((string)($_POST['certification_number']??''))];
                if(strlen($number)>40||strlen($phone)>30||strlen($fields['national_id'])>32||($fields['national_id']!==''&&!preg_match('/^\\d{16}$/',$fields['national_id'])))throw new RuntimeException('Periksa NIDN, NIK (16 digit), dan nomor telepon.');
                if(!in_array($fields['gender'],['','male','female'],true))throw new RuntimeException('Jenis kelamin tidak valid.');
                foreach(['birth_date','employment_start_date'] as $dateField)if($fields[$dateField]!==''&&!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$fields[$dateField]))throw new RuntimeException('Format tanggal tidak valid.');
                $limits=['birth_place'=>100,'religion'=>40,'marital_status'=>30,'province'=>100,'city'=>100,'district'=>100,'village'=>100,'postal_code'=>10,'emergency_contact_name'=>160,'emergency_contact_phone'=>30,'academic_rank'=>100,'functional_position'=>120,'employment_status'=>40,'employment_type'=>40,'highest_education'=>40,'expertise'=>255,'certification_number'=>100];
                foreach($limits as $field=>$max)if(strlen($fields[$field])>$max)throw new RuntimeException('Panjang data '.$field.' melebihi batas.');
                if($fields['study_program_id']<1)$fields['study_program_id']=null;
                $sql='INSERT INTO lecturers(user_id,lecturer_number,phone,photo_url,national_id,birth_place,birth_date,gender,religion,marital_status,province,city,district,village,postal_code,address,emergency_contact_name,emergency_contact_phone,study_program_id,academic_rank,functional_position,employment_status,employment_type,employment_start_date,highest_education,expertise,certification_number) VALUES(:uid,:number,:phone,:photo,:national_id,:birth_place,:birth_date,:gender,:religion,:marital_status,:province,:city,:district,:village,:postal_code,:address,:emergency_contact_name,:emergency_contact_phone,:study_program_id,:academic_rank,:functional_position,:employment_status,:employment_type,:employment_start_date,:highest_education,:expertise,:certification_number)';
                $q=$pdo->prepare($sql);
                $q->execute(['uid'=>$userId,'number'=>$number?:null,'phone'=>$phone?:null,'photo'=>$uploadedPhoto]+array_map(static fn($v)=>$v===''?null:$v,$fields));
            }
            $pdo->commit();flash($action==='save_student'?'Data mahasiswa dan akun berhasil dibuat.':($action==='save_lecturer'?'Data dosen dan akun berhasil dibuat.':'Akun berhasil dibuat.'));
        } catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('[STIT ADMIN CREATE USER] '.$e->getMessage());flash('Gagal membuat data. Periksa username, email, NIM/NIDN agar tidak duplikat dan pastikan relasi data valid.','error');}
        go($action==='save_student'?'students':($action==='save_lecturer'?'lecturers':'accounts'));
    }
    if ($action==='save_course') {
        allow($academicRoles);
        $program=(int)($_POST['study_program_id']??0);$code=strtoupper(trim((string)($_POST['code']??'')));$name=trim((string)($_POST['name']??''));$credits=(int)($_POST['credits']??0);$recommended=(int)($_POST['recommended_semester']??0);
        if($program<1||$code===''||$name===''||strlen($code)>30||strlen($name)>180||$credits<1||$credits>12||$recommended<0||$recommended>8){flash('Data mata kuliah tidak valid.','error');go('courses');}
        try{$q=$pdo->prepare('INSERT INTO courses(study_program_id,code,name,credits,recommended_semester) VALUES(:program,:code,:name,:credits,:semester)');$q->execute(['program'=>$program,'code'=>$code,'name'=>$name,'credits'=>$credits,'semester'=>$recommended?:null]);flash('Mata kuliah berhasil ditambahkan.');}
        catch(Throwable $e){flash('Gagal menyimpan mata kuliah. Pastikan kode belum digunakan dan program studi tersedia.','error');}
        go('courses');
    }
    if ($action==='save_schedule') {
        allow($academicRoles);
        $classId=(int)($_POST['course_class_id']??0);$day=(int)($_POST['day_of_week']??0);$start=(string)($_POST['start_time']??'');$end=(string)($_POST['end_time']??'');$room=trim((string)($_POST['room']??''));
        if($classId<1||$day<1||$day>7||!preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/',$start)||!preg_match('/^([01]\\d|2[0-3]):[0-5]\\d$/',$end)||$start>=$end||strlen($room)>100){flash('Data jadwal tidak valid.','error');go('schedules');}
        try{$q=$pdo->prepare('INSERT INTO class_schedules(course_class_id,day_of_week,start_time,end_time,room) VALUES(:class,:day,:start,:end,:room)');$q->execute(['class'=>$classId,'day'=>$day,'start'=>$start,'end'=>$end,'room'=>$room?:null]);flash('Jadwal kuliah berhasil ditambahkan.');}
        catch(Throwable $e){flash('Gagal menambahkan jadwal. Periksa kelas kuliah dan benturan jadwal secara manual.','error');}
        go('schedules');
    }
    if ($action==='save_class') {
        allow($academicRoles);
        $course=(int)($_POST['course_id']??0);$semester=(int)($_POST['semester_id']??0);$lecturer=(int)($_POST['lecturer_id']??0);$class=trim((string)($_POST['class_name']??''));$capacity=(int)($_POST['capacity']??0);
        if($course<1||$semester<1||$class===''||strlen($class)>40||$capacity<0||$capacity>500){flash('Data kelas kuliah tidak valid.','error');go('classes');}
        try{$q=$pdo->prepare('INSERT INTO course_classes(course_id,semester_id,lecturer_id,class_name,capacity) VALUES(:course,:semester,:lecturer,:class,:capacity)');$q->execute(['course'=>$course,'semester'=>$semester,'lecturer'=>$lecturer?:null,'class'=>$class,'capacity'=>$capacity?:null]);flash('Kelas kuliah berhasil dibuat.');}
        catch(Throwable $e){flash('Gagal membuat kelas. Periksa mata kuliah, semester, dan dosen pengampu.','error');}
        go('classes');
    }
    if ($action==='save_krs') {
        allow(['super_admin','admin_akademik']);
        $student=(int)($_POST['student_id']??0);$semester=(int)($_POST['semester_id']??0);$class=(int)($_POST['course_class_id']??0);
        if($student<1||$semester<1||$class<1){flash('Mahasiswa, semester, dan kelas wajib dipilih.','error');go('krs');}
        try{
            $pdo->beginTransaction();
            $q=$pdo->prepare('SELECT id,status FROM study_plans WHERE student_id=:student AND semester_id=:semester FOR UPDATE');
            $q->execute(['student'=>$student,'semester'=>$semester]);$existing=$q->fetch();
            if($existing && $existing['status']!=='submitted' && $existing['status']!=='draft')throw new RuntimeException('KRS sudah diproses dan tidak dapat diubah.');
            if($existing){$plan=(int)$existing['id'];}
            else{$q=$pdo->prepare("INSERT INTO study_plans(student_id,semester_id,status,submitted_at) VALUES(:student,:semester,'submitted',UTC_TIMESTAMP())");$q->execute(['student'=>$student,'semester'=>$semester]);$plan=(int)$pdo->lastInsertId();}
            $q=$pdo->prepare('INSERT INTO study_plan_details(study_plan_id,course_class_id) SELECT :plan,id FROM course_classes WHERE id=:class AND semester_id=:semester');
            $q->execute(['plan'=>$plan,'class'=>$class,'semester'=>$semester]);
            if($q->rowCount()!==1)throw new RuntimeException('Kelas tidak termasuk semester yang dipilih atau sudah ada di KRS.');
            $pdo->commit();flash('Mata kuliah berhasil ditambahkan ke KRS.');
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();flash('Gagal menambahkan mata kuliah. Periksa semester, status KRS, dan pastikan kelas belum tercatat.','error');}
        go('krs');
    }
    if ($action==='review_krs') {
        allow(['super_admin','admin_akademik']);
        $plan=(int)($_POST['plan_id']??0);$decision=(string)($_POST['decision']??'');
        if($plan<1||!in_array($decision,['approved','rejected'],true)){flash('Keputusan KRS tidak valid.','error');go('krs');}
        $q=$pdo->prepare("UPDATE study_plans SET status=:status,approved_by=:user,approved_at=UTC_TIMESTAMP() WHERE id=:id AND status='submitted'");
        $q->execute(['status'=>$decision,'user'=>$u['id'],'id'=>$plan]);
        flash($q->rowCount()?'Status KRS berhasil diperbarui.':'KRS tidak ditemukan atau sudah diproses.',$q->rowCount()?'success':'error');go('krs');
    }
    if ($action==='save_grade') {
        allow(['super_admin','admin_akademik','dosen']);
        $student=(int)($_POST['student_id']??0);$class=(int)($_POST['course_class_id']??0);$score=filter_var($_POST['numeric_score']??null,FILTER_VALIDATE_FLOAT);
        if($student<1||$class<1||$score===false||$score<0||$score>100){flash('Nilai harus berupa angka 0 sampai 100.','error');go('grades');}
        $ownership='';
        if($u['role']==='dosen')$ownership=' AND EXISTS(SELECT 1 FROM course_classes cc JOIN lecturers l ON l.id=cc.lecturer_id WHERE cc.id=:owned_class AND l.user_id=:owner)';
        $q=$pdo->prepare("SELECT COUNT(*) FROM study_plan_details d JOIN study_plans p ON p.id=d.study_plan_id WHERE d.course_class_id=:class AND p.student_id=:student AND p.status='approved'".($ownership));
        $params=['class'=>$class,'student'=>$student];if($ownership){$params['owned_class']=$class;$params['owner']=$u['id'];}
        $q->execute($params);
        if(!(int)$q->fetchColumn()){flash('Nilai hanya dapat diinput untuk mahasiswa yang KRS-nya disetujui pada kelas tersebut.','error');go('grades');}
        $letter=$score>=85?'A':($score>=80?'AB':($score>=75?'B':($score>=70?'BC':($score>=65?'C':($score>=50?'D':'E')))));
        $points=['A'=>4.0,'AB'=>3.5,'B'=>3.0,'BC'=>2.5,'C'=>2.0,'D'=>1.0,'E'=>0.0][$letter];
        try{$q=$pdo->prepare('INSERT INTO grades(student_id,course_class_id,numeric_score,letter_grade,grade_points,published_at) VALUES(:student,:class,:score,:letter,:points,NULL) ON DUPLICATE KEY UPDATE numeric_score=VALUES(numeric_score),letter_grade=VALUES(letter_grade),grade_points=VALUES(grade_points),published_at=NULL');$q->execute(['student'=>$student,'class'=>$class,'score'=>$score,'letter'=>$letter,'points'=>$points]);flash('Nilai tersimpan sebagai draft. Publikasikan setelah pemeriksaan.');}
        catch(Throwable $e){flash('Gagal menyimpan nilai. Periksa kelas dan data mahasiswa.','error');}
        go('grades');
    }
    if ($action==='publish_grade') {
        allow(['super_admin','admin_akademik']);
        $grade=(int)($_POST['grade_id']??0);$q=$pdo->prepare('UPDATE grades SET published_at=UTC_TIMESTAMP() WHERE id=:id');$q->execute(['id'=>$grade]);flash($q->rowCount()?'Nilai berhasil dipublikasikan.':'Nilai tidak ditemukan atau sudah dipublikasikan.',$q->rowCount()?'success':'error');go('grades');
    }
    if ($action==='save_attendance') {
        allow(['super_admin','admin_akademik','dosen']);
        $student=(int)($_POST['student_id']??0);$class=(int)($_POST['course_class_id']??0);$meeting=(int)($_POST['meeting_number']??0);$status=(string)($_POST['attendance_status']??'');
        if($student<1||$class<1||$meeting<1||$meeting>32||!in_array($status,['present','sick','permission','absent'],true)){flash('Data presensi tidak valid.','error');go('attendance');}
        $owned=$u['role']==='dosen'?' AND EXISTS(SELECT 1 FROM course_classes cc JOIN lecturers l ON l.id=cc.lecturer_id WHERE cc.id=:owned_class AND l.user_id=:owner)':'';
        $q=$pdo->prepare("SELECT COUNT(*) FROM study_plan_details d JOIN study_plans p ON p.id=d.study_plan_id WHERE d.course_class_id=:class AND p.student_id=:student AND p.status='approved'".$owned);
        $params=['class'=>$class,'student'=>$student];if($owned){$params['owned_class']=$class;$params['owner']=$u['id'];}$q->execute($params);
        if(!(int)$q->fetchColumn()){flash('Mahasiswa tidak terdaftar pada kelas melalui KRS yang disetujui.','error');go('attendance');}
        try{$q=$pdo->prepare('INSERT INTO attendance(student_id,course_class_id,meeting_number,attendance_status,recorded_at,recorded_by) VALUES(:student,:class,:meeting,:status,UTC_TIMESTAMP(),:recorder) ON DUPLICATE KEY UPDATE attendance_status=VALUES(attendance_status),recorded_at=UTC_TIMESTAMP(),recorded_by=VALUES(recorded_by)');$q->execute(['student'=>$student,'class'=>$class,'meeting'=>$meeting,'status'=>$status,'recorder'=>$u['id']]);flash('Presensi berhasil disimpan.');}
        catch(Throwable $e){flash('Gagal menyimpan presensi.','error');}
        go('attendance');
    }
    if ($action==='save_semester') {
        allow($academicRoles);
        $year=(int)($_POST['academic_year_id']??0); $name=(string)($_POST['name']??''); $start=(string)($_POST['start_date']??''); $end=(string)($_POST['end_date']??'');
        if(!$year || !in_array($name,['Semester 1','Semester 2','Semester 3','Semester 4','Semester 5','Semester 6','Semester 7','Semester 8'],true) || !$start || !$end || $start>$end) { flash('Data semester tidak valid.','error'); go('semesters'); }
        try {$q=$pdo->prepare('INSERT INTO semesters(academic_year_id,name,start_date,end_date,is_active) VALUES(:year,:name,:start,:end,0)');$q->execute(['year'=>$year,'name'=>$name,'start'=>$start,'end'=>$end]);flash('Semester berhasil ditambahkan.');}
        catch(Throwable $e){flash('Gagal menambahkan semester. Kombinasi tahun dan semester mungkin sudah ada.','error');}
        go('semesters');
    }
}

$u=user();
if ($page!=='login' && !$u) go('login');
if ($page==='login' && $u) go('dashboard');
$flash=$_SESSION['flash']??null; unset($_SESSION['flash']);
$campusLogoUrl='../../website/assets/stit-darul-ilmi-icon.svg';
try { $savedLogo=$pdo->query('SELECT logo_image_url FROM campus_profile WHERE id=1')->fetchColumn(); if(is_string($savedLogo)&&trim($savedLogo)!=='') $campusLogoUrl=trim($savedLogo); } catch(Throwable $logoError) { error_log('[STIT ADMIN LOGO] '.$logoError->getMessage()); }
$labels=['super_admin'=>'Super Admin','admin_akademik'=>'Admin Akademik','admin_keuangan'=>'Admin Keuangan','ketua_prodi'=>'Ketua Program Studi','ketua_stit'=>'Ketua STIT','dosen'=>'Dosen'];
$menus=[
 'dashboard'=>'Dashboard','programs'=>'Program Studi','years'=>'Tahun Akademik','semesters'=>'Semester',
 'students'=>'Mahasiswa','lecturers'=>'Dosen','courses'=>'Mata Kuliah','classes'=>'Kelas Kuliah','schedules'=>'Jadwal Kuliah','krs'=>'KRS','grades'=>'Input Nilai','khs'=>'KHS / IP Semester','ipk'=>'Rekap IPK','attendance'=>'Presensi','accounts'=>'Akun Pengguna','pmb'=>'PMB','news'=>'Berita Kampus','migrations'=>'Migrasi Database','system'=>'Pengaturan Tema'
];
$mobileQuick = [
    ['page'=>'dashboard','label'=>'Beranda','icon'=>'⌂'],
    ['page'=>in_array($u['role'],$academicRoles,true)?'students':(in_array($u['role'],['super_admin','admin_akademik','dosen'],true)?'grades':'dashboard'),'label'=>in_array($u['role'],$academicRoles,true)?'Data':'Nilai','icon'=>'▦'],
    ['page'=>in_array($u['role'],$academicRoles,true)?'krs':(in_array($u['role'],['super_admin','admin_akademik','dosen'],true)?'attendance':'dashboard'),'label'=>in_array($u['role'],$academicRoles,true)?'Akademik':'Presensi','icon'=>'▤'],
    ['page'=>in_array($u['role'],['super_admin','admin_akademik','ketua_prodi','ketua_stit'],true)?'khs':'dashboard','label'=>'Laporan','icon'=>'▥'],
];
// Samakan daftar menu mobile dengan guard akses halaman di bawah agar tidak
// menampilkan tautan yang pasti ditolak oleh server.
$mobileAllowed = static function(string $key) use ($u,$academicRoles,$viewRoles): bool {
    $role=(string)$u['role'];
    if($key==='system') return in_array($role,['super_admin','admin_akademik','admin_keuangan','ketua_prodi','ketua_stit','dosen'],true);
    if($key==='migrations') return $role==='super_admin';
    if(in_array($key,['programs','years','semesters','courses','classes','schedules','students','lecturers','accounts','krs','news','pmb'],true)) return in_array($role,$academicRoles,true);
    if(in_array($key,['grades','attendance'],true)) return in_array($role,['super_admin','admin_akademik','dosen'],true);
    if(in_array($key,['khs','ipk'],true)) return in_array($role,$viewRoles,true);
    if($key==='dashboard') return in_array($role,['super_admin','admin_akademik','admin_keuangan','ketua_prodi','ketua_stit','dosen'],true);
    return in_array($role,$viewRoles,true);
};
$menuGroups = [
    'Menu Akademik' => ['programs','years','semesters','students','lecturers','courses','classes','schedules','krs','grades','attendance'],
    'Menu Administrasi' => ['accounts','pmb','news'],
    'Menu Laporan' => ['khs','ipk'],
    'Menu Sistem' => ['system','migrations'],
];
$visibleMenuGroups = [];
foreach ($menuGroups as $groupLabel => $groupKeys) {
    $visibleKeys = array_values(array_filter($groupKeys, static fn(string $key): bool => $mobileAllowed($key)));
    if ($visibleKeys) $visibleMenuGroups[$groupLabel] = $visibleKeys;
}
if ($page==='login') {
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Admin - STIT Darul Ilmi</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:radial-gradient(circle at 12% 12%,#16885b 0,transparent 34%),linear-gradient(135deg,#03452f,#087a4b 58%,#b9e66b);font:15px system-ui;color:#173b2b}.login{width:min(460px,94vw);background:#fff;padding:36px;border:1px solid #e1eee5;border-radius:28px;box-shadow:0 24px 80px #002b1c66}.mark-wrap{display:flex;justify-content:center;align-items:center;margin:0 auto 22px}.campus-logo{display:block;width:142px;height:142px;max-width:48%;object-fit:contain}.login h1{font-size:27px;line-height:1.15;color:#075b3d;margin:22px 0 8px}.muted{color:#64756b;line-height:1.55}label{display:block;font-weight:700;margin:17px 0 7px;color:#234b37}input{width:100%;padding:14px 15px;border:1px solid #d4e5d9;border-radius:13px;font:inherit;background:#fbfefc;outline:none}input:focus{border-color:#078653;box-shadow:0 0 0 3px #07865320}button{width:100%;margin-top:24px;padding:14px;border:0;border-radius:30px;background:linear-gradient(90deg,#087a4b,#03452f);color:white;font-weight:800;font-size:15px;cursor:pointer;box-shadow:0 8px 20px #075b3d30}.alert{padding:12px 14px;border-radius:12px;background:#fff0ee;color:#a32118;margin:14px 0}.login-foot{border-top:1px solid #e8f1eb;margin-top:24px;padding-top:16px;font-size:12px}@media(max-width:760px){
  #mobileMenuSheet:not([hidden]){
    position:fixed!important;z-index:1002!important;top:0!important;left:0!important;right:auto!important;bottom:0!important;
    width:clamp(300px,59vw,410px)!important;max-width:88vw!important;height:100vh!important;height:100dvh!important;min-height:100dvh!important;max-height:100dvh!important;
    box-sizing:border-box!important;margin:0!important;padding:calc(14px + env(safe-area-inset-top)) 22px calc(14px + env(safe-area-inset-bottom))!important;
    display:flex!important;flex-direction:column!important;gap:0!important;overflow:hidden!important;
    color:#eaf4ef!important;background:linear-gradient(160deg,#111820 0%,#0b1118 58%,#080e14 100%)!important;
    border:1px solid rgba(170,205,188,.12)!important;border-left:0!important;border-radius:0 22px 22px 0!important;
    box-shadow:18px 0 48px rgba(0,0,0,.34)!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand{
    position:relative;isolation:isolate;min-height:112px!important;padding:14px 4px 18px!important;margin:calc(-1 * (14px + env(safe-area-inset-top))) -22px 0!important;
    padding-top:calc(14px + env(safe-area-inset-top))!important;padding-left:22px!important;padding-right:22px!important;
    background:radial-gradient(ellipse at 82% 20%,rgba(49,226,172,.36),transparent 48%),linear-gradient(130deg,#064d43 0%,#08785e 62%,#0b4b45 100%)!important;
    border:0!important;overflow:hidden;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand:after{content:"";position:absolute;z-index:-1;right:-28px;bottom:-54px;width:190px;height:110px;border-radius:50%;background:rgba(4,43,43,.28);transform:rotate(-18deg)}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand img{width:58px!important;height:58px!important;border-radius:50%!important;background:rgba(255,255,255,.12)!important;border:1px solid rgba(255,255,255,.22)!important;padding:3px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand strong{font-size:14px!important;letter-spacing:.045em!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand span{font-size:10px!important;letter-spacing:.04em!important;color:#d1f7e8!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-close{background:rgba(255,255,255,.12)!important;border-color:rgba(255,255,255,.2)!important;color:#fff!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile{padding:15px 4px!important;margin:0 0 12px!important;border-bottom:1px solid #2a3840!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home{min-height:48px!important;margin-bottom:14px!important;border-radius:11px!important;background:linear-gradient(100deg,#075b4a,#103c3a)!important;border-color:#176b57!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups{flex:1 1 auto!important;min-height:0!important;overflow-y:auto!important;overscroll-behavior:contain!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-group{margin-bottom:13px!important;padding-bottom:11px!important;border-bottom:1px solid #2a3840!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-group h3{font-size:10px!important;margin-bottom:7px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a{min-height:42px!important;font-size:13px!important;padding:8px 10px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-icon{width:23px!important;height:23px!important;flex-basis:23px!important;color:#38dcae!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-icon svg{width:21px!important;height:21px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout{margin-top:8px!important;padding-top:12px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button{min-height:46px!important;font-size:13px!important}
}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand{flex:0 0 auto;min-height:64px;align-items:center;padding:4px 0 14px;border-bottom:1px solid #2a3840}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand img{width:46px;height:46px;border-radius:12px}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand strong{font-size:12px;letter-spacing:.07em}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand span{font-size:9px;letter-spacing:.13em}
  #mobileMenuSheet:not([hidden]) .mobile-menu-close{width:36px!important;height:36px!important;flex-basis:36px!important;border-radius:10px!important;background:#1b2930!important;border:1px solid #34434a!important;color:#e1eee7!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile{flex:0 0 auto;padding:13px 2px;margin:0 0 12px;border-bottom:1px solid #2a3840}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-avatar{width:40px;height:40px;flex-basis:40px}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile strong{font-size:13px}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile span{font-size:10px}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home{flex:0 0 auto;min-height:44px;margin:0 0 12px;padding:9px 11px;border-radius:10px;background:#173a34;border:1px solid #24584d;font-size:13px}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups{display:block!important;flex:1 1 auto!important;min-height:0!important;overflow-y:auto!important;overflow-x:hidden!important;overscroll-behavior:contain!important;padding:0 2px 10px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-group{margin:0 0 10px;padding:0 0 9px;border-bottom:1px solid #2a3840}
  #mobileMenuSheet:not([hidden]) .mobile-menu-group h3{margin:0 0 5px;padding:0 8px;color:#91a79c;font-size:9px;letter-spacing:.12em;line-height:1.5}
  #mobileMenuSheet:not([hidden]) .mobile-menu-group h3:before{height:11px;width:3px;margin-right:7px;background:#23c99a}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid{display:grid!important;grid-template-columns:minmax(0,1fr)!important;gap:2px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a{display:flex!important;min-height:40px!important;padding:7px 9px!important;gap:10px!important;border:1px solid transparent!important;border-radius:9px!important;background:transparent!important;color:#d8e4de!important;box-shadow:none!important;font-size:12px!important;font-weight:600!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-icon{width:21px;height:21px;flex-basis:21px;color:#42d8aa}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-icon svg{width:19px;height:19px}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-label{flex:1;min-width:0;line-height:1.35}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-chevron{font-size:19px;color:#82968c}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active{background:#17463b!important;border-color:#286c57!important;color:#fff!important;box-shadow:inset 3px 0 #39d9aa!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active .mobile-drawer-icon{color:#68efc2}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout{flex:0 0 auto;margin:8px 0 0;padding-top:10px;border-top:1px solid #2a3840}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button{min-height:42px;margin:0;border-radius:10px;background:#351d27;border:1px solid #5c2a3a;color:#ff8798;font-size:12px}
}
@media(max-width:760px){
#mobileMenuSheet:not([hidden]){left:0!important;right:auto!important;width:clamp(300px,59vw,410px)!important;max-width:88vw!important;border-radius:0 22px 22px 0!important;border-left:0!important;border-right:1px solid rgba(170,205,188,.12)!important;box-shadow:18px 0 48px rgba(0,0,0,.34)!important}
}
/* Final green-theme cleanup: override legacy dark mobile drawer styles */
@media(max-width:760px){
 html[data-theme="green"] body{background:#f3faf5!important;color:#174b35!important}
 html[data-theme="green"] .mobile-bottom-nav{background:#fff!important;color:#174b35!important;border-top:1px solid #cfe5d6!important;box-shadow:0 -8px 24px rgba(7,91,61,.10)!important}
 html[data-theme="green"] .mobile-nav-item{background:transparent!important;color:#527263!important}
 html[data-theme="green"] .mobile-nav-item.active{background:#e4f5e9!important;color:#087a4b!important}
 html[data-theme="green"] .mobile-menu-backdrop,#mobileMenuBackdrop{background:rgba(7,65,43,.34)!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]){background:#fff!important;color:#174b35!important;border:1px solid #cfe5d6!important;border-radius:0 20px 20px 0!important;box-shadow:18px 0 48px rgba(7,91,61,.18)!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-heading{background:#fff!important;color:#075b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-brand{background:linear-gradient(135deg,#075b3d,#0b955d)!important;color:#fff!important;border-color:#d5e9dc!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile{background:#fff!important;color:#174b35!important;border-color:#d5e9dc!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile strong,html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile small{color:#174b35!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-home{background:#e6f5eb!important;border:1px solid #cfe5d6!important;color:#075b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-group{background:#fff!important;border-color:#d8ebdf!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-group h3{color:#087a4b!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a{background:#f4faf6!important;border-color:#d8ebdf!important;color:#175b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active{background:#dff3e5!important;border-color:#9bcfae!important;color:#05613e!important;box-shadow:inset 3px 0 #0b955d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-icon{color:#087a4b!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-close{background:#e5f4e9!important;border-color:#c6e2d0!important;color:#075b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-logout{background:#fff!important;border-color:#d8ebdf!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button{background:#fff1ee!important;border-color:#f2c8bf!important;color:#a23a2a!important}
 html[data-theme="green"] .account-modal,html[data-theme="green"] .account-modal .modal-content{background:#fff!important;color:#174b35!important}
}
html[data-theme="green"] .card,html[data-theme="green"] .panel,html[data-theme="green"] .stat,html[data-theme="green"] .item,html[data-theme="green"] .modal,html[data-theme="green"] .modal-content{background:#fff!important;color:#174b35!important}
html[data-theme="green"] .content,html[data-theme="green"] .main{background:#f3faf5!important;color:#174b35!important}


/* Student detail modal: open by tapping a student's name in the data table. */
.student-name-link{color:var(--primary,#176b50);font-weight:700;text-decoration:none;border-bottom:1px dashed currentColor;cursor:pointer}
.student-name-link:hover{text-decoration:underline}
.student-detail-backdrop{position:fixed;inset:0;z-index:10000;background:rgba(13,35,29,.58);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:clamp(10px,3vw,28px);overflow-y:auto}
.student-detail-modal{position:relative;width:min(920px,100%);max-height:min(92dvh,900px);overflow-y:auto;margin:auto;background:var(--surface,#fff);border:1px solid rgba(20,90,66,.18);border-radius:22px;box-shadow:0 24px 80px rgba(0,0,0,.25);padding:clamp(16px,3vw,28px)}
.student-detail-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px}
.student-detail-heading h2{margin:4px 0 0}
.student-detail-close{flex:0 0 40px;width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#edf6f0;color:#245b49;text-decoration:none;font-size:28px;line-height:1}
.student-detail-modal form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.student-detail-modal form>div{min-width:0}
.student-detail-modal form input,.student-detail-modal form select{width:100%;max-width:100%;box-sizing:border-box}
.student-detail-actions{grid-column:1/-1;display:flex;align-items:center;gap:12px;position:sticky;bottom:-1px;padding:14px 0 2px;background:var(--surface,#fff)}
.student-detail-cancel{padding:10px 16px;border-radius:10px;background:#edf2ef;color:#34564a;text-decoration:none;font-weight:700}
@media(max-width:600px){.student-detail-backdrop{align-items:flex-end;padding:0}.student-detail-modal{width:100%;max-height:94dvh;border-radius:22px 22px 0 0;padding:18px 16px calc(20px + env(safe-area-inset-bottom));}.student-detail-modal form{grid-template-columns:1fr;gap:12px}.student-detail-actions{bottom:0}.student-detail-actions button{flex:1;min-height:46px}}
</style></head><body><main class="login"><div class="mark-wrap"><img class="campus-logo" src="<?=e($campusLogoUrl)?>" alt="Logo STIT Darul Ilmi Tasikmalaya"></div><p style="color:#078653;font-weight:800;letter-spacing:.12em;font-size:11px;margin:20px 0 0">PORTAL AKADEMIK TERPADU</p><h1>Selamat Datang<br>Admin STIT Darul Ilmi</h1><p class="muted">Masuk untuk mengelola layanan akademik dan informasi kampus STIT Darul Ilmi Tasikmalaya.</p><?php if($flash):?><div class="alert"><?=e($flash['message'])?></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="login"><label>Username</label><input name="username" autocomplete="username" placeholder="Masukkan username" required><label>Password</label><input type="password" name="password" autocomplete="current-password" placeholder="Masukkan password" required><button type="submit">Masuk ke Dashboard　›</button></form><div class="login-foot muted">Gunakan akun admin yang dibuat melalui prosedur setup backend. Tidak ada akun bawaan.<br><a href="../../portal/public/?page=login" style="display:inline-block;margin-top:12px;color:#087a4b;font-weight:800">Login Mahasiswa / Dosen →</a></div></main></body></html><?php exit;
}
$datasets=[
 'programs'=>['title'=>'Program Studi','sql'=>'SELECT id,code,name,degree_level,is_active FROM study_programs ORDER BY name'],
 'years'=>['title'=>'Tahun Akademik','sql'=>'SELECT id,name,start_date,end_date,is_active FROM academic_years ORDER BY start_date DESC'],
 'semesters'=>['title'=>'Semester','sql'=>'SELECT s.id,y.name AS academic_year,s.name,s.start_date,s.end_date,s.is_active FROM semesters s JOIN academic_years y ON y.id=s.academic_year_id ORDER BY s.start_date DESC'],
 'students'=>['title'=>'Mahasiswa','sql'=>'SELECT s.nim,u.full_name,p.name AS program,s.entry_year,s.phone,s.birth_place,s.birth_date,s.gender,s.city,s.district,s.admission_pathway,s.status FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN study_programs p ON p.id=s.study_program_id ORDER BY u.full_name LIMIT 200'],
 'lecturers'=>['title'=>'Dosen','sql'=>'SELECT l.lecturer_number,u.full_name,u.email,l.phone,l.photo_url FROM lecturers l JOIN users u ON u.id=l.user_id ORDER BY u.full_name LIMIT 200'],
 'courses'=>['title'=>'Mata Kuliah','sql'=>'SELECT c.code,c.name,c.credits,p.name AS program,c.recommended_semester FROM courses c JOIN study_programs p ON p.id=c.study_program_id ORDER BY p.name,c.recommended_semester,c.name LIMIT 300'],
 'classes'=>['title'=>'Kelas Kuliah','sql'=>'SELECT c.class_name,co.code,co.name AS course,s.name AS semester,u.full_name AS lecturer,c.capacity FROM course_classes c JOIN courses co ON co.id=c.course_id JOIN semesters s ON s.id=c.semester_id LEFT JOIN lecturers l ON l.id=c.lecturer_id LEFT JOIN users u ON u.id=l.user_id ORDER BY s.start_date DESC LIMIT 300'],
 'schedules'=>['title'=>'Jadwal Kuliah','sql'=>'SELECT cs.id,cc.class_name,c.code,c.name AS course,CASE cs.day_of_week WHEN 1 THEN \'Senin\' WHEN 2 THEN \'Selasa\' WHEN 3 THEN \'Rabu\' WHEN 4 THEN \'Kamis\' WHEN 5 THEN \'Jumat\' WHEN 6 THEN \'Sabtu\' ELSE \'Minggu\' END AS hari,cs.start_time,cs.end_time,cs.room FROM class_schedules cs JOIN course_classes cc ON cc.id=cs.course_class_id JOIN courses c ON c.id=cc.course_id ORDER BY cs.day_of_week,cs.start_time LIMIT 300'],
 'krs'=>['title'=>'Kartu Rencana Studi','sql'=>'SELECT p.id,s.nim,u.full_name AS mahasiswa,y.name AS tahun_akademik,se.name AS semester,p.status,p.submitted_at FROM study_plans p JOIN students s ON s.id=p.student_id JOIN users u ON u.id=s.user_id JOIN semesters se ON se.id=p.semester_id JOIN academic_years y ON y.id=se.academic_year_id ORDER BY p.id DESC LIMIT 300'],
 'grades'=>['title'=>'Nilai / KHS','sql'=>'SELECT g.id,s.nim,u.full_name AS mahasiswa,c.code,c.name AS mata_kuliah,cc.class_name,g.numeric_score,g.letter_grade,g.grade_points,g.published_at FROM grades g JOIN students s ON s.id=g.student_id JOIN users u ON u.id=s.user_id JOIN course_classes cc ON cc.id=g.course_class_id JOIN courses c ON c.id=cc.course_id ORDER BY g.id DESC LIMIT 300'],
 'khs'=>['title'=>'Kartu Hasil Studi (KHS)','sql'=>'SELECT s.nim,u.full_name AS mahasiswa,y.name AS tahun_akademik,se.name AS semester,COUNT(g.id) AS mata_kuliah,SUM(c.credits) AS total_sks,ROUND(SUM(g.grade_points*c.credits)/NULLIF(SUM(c.credits),0),2) AS ip FROM grades g JOIN students s ON s.id=g.student_id JOIN users u ON u.id=s.user_id JOIN course_classes cc ON cc.id=g.course_class_id JOIN courses c ON c.id=cc.course_id JOIN semesters se ON se.id=cc.semester_id JOIN academic_years y ON y.id=se.academic_year_id WHERE g.published_at IS NOT NULL GROUP BY s.id,u.full_name,s.nim,se.id,y.name,se.name ORDER BY y.name DESC,se.name,s.nim'],
 'ipk'=>['title'=>'Rekap Indeks Prestasi Kumulatif','sql'=>'SELECT s.nim,u.full_name AS mahasiswa,COUNT(g.id) AS mata_kuliah,SUM(c.credits) AS total_sks,ROUND(SUM(g.grade_points*c.credits)/NULLIF(SUM(c.credits),0),2) AS ipk FROM grades g JOIN students s ON s.id=g.student_id JOIN users u ON u.id=s.user_id JOIN course_classes cc ON cc.id=g.course_class_id JOIN courses c ON c.id=cc.course_id WHERE g.published_at IS NOT NULL GROUP BY s.id,u.full_name,s.nim ORDER BY s.nim'],
 'attendance'=>['title'=>'Presensi','sql'=>'SELECT a.id,s.nim,u.full_name AS mahasiswa,c.name AS mata_kuliah,cc.class_name,a.meeting_number,a.attendance_status,a.recorded_at FROM attendance a JOIN students s ON s.id=a.student_id JOIN users u ON u.id=s.user_id JOIN course_classes cc ON cc.id=a.course_class_id JOIN courses c ON c.id=cc.course_id ORDER BY a.recorded_at DESC LIMIT 300'],
 'accounts'=>['title'=>'Akun Pengguna','sql'=>'SELECT u.id,u.username,u.full_name,u.email,r.name AS role,u.status,u.created_at,u.last_login_at,(SELECT p.name FROM user_program_scopes ups JOIN study_programs p ON p.id=ups.study_program_id WHERE ups.user_id=u.id LIMIT 1) AS program_studi FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.created_at DESC LIMIT 300'],
 'news'=>['title'=>'Berita Kampus','sql'=>'SELECT id,title,category,published_at,created_at FROM news_posts ORDER BY created_at DESC LIMIT 100'],
 'pmb'=>['title'=>'Penerimaan Mahasiswa Baru','sql'=>'SELECT registration_number,full_name,email,phone,status,created_at FROM pmb_applicants ORDER BY created_at DESC LIMIT 300']
];
if ($page!=='dashboard' && $page!=='migrations' && $page!=='system' && !isset($datasets[$page])) $page='dashboard';
$programScopeId=null;
if ($u['role']==='ketua_prodi') {
    $scopeQuery=$pdo->prepare('SELECT study_program_id FROM user_program_scopes WHERE user_id=:uid LIMIT 1');
    $scopeQuery->execute(['uid'=>$u['id']]);
    $scopeValue=$scopeQuery->fetchColumn();
    if(!$scopeValue) { http_response_code(403); exit('Akun Ketua Prodi belum diberi cakupan program studi. Hubungi Admin Akademik.'); }
    $programScopeId=(int)$scopeValue;
}
if ($page==='system') allow(['super_admin','admin_akademik','admin_keuangan','ketua_prodi','ketua_stit','dosen']); elseif ($page==='migrations') allow(['super_admin']); elseif (in_array($page,['programs','years','semesters','courses','classes','schedules','students','lecturers','accounts','krs','news','pmb'],true)) allow($academicRoles); elseif (in_array($page,['grades','attendance'],true)) allow(['super_admin','admin_akademik','dosen']); elseif (in_array($page,['khs','ipk'],true)) allow(['super_admin','admin_akademik','ketua_prodi','ketua_stit']); elseif ($page==='dashboard') allow(['super_admin','admin_akademik','admin_keuangan','ketua_prodi','ketua_stit','dosen']); else allow($viewRoles);
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><script>try{document.documentElement.dataset.theme=localStorage.getItem('stit-admin-theme')||'green'}catch(e){document.documentElement.dataset.theme='green'}</script><title><?=e($menus[$page]??'Dashboard')?> - STIT Darul Ilmi</title><link rel="icon" href="<?=e($campusLogoUrl)?>"><style>
:root{--navy:#03452f;--teal:#087a4b;--bg:#f5faf6;--ink:#173b2b;--muted:#64756b;--line:#e1eee5;--mint:#eaf6ef;--lime:#b9e66b}*{box-sizing:border-box}body{margin:0;background:var(--bg);font:14px system-ui;color:var(--ink)}.layout{display:flex;min-height:100vh}.side{width:255px;background:linear-gradient(180deg,#03452f,#075b3d 70%,#087a4b);color:#fff;padding:24px 16px;flex-shrink:0;box-shadow:8px 0 28px #03452f12}.brand{display:flex;align-items:center;gap:11px;font-size:15px;font-weight:850;padding:5px 10px 25px;letter-spacing:.02em}.brand img{width:44px;height:44px;object-fit:contain;flex:0 0 44px;background:#fff;border-radius:10px;padding:3px}.brand-copy{min-width:0}.brand small{display:block;color:#c5e4d2;font-weight:500;font-size:12px;margin:5px 0 0}.nav a{display:block;color:#e0f0e6;text-decoration:none;padding:12px 14px;border-radius:12px;margin:4px 0;transition:.15s}.nav a.active,.nav a:hover{background:#ffffff20;color:#fff;box-shadow:inset 3px 0 #b9e66b}.nav-dashboard{margin-bottom:14px!important}.nav-group{margin:14px 0 18px;padding-top:12px;border-top:1px solid #ffffff24}.nav-group-title{padding:0 14px 7px;color:#b9e6ca;font-size:10px;font-weight:850;letter-spacing:.12em;text-transform:uppercase}.nav-group a{padding:10px 14px;margin:3px 0;font-size:13px}.mobile-menu-group{margin:0 0 22px}.mobile-menu-group h3{display:flex;align-items:center;gap:9px;margin:0 0 10px;padding:0 2px;color:#087a4b;font-size:11px;font-weight:900;letter-spacing:.11em;text-transform:uppercase}.mobile-menu-group h3:before{content:'';width:4px;height:15px;border-radius:4px;background:#36a66d}.mobile-menu-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:9px}.mobile-menu-grid a{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:52px;padding:12px 14px;border:1px solid #e5eee8;border-radius:14px;background:#fbfdfb;color:#234b37;text-decoration:none;font-weight:700;font-size:13px;box-shadow:0 2px 8px rgba(20,70,42,.035);transition:background .16s,border-color .16s,transform .16s}.mobile-menu-grid a:active{transform:scale(.985)}.mobile-menu-grid a span:last-child{color:#8aa694;font-size:21px;font-weight:500}.mobile-menu-grid a.active{background:#eaf6ef;border-color:#8fc5a3;color:#075b3d;box-shadow:inset 3px 0 #087a4b,0 3px 10px rgba(8,122,75,.07)}.mobile-menu-grid a.active span:last-child{color:#087a4b}.main{flex:1;min-width:0}.top{height:72px;background:#fff;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 30px;box-shadow:0 3px 16px #03452f08}.content{padding:28px 30px;max-width:1400px;margin:auto}h1{font-size:25px;margin:0 0 7px;color:var(--navy)}.muted{color:var(--muted)}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:15px;margin:24px 0}.card,.panel{background:#fff;border:1px solid var(--line);border-radius:18px;padding:19px;box-shadow:0 5px 18px #03452f08}.card strong{display:block;font-size:28px;margin-top:7px;color:var(--teal)}.panel{margin-top:20px;overflow:auto}.panel h2{font-size:17px;margin:0 0 16px;color:var(--navy)}table{border-collapse:collapse;width:100%;min-width:580px}th,td{text-align:left;padding:12px 13px;border-bottom:1px solid #edf3ee}th{background:var(--mint);color:#315b43;font-size:12px;text-transform:uppercase;letter-spacing:.03em}form.inline{display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-bottom:18px}form.inline label{display:block;font-size:12px;color:#475d50;margin-bottom:5px}input,select{padding:10px;border:1px solid #d4e5d9;border-radius:10px;font:inherit;background:#fcfefc}input:focus,select:focus{outline:0;border-color:var(--teal);box-shadow:0 0 0 3px #087a4b18}button,.btn{padding:10px 14px;border:0;border-radius:11px;background:var(--teal);color:white;font-weight:700;cursor:pointer;text-decoration:none}.alert{padding:12px 14px;border-radius:12px;margin:0 0 18px;background:#e8f6ee;color:#17663a}.alert.error{background:#fff0ee;color:#a32118}.logout{background:var(--mint);color:var(--navy)}.tag{background:var(--mint);color:var(--teal);border-radius:20px;padding:4px 9px;font-size:12px}.student-create-panel{padding:24px;overflow:visible}.student-form-heading{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;margin-bottom:20px}.student-form-heading h2{font-size:21px;margin:5px 0}.student-form-heading p{margin:0}.eyebrow{font-size:10px;letter-spacing:.14em;font-weight:850;color:var(--teal)}.student-tabs{display:flex;gap:8px;overflow-x:auto;padding:5px 2px 14px;border-bottom:1px solid var(--line);margin-bottom:22px}.student-tab{white-space:nowrap;background:#f2f8f4;color:#466451;border:1px solid #e0ece3;border-radius:12px;padding:11px 15px;margin:0}.student-tab.active{background:var(--navy);color:#fff;border-color:var(--navy);box-shadow:0 5px 14px #03452f20}.student-tab-panel{min-height:280px}.student-tab-panel[hidden]{display:none}.student-tab-panel h3{font-size:16px;margin:0 0 5px;color:var(--navy)}.student-tab-panel>p{margin:0 0 20px}.student-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.student-fields>div{min-width:0}.student-fields label,.student-photo-control label{display:block;font-size:12px;font-weight:750;color:#405b49;margin-bottom:7px}.student-fields input,.student-fields select{width:100%;min-height:43px}.student-profile-upload{display:flex;align-items:center;gap:20px;padding:16px;background:#f5faf6;border:1px dashed #bdd9c6;border-radius:15px;margin-bottom:20px}.student-avatar-preview{width:104px;height:104px;flex:0 0 104px;border-radius:50%;background:#e2f0e6;border:3px solid #fff;box-shadow:0 0 0 1px #cfe3d5;display:grid;place-items:center;overflow:hidden;color:#6a8a73;font-size:11px;font-weight:800}.student-avatar-preview img{width:100%;height:100%;object-fit:cover}.student-photo-control{flex:1;min-width:0}.student-photo-control input{display:block;width:100%;padding:9px;background:#fff;border:1px solid #d4e5d9;border-radius:10px}.student-photo-control small{display:block;margin-top:7px;line-height:1.5}.student-security-note{display:flex;flex-direction:column;gap:4px;background:#f0f8f2;border:1px solid #d7eadc;border-radius:12px;padding:13px;margin-top:20px;color:#47624e;font-size:12px}.student-form-footer{display:flex;justify-content:space-between;align-items:center;gap:14px;border-top:1px solid var(--line);padding-top:18px;margin-top:25px}.student-form-footer>div{display:flex;gap:8px}.student-form-footer button{margin:0;min-width:120px}.student-next{background:#eaf4ed;color:var(--navy)}.student-form-footer button[type=submit]{background:var(--teal)}@media(max-width:650px){.student-create-panel{padding:17px}.student-form-heading{display:block}.student-form-heading>.tag{display:inline-block;margin-top:10px}.student-fields{grid-template-columns:1fr}.student-tab{padding:10px 12px;font-size:12px}.student-profile-upload{align-items:flex-start;flex-direction:column}.student-form-footer{align-items:flex-start;flex-direction:column}.student-form-footer>div{width:100%}.student-form-footer button{flex:1}}@media(max-width:760px){.layout{display:block}.side{width:100%;padding:12px}.brand{padding:5px 8px 12px}.nav{display:flex;overflow:auto;gap:4px}.nav a{white-space:nowrap}.top{height:58px;padding:0 16px}.content{padding:20px 14px}}
@media(min-width:761px){.mobile-bottom-nav,.mobile-menu-sheet,.mobile-menu-backdrop{display:none!important}}
@media(max-width:760px){
  .mobile-bottom-nav{display:grid}
  .mobile-menu-sheet{display:block}
  .mobile-menu-backdrop{display:block}
  body{padding-bottom:calc(76px + env(safe-area-inset-bottom))}
  .layout{display:block;min-height:100dvh}
  .side{display:none}
  .main{width:100%;min-width:0}
  .top{height:60px;padding:0 16px;position:sticky;top:0;z-index:20}
  .content{padding:18px 14px calc(104px + env(safe-area-inset-bottom))}
  h1{font-size:22px}
  .cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:16px 0}
  .card,.panel{border-radius:15px;padding:15px}
  .card strong{font-size:24px}
  .panel{overflow:visible}
  .panel .table-wrap,.panel:has(table){overflow-x:auto}
  form.inline{display:grid;grid-template-columns:minmax(0,1fr);align-items:stretch;gap:12px}
  form.inline>div{width:100%;min-width:0}
  form.inline input:not([type=checkbox]):not([type=hidden]),form.inline select,form.inline textarea{width:100%;min-height:46px}
  form.inline button{width:100%;min-height:46px}
  .mobile-bottom-nav{position:fixed;z-index:1000;left:0;right:0;bottom:0;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:2px;padding:7px 6px calc(7px + env(safe-area-inset-bottom));background:rgba(255,255,255,.97);border-top:1px solid #dce9df;box-shadow:0 -8px 24px rgba(14,55,32,.10);backdrop-filter:blur(14px)}
  .mobile-nav-item{display:flex;min-width:0;min-height:54px;align-items:center;justify-content:center;flex-direction:column;gap:3px;padding:4px 2px;border:0;border-radius:12px;background:transparent;color:#64756b;text-decoration:none;font:700 10px system-ui;white-space:nowrap}
  .mobile-nav-item.active{background:#eaf6ef;color:#075b3d}
  .mobile-nav-icon{font-size:21px;line-height:1.1;font-weight:800}
  .mobile-menu-backdrop{position:fixed;inset:0;z-index:1001;background:rgba(9,31,20,.42)}
  .mobile-menu-backdrop[hidden],.mobile-menu-sheet[hidden]{display:none}
  .mobile-menu-sheet{position:fixed!important;z-index:1002!important;top:0!important;right:0!important;bottom:0!important;left:auto!important;width:min(88vw,390px)!important;height:100vh!important;height:100dvh!important;min-height:100vh!important;max-height:none!important;box-sizing:border-box!important;display:block!important;padding:24px 18px calc(24px + env(safe-area-inset-bottom))!important;margin:0!important;background:#fff!important;border-radius:22px 0 0 22px!important;box-shadow:-16px 0 48px rgba(0,0,0,.20)!important;overflow-x:hidden!important;overflow-y:auto!important;overscroll-behavior:contain;}
  #mobileMenuSheet:not([hidden]){background:linear-gradient(180deg,#101a20 0%,#0b1218 55%,#080e13 100%)!important;color:#eef8f3!important;border-radius:0 24px 24px 0!important;box-shadow:18px 0 55px rgba(0,0,0,.42)!important;padding:calc(16px + env(safe-area-inset-top)) 18px calc(18px + env(safe-area-inset-bottom))!important;display:flex!important;flex-direction:column!important;gap:0!important}
  .mobile-drawer-brand{display:flex;align-items:center;gap:12px;padding:8px 0 18px;border-bottom:1px solid rgba(180,220,200,.16)}
  .mobile-drawer-brand img{width:52px;height:52px;object-fit:contain;background:#fff;border-radius:14px;padding:4px}
  .mobile-drawer-brand>div{display:grid;gap:4px;min-width:0;flex:1}
  .mobile-drawer-brand strong{font-size:14px;letter-spacing:.06em;color:#fff}
  .mobile-drawer-brand span{font-size:10px;font-weight:800;letter-spacing:.16em;color:#37e0b0}
  .mobile-menu-close{width:40px!important;height:40px!important;flex:0 0 40px!important;border:1px solid rgba(180,220,200,.18)!important;border-radius:13px!important;background:rgba(255,255,255,.06)!important;color:#d8eee3!important;font-size:25px!important}
  .mobile-drawer-profile{display:flex;align-items:center;gap:12px;padding:18px 4px 17px;margin-bottom:12px;border-bottom:1px solid rgba(180,220,200,.13)}
  .mobile-drawer-avatar{width:44px;height:44px;display:grid;place-items:center;border-radius:50%;background:linear-gradient(145deg,#0d8062,#064b40);border:1px solid rgba(58,235,183,.32)}
  .mobile-drawer-avatar svg{width:24px;height:24px;fill:none;stroke:#7af2cf;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
  .mobile-drawer-profile>div:last-child{display:grid;gap:4px;min-width:0}
  .mobile-drawer-profile strong{font-size:14px;color:#f2fff8;overflow-wrap:anywhere}
  .mobile-drawer-profile span{font-size:11px;color:#9aafa6;overflow-wrap:anywhere}
  .mobile-drawer-home{display:flex;align-items:center;gap:12px;min-height:48px;padding:10px 13px;margin:0 0 15px;border:1px solid rgba(42,225,175,.18);border-radius:13px;background:linear-gradient(100deg,rgba(0,142,104,.28),rgba(0,95,80,.12));color:#eafff6;text-decoration:none;font-weight:750;font-size:13px}
  .mobile-drawer-home.active{background:linear-gradient(100deg,#087b60,#075344);border-color:#1bc99a}
  .mobile-drawer-groups{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;padding:0 2px 12px}
  .mobile-menu-group{margin:0 0 13px;padding:0 0 12px;border-bottom:1px solid rgba(177,207,190,.15)}
  .mobile-menu-group h3{display:flex;align-items:center;margin:0 0 7px;padding:0 8px;color:#93a79e;font-size:10px;font-weight:900;letter-spacing:.12em;text-transform:uppercase}
  .mobile-menu-group h3:before{content:'';width:3px;height:13px;border-radius:3px;background:#29d6a5;margin-right:8px}
  .mobile-menu-grid{display:grid;grid-template-columns:minmax(0,1fr)!important;gap:3px}
  .mobile-menu-grid a{display:flex;align-items:center;gap:12px;min-height:43px;padding:8px 10px;border:1px solid transparent;border-radius:11px;background:transparent;color:#dce9e3;text-decoration:none;font-weight:550;font-size:13px;box-shadow:none;transition:background .15s,border-color .15s}
  .mobile-menu-grid a:active{transform:scale(.99)}
  .mobile-drawer-icon{width:24px;height:24px;flex:0 0 24px;display:grid;place-items:center;color:#35e0b0}
  .mobile-drawer-icon svg{width:21px;height:21px;fill:none;stroke:currentColor;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round}
  .mobile-drawer-label{flex:1;min-width:0}
  .mobile-drawer-chevron{color:#80958c;font-size:22px;line-height:1;font-weight:400}
  .mobile-menu-grid a.active{background:linear-gradient(100deg,rgba(0,139,102,.38),rgba(0,86,76,.18));border-color:rgba(47,221,171,.22);color:#fff;box-shadow:inset 3px 0 #36e3b2}
  .mobile-menu-grid a.active .mobile-drawer-icon,.mobile-menu-grid a.active .mobile-drawer-chevron{color:#54edc1}
  .mobile-drawer-logout{flex:0 0 auto;padding-top:13px;margin-top:4px;border-top:1px solid rgba(177,207,190,.15)}
  .mobile-drawer-logout button{width:100%;display:flex;align-items:center;gap:12px;min-height:46px;padding:10px 13px;margin:0;border:1px solid rgba(255,83,108,.20);border-radius:12px;background:rgba(190,43,67,.13);color:#ff7588;font-size:13px;font-weight:800;text-align:left;box-shadow:none}
  .mobile-drawer-logout .mobile-drawer-icon{color:#ff7188}
  .mobile-drawer-logout .mobile-drawer-icon svg{stroke:currentColor}
  #mobileMenuSheet:not([hidden]){position:fixed!important;inset:0!important;z-index:1002!important;width:100vw!important;max-width:100vw!important;height:100vh!important;height:100dvh!important;min-height:100vh!important;min-height:100dvh!important;max-height:100dvh!important;box-sizing:border-box!important;margin:0!important;padding:calc(24px + env(safe-area-inset-top)) 20px calc(24px + env(safe-area-inset-bottom))!important;border-radius:0!important;display:block!important;overflow-x:hidden!important;overflow-y:auto!important;background:#fff!important;}
  #mobileMenuSheet:not([hidden]) .mobile-menu-heading{position:sticky;top:0;z-index:1;background:#fff;padding:8px 0 18px;}
@keyframes mobileDrawerIn{from{transform:translateX(28px);opacity:.7}to{transform:translateX(0);opacity:1}}
  .mobile-menu-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:4px 0 18px;margin-bottom:18px;border-bottom:1px solid #e6efe9}
  .mobile-menu-kicker{display:block;color:#087a4b;font-size:10px;font-weight:900;letter-spacing:.16em;margin-bottom:6px}
  .mobile-menu-title{font-weight:850;color:#123e2d;font-size:21px;line-height:1.2;margin:0 0 5px}
  .mobile-menu-heading small{color:#718176;font-size:12px}
  .mobile-menu-close{width:38px;height:38px;flex:0 0 38px;display:grid;place-items:center;border:1px solid #e1eee5;border-radius:12px;background:#f5faf6;color:#315b43;font-size:25px;line-height:1;padding:0}
  .mobile-menu-grid{display:grid;grid-template-columns:minmax(0,1fr)!important;gap:9px}
  .mobile-menu-grid a{display:flex;align-items:center;min-height:48px;padding:11px 13px;border:1px solid #e1eee5;border-radius:12px;background:#f8fbf8;color:#234b37;text-decoration:none;font-weight:700;font-size:13px}
  .mobile-menu-grid a.active{background:#eaf6ef;border-color:#b9d9c4;color:#075b3d}
  .account-modal{inset:0!important;padding:0!important;align-items:stretch!important;overflow:hidden!important}
  .account-modal-card{flex:1 1 100%!important;width:100%!important;max-width:none!important;max-height:100dvh!important;height:100dvh!important;border:0!important;border-radius:0!important;display:flex!important;flex-direction:column!important;overflow:hidden!important}
  .account-modal-card>#accountEditForm{display:flex;flex:1;min-height:0;flex-direction:column}
  .account-modal-body{flex:1;min-height:0;overflow-y:auto;padding:18px 16px calc(18px + env(safe-area-inset-bottom))}
  .account-modal-head{flex:0 0 auto;padding:16px}
  .account-modal-actions{flex:0 0 auto;display:grid;grid-template-columns:1fr 1fr;padding:12px 16px calc(12px + env(safe-area-inset-bottom))}
  .account-delete-form{flex:0 0 auto;padding:0 16px 12px}
  .account-delete-form button{width:100%}
}

/* STIT DARUL ILMI — PREMIUM DARK DASHBOARD THEME */
:root{--navy:#080e17!important;--teal:#0b6b54!important;--bg:#0b1119!important;--ink:#e8f1ed!important;--muted:#a0b0aa!important;--line:#293840!important;--mint:#14251f!important;--lime:#39dda9!important;color-scheme:dark}
html,body{background:#0b1119!important;color:#e8f1ed!important}
body{background:radial-gradient(ellipse at 50% -18%,#123b32 0%,#0b1119 52%)!important;color:#e8f1ed!important}
.layout,.main{background:transparent!important}
.side{background:linear-gradient(180deg,#101820 0%,#0b1119 62%,#080e15 100%)!important;color:#eaf4ef!important;border-right:1px solid #263840!important;box-shadow:12px 0 35px rgba(0,0,0,.28)!important}
.brand{color:#f4faf7!important;border-bottom:1px solid #293840!important}.brand small{color:#a8c8ba!important}.brand img{border-radius:50%!important}
.nav a{color:#dce8e2!important}.nav a.active,.nav a:hover{background:linear-gradient(100deg,#103f37,#12352f)!important;color:#fff!important;box-shadow:inset 3px 0 #39dda9!important}
.nav-group{border-top-color:#293840!important}.nav-group-title{color:#9eacbd!important}
.top{background:linear-gradient(105deg,#064d43,#075b4a 62%,#0b403a)!important;color:#f3fff9!important;border-bottom:1px solid #176b57!important;box-shadow:0 8px 28px rgba(0,0,0,.22)!important}
.top strong{color:#f3fff9!important}.top .logout{background:#12372f!important;color:#75efc8!important;border:1px solid #26765f!important}
.content{color:#e8f1ed!important}h1,h2,h3,h4,.panel h2{color:#f0faf5!important}.muted,.content .muted{color:#a0b0aa!important}
.card,.panel{background:linear-gradient(145deg,#151f28,#101820)!important;border:1px solid #2a3b43!important;color:#e8f1ed!important;box-shadow:0 10px 28px rgba(0,0,0,.18)!important}
.card strong{color:#45e0b1!important}.panel h2{color:#dff8ec!important}
table{color:#e1ebe6!important}th{background:#18372f!important;color:#8de8c7!important;border-bottom:1px solid #315448!important}td,th{border-bottom-color:#293840!important}tr:hover td{background:#17252a!important}
input,select,textarea{background:#111c24!important;color:#e8f1ed!important;border:1px solid #35464d!important}
input::placeholder,textarea::placeholder{color:#80918b!important}
input:focus,select:focus,textarea:focus{border-color:#38dba8!important;box-shadow:0 0 0 3px rgba(56,219,168,.13)!important;outline:none!important}
.alert{background:#15382f!important;color:#aaf2d6!important;border:1px solid #286b56!important}.alert.error{background:#3a2029!important;color:#ff9aa9!important;border-color:#75404b!important}
.mobile-bottom-nav{background:rgba(8,14,23,.98)!important;border-top:1px solid #293840!important;box-shadow:0 -8px 28px rgba(0,0,0,.38)!important;backdrop-filter:blur(18px)!important}
.mobile-nav-item{color:#9aaba4!important;background:transparent!important}.mobile-nav-item.active{background:#103c36!important;color:#45e2b2!important}.mobile-nav-icon{color:inherit!important}
.mobile-menu-backdrop{background:rgba(1,6,10,.72)!important;backdrop-filter:blur(3px)!important}
@media(max-width:760px){
 body{background:#0b1119!important}.top{background:linear-gradient(105deg,#064d43,#075b4a 62%,#0b403a)!important}
 .content{background:transparent!important}.cards .card,.content .panel{background:linear-gradient(145deg,#151f28,#101820)!important}
 .mobile-bottom-nav{grid-template-columns:repeat(5,minmax(0,1fr))!important}
 #mobileMenuSheet:not([hidden]){background:linear-gradient(160deg,#101820 0%,#0b1119 58%,#080e15 100%)!important;color:#eaf4ef!important;border:1px solid rgba(170,205,188,.13)!important;box-shadow:18px 0 48px rgba(0,0,0,.5)!important}
 #mobileMenuSheet:not([hidden]) .mobile-menu-group h3{color:#9eacbd!important}
 #mobileMenuSheet:not([hidden]) .mobile-menu-grid{grid-template-columns:minmax(0,1fr)!important}
 #mobileMenuSheet:not([hidden]) .mobile-menu-grid a{color:#e1e9e6!important;background:transparent!important}
 #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active{background:#103f37!important;color:#fff!important;border-color:#286c57!important}
}


/* Theme selector: green is the original campus theme; black is a full dark variant */
.theme-settings{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;max-width:900px;margin-top:22px}
.theme-option{position:relative;display:flex;align-items:center;gap:16px;min-width:0;text-align:left;padding:18px;border:2px solid var(--line);border-radius:18px;background:#fff;color:#173b2b;cursor:pointer;box-shadow:0 8px 24px rgba(3,69,47,.07);transition:.18s}
.theme-option:hover{transform:translateY(-2px);border-color:#087a4b}.theme-option[aria-pressed="true"]{border-color:#087a4b;box-shadow:0 0 0 3px rgba(8,122,75,.13)}
.theme-preview{width:112px;height:76px;flex:0 0 112px;border-radius:11px;padding:10px;display:grid;grid-template-columns:22px 1fr;grid-template-rows:10px 1fr;gap:7px;overflow:hidden}
.theme-preview i{display:block;border-radius:4px}.theme-preview i:first-child{grid-row:1/3}.theme-preview i:nth-child(2){grid-column:2}.theme-preview i:nth-child(3){grid-column:2}.theme-preview b{display:block;grid-column:2;height:14px;border-radius:4px}
.theme-preview-black{background:#080d12}.theme-preview-black i:first-child{background:#101922}.theme-preview-black i:nth-child(2),.theme-preview-black i:nth-child(3){background:#23333e}.theme-preview-black b{background:#39d6a0}
.theme-preview-green{background:#f5faf6}.theme-preview-green i:first-child{background:#03452f}.theme-preview-green i:nth-child(2),.theme-preview-green i:nth-child(3){background:#dcece1}.theme-preview-green b{background:#087a4b}
.theme-option-copy{display:grid;gap:5px;min-width:0}.theme-option-copy strong{font-size:15px}.theme-option-copy small{font-size:12px;line-height:1.45;color:#64756b}.theme-check{margin-left:auto;display:grid;place-items:center;width:26px;height:26px;flex:0 0 26px;border-radius:50%;background:#e8f5ec;color:#087a4b;opacity:0}.theme-option[aria-pressed="true"] .theme-check{opacity:1}.theme-status{grid-column:1/-1;margin:0;color:#64756b;font-size:12px}
html[data-theme="black"]{color-scheme:dark;--bg:#080d12;--ink:#edf4f7;--muted:#9aabb5;--line:rgba(178,204,218,.14);--mint:#14212b}
html[data-theme="black"] body{background:#080d12!important;color:#edf4f7!important}
html[data-theme="black"] .main,html[data-theme="black"] .content{background:#080d12!important;color:#edf4f7!important}
html[data-theme="black"] .side{background:linear-gradient(180deg,#080d12,#101922 70%,#0b171d)!important;border-right:1px solid #26343d}
html[data-theme="black"] .side .brand small,html[data-theme="black"] .nav-group-title{color:#9aabb5!important}
html[data-theme="black"] .nav a{color:#c2d0d7!important}html[data-theme="black"] .nav a.active,html[data-theme="black"] .nav a:hover{background:#142b2b!important;color:#45e2b2!important;box-shadow:inset 3px 0 #39d6a0}
html[data-theme="black"] .top{background:#101922!important;color:#edf4f7!important;border-bottom:1px solid #26343d}
html[data-theme="black"] .card,html[data-theme="black"] .panel,html[data-theme="black"] .stat,html[data-theme="black"] .item,html[data-theme="black"] .theme-option{background:#101922!important;color:#edf4f7!important;border:1px solid #293943!important;box-shadow:0 12px 32px rgba(0,0,0,.24)}
html[data-theme="black"] h1,html[data-theme="black"] h2,html[data-theme="black"] h3,html[data-theme="black"] label,html[data-theme="black"] strong{color:#edf4f7}
html[data-theme="black"] .muted,html[data-theme="black"] small,html[data-theme="black"] .theme-option-copy small,html[data-theme="black"] .theme-status{color:#9aabb5!important}
html[data-theme="black"] input,html[data-theme="black"] select,html[data-theme="black"] textarea{background:#0b141c!important;color:#edf4f7!important;border-color:#344650!important}
html[data-theme="black"] table,html[data-theme="black"] td,html[data-theme="black"] th{color:#edf4f7!important;border-color:#293943!important}
html[data-theme="black"] th{background:#172630!important}
html[data-theme="black"] .mobile-bottom-nav{background:rgba(8,14,23,.98)!important;border-color:#263840!important}
html[data-theme="black"] .mobile-nav-item{color:#9aabb5!important}html[data-theme="black"] .mobile-nav-item.active{background:#103c36!important;color:#45e2b2!important}
html[data-theme="black"] #mobileMenuSheet:not([hidden]){background:linear-gradient(160deg,#101820,#080e15)!important;color:#edf4f7!important}
html[data-theme="black"] .theme-option[aria-pressed="true"]{border-color:#39d6a0!important;box-shadow:0 0 0 3px rgba(57,214,160,.14)}
html[data-theme="black"] .theme-check{background:#12382f;color:#45e2b2}
@media(max-width:760px){.theme-settings{grid-template-columns:1fr}.theme-option{padding:14px}.theme-preview{width:94px;flex-basis:94px}}

/* Full green-and-white dashboard theme: no dark/black surfaces */
html[data-theme="green"]{color-scheme:light;--bg:#f3faf5;--ink:#174b35;--muted:#547564;--line:#cfe5d6;--mint:#e5f4e9;--lime:#b9e66b}
html[data-theme="green"] body,html[data-theme="green"] .main{background:#f3faf5!important;color:#174b35!important}
html[data-theme="green"] .side{background:linear-gradient(180deg,#075b3d 0%,#087a4b 58%,#11945c 100%)!important;color:#fff!important;border-right:1px solid #0b744a!important;box-shadow:8px 0 28px rgba(7,91,61,.12)!important}
html[data-theme="green"] .side .brand,html[data-theme="green"] .side .brand strong{color:#fff!important}
html[data-theme="green"] .side .brand small,html[data-theme="green"] .nav-group-title{color:#d5f3df!important}
html[data-theme="green"] .nav a{color:#e9f8ee!important}
html[data-theme="green"] .nav a.active,html[data-theme="green"] .nav a:hover{background:rgba(255,255,255,.19)!important;color:#fff!important;box-shadow:inset 4px 0 #c9f27b!important}
html[data-theme="green"] .top{background:#fff!important;color:#175b3d!important;border-bottom:1px solid #d5e9dc!important;box-shadow:0 4px 18px rgba(7,91,61,.05)!important}
html[data-theme="green"] .content{background:#f3faf5!important;color:#174b35!important}
html[data-theme="green"] .content h1,html[data-theme="green"] .content h2,html[data-theme="green"] .content h3,html[data-theme="green"] .content h4{color:#075b3d!important}
html[data-theme="green"] .muted,html[data-theme="green"] .content small{color:#547564!important}
html[data-theme="green"] .card,html[data-theme="green"] .panel,html[data-theme="green"] .stat,html[data-theme="green"] .item,html[data-theme="green"] .theme-option,html[data-theme="green"] .modal,html[data-theme="green"] .modal-content,html[data-theme="green"] .dialog{background:#fff!important;color:#174b35!important;border:1px solid #d5e9dc!important;box-shadow:0 10px 28px rgba(7,91,61,.07)!important}
html[data-theme="green"] .card strong,html[data-theme="green"] .panel strong,html[data-theme="green"] .stat strong{color:#087a4b!important}
html[data-theme="green"] input,html[data-theme="green"] select,html[data-theme="green"] textarea{background:#fff!important;color:#174b35!important;border:1px solid #bddbc8!important}
html[data-theme="green"] input::placeholder,html[data-theme="green"] textarea::placeholder{color:#819b8c!important}
html[data-theme="green"] input:focus,html[data-theme="green"] select:focus,html[data-theme="green"] textarea:focus{border-color:#087a4b!important;box-shadow:0 0 0 3px rgba(8,122,75,.13)!important}
html[data-theme="green"] table{background:#fff!important;color:#174b35!important}
html[data-theme="green"] th{background:#e2f3e7!important;color:#075b3d!important}
html[data-theme="green"] td,html[data-theme="green"] th{border-color:#d5e9dc!important}
html[data-theme="green"] tr:nth-child(even) td{background:#f7fcf8!important}
html[data-theme="green"] button:not(.mobile-menu-toggle):not(.mobile-menu-close){color:#fff}
html[data-theme="green"] button.primary,html[data-theme="green"] .btn-primary,html[data-theme="green"] .logout,html[data-theme="green"] .theme-option[aria-pressed="true"] .theme-check{background:linear-gradient(135deg,#0a8a53,#05613e)!important;color:#fff!important}
html[data-theme="green"] .mobile-bottom-nav{background:#fff!important;border-top:1px solid #cfe5d6!important;box-shadow:0 -8px 24px rgba(7,91,61,.08)!important}
html[data-theme="green"] .mobile-nav-item{color:#547564!important}
html[data-theme="green"] .mobile-nav-item.active{background:#e4f5e9!important;color:#087a4b!important}
html[data-theme="green"] #mobileMenuBackdrop{background:rgba(7,65,43,.35)!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]){background:#fff!important;color:#174b35!important;border:1px solid #cfe5d6!important;box-shadow:18px 0 48px rgba(7,91,61,.18)!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-brand{background:linear-gradient(135deg,#075b3d,#0b955d)!important;color:#fff!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-brand strong{color:#fff!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-brand span{color:#dff7e8!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-close{background:#e5f4e9!important;border-color:#c6e2d0!important;color:#075b3d!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-group h3{color:#087a4b!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a{background:#f4faf6!important;color:#175b3d!important;border:1px solid #d8ebdf!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active{background:#dff3e5!important;color:#05613e!important;border-color:#9bcfae!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a span{color:inherit!important}
html[data-theme="green"] .theme-option{background:#fff!important;color:#174b35!important}
html[data-theme="green"] .theme-option-copy strong{color:#075b3d!important}
html[data-theme="green"] .theme-option-copy small,html[data-theme="green"] .theme-status{color:#547564!important}
html[data-theme="green"] .theme-preview-green{background:#e7f5eb}
html[data-theme="green"] .theme-preview-green i:first-child{background:#087a4b}
html[data-theme="green"] .theme-preview-green i:nth-child(2),html[data-theme="green"] .theme-preview-green i:nth-child(3){background:#fff;border:1px solid #d1e7d8}
html[data-theme="green"] .theme-preview-green b{background:#0b955d}
html[data-theme="green"] .alert{background:#e7f6eb!important;color:#075b3d!important;border:1px solid #c5e4cf!important}
html[data-theme="green"] .alert.error{background:#fff1ee!important;color:#a23a2a!important;border-color:#f2c8bf!important}
html[data-theme="green"] .badge,html[data-theme="green"] .status{background:#e5f4e9!important;color:#087a4b!important}
html[data-theme="green"] .pagination a,html[data-theme="green"] .pagination button{background:#fff!important;color:#087a4b!important;border-color:#cfe5d6!important}
html[data-theme="green"] .pagination .active{background:#087a4b!important;color:#fff!important}

</style><link rel="stylesheet" href="../../website/assets/css/campus-theme.css?v=1"></head><body><div class="layout"><aside class="side"><div class="brand"><img src="<?=e($campusLogoUrl)?>" alt="Logo STIT Darul Ilmi"><span class="brand-copy">STIT DARUL ILMI<small>Academic Portal</small></span></div><nav class="nav" aria-label="Navigasi desktop"><a class="nav-dashboard <?=$page==='dashboard'?'active':''?>" href="?page=dashboard">Dashboard</a><?php foreach($visibleMenuGroups as $groupLabel=>$groupKeys): ?><div class="nav-group"><div class="nav-group-title"><?=e($groupLabel)?></div><?php foreach($groupKeys as $key): ?><a class="<?=$page===$key?'active':''?>" href="?page=<?=e($key)?>"><?=e($menus[$key])?></a><?php endforeach; ?></div><?php endforeach; ?></nav></aside>
<nav class="mobile-bottom-nav" aria-label="Navigasi utama Android">
<?php foreach($mobileQuick as $item): ?><a class="mobile-nav-item <?=$page===$item['page']?'active':''?>" href="?page=<?=e($item['page'])?>"><span class="mobile-nav-icon" aria-hidden="true"><?=e($item['icon'])?></span><span><?=e($item['label'])?></span></a><?php endforeach; ?>

</nav>

<?php
$mobileIconPaths = [
 'dashboard'=>'M3 10.5 12 3l9 7.5V21h-6v-6H9v6H3z',
 'programs'=>'M3 5h18v14H3z M7 9h10 M7 13h6',
 'years'=>'M4 4h16v16H4z M8 2v4 M16 2v4 M4 9h16',
 'semesters'=>'M4 4h16v16H4z M8 8h8 M8 12h8 M8 16h5',
 'students'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M20 8v6 M17 11h6',
 'lecturers'=>'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M4 21a8 8 0 0 1 16 0',
 'courses'=>'M4 4h16v16H4z M8 8h8 M8 12h8 M8 16h5',
 'classes'=>'M3 7h18v13H3z M7 7V4h10v3 M7 12h10',
 'schedules'=>'M4 4h16v16H4z M8 2v4 M16 2v4 M4 9h16 M8 13h3',
 'krs'=>'M5 3h14v18H5z M8 8h8 M8 12h8 M8 16h5',
 'grades'=>'M4 4h16v16H4z M8 15l3-3 2 2 4-5',
 'khs'=>'M4 19V5 M4 19h16 M8 15l3-4 3 2 4-7',
 'ipk'=>'M4 19V5 M4 19h16 M8 15l3-4 3 2 4-7',
 'attendance'=>'M4 4h16v16H4z M8 9h8 M8 13h3 M14 14l2 2 4-5',
 'accounts'=>'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2 M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8 M18 8v6 M15 11h6',
 'pmb'=>'M12 2 20 6v6c0 5-3.5 8-8 10-4.5-2-8-5-8-10V6z M9 12l2 2 4-4',
 'news'=>'M4 4h16v16H4z M8 8h8 M8 12h8 M8 16h5',
 'migrations'=>'M4 7h16 M7 4 4 7l3 3 M20 17H4 M17 14l3 3-3 3',
 'default'=>'M4 4h16v16H4z M8 8h8 M8 12h8'
];
$mobileIcon = static function(string $key) use ($mobileIconPaths): string {
 $path=$mobileIconPaths[$key]??$mobileIconPaths['default'];
 return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="'.$path.'"/></svg>';
};
?>

<main class="main"><header class="top"><strong><?=e($labels[$u['role']]??$u['role'])?></strong><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="logout"><button class="logout">Keluar</button></form></header><section class="content"><?php if($flash):?><div class="alert <?=$flash['type']==='error'?'error':''?>"><?=e($flash['message'])?></div><?php endif;?>
<?php if($page==='system'):?>
<h1>Pengaturan Sistem</h1><p class="muted">Atur tampilan Dashboard SIAKAD sesuai kenyamanan Anda. Pilihan tema disimpan pada browser/perangkat ini.</p>
<div class="theme-settings">
 <button type="button" class="theme-option" data-theme-choice="black" aria-pressed="false">
  <span class="theme-preview theme-preview-black"><i></i><i></i><i></i><b></b></span>
  <span class="theme-option-copy"><strong>Tema Hitam</strong><small>Mode gelap, nyaman untuk penggunaan malam hari</small></span><span class="theme-check">✓</span>
 </button>
 <button type="button" class="theme-option" data-theme-choice="green" aria-pressed="false">
  <span class="theme-preview theme-preview-green"><i></i><i></i><i></i><b></b></span>
  <span class="theme-option-copy"><strong>Tema Hijau Putih</strong><small>Dominasi hijau kampus dan putih bersih di seluruh dashboard</small></span><span class="theme-check">✓</span>
 </button>
 <p class="theme-status" id="themeStatus" aria-live="polite">Tema yang dipilih akan langsung diterapkan.</p>
</div>
<?php elseif($page==='dashboard'):?><h1>Dashboard Akademik</h1><p class="muted">Ringkasan data STIT Darul Ilmi Tasikmalaya.</p><div class="cards"><?php if($u['role']==='ketua_prodi' && $programScopeId!==null): $scopeCounts=[['Mahasiswa',"SELECT COUNT(*) FROM students WHERE study_program_id=".$programScopeId],['Mata Kuliah',"SELECT COUNT(*) FROM courses WHERE study_program_id=".$programScopeId]]; foreach($scopeCounts as [$label,$sql]):?><div class="card"><span class="muted"><?=e($label)?></span><strong><?=number_format((int)$pdo->query($sql)->fetchColumn())?></strong></div><?php endforeach; else: foreach(['students'=>'Mahasiswa','lecturers'=>'Dosen','study_programs'=>'Program Studi','courses'=>'Mata Kuliah','course_classes'=>'Kelas Kuliah','pmb_applicants'=>'Pendaftar PMB'] as $t=>$label):?><div class="card"><span class="muted"><?=e($label)?></span><strong><?=number_format(tableCount($pdo,$t))?></strong></div><?php endforeach; endif;?></div><?php if(in_array($u['role'],$academicRoles,true)): $campus=$pdo->query('SELECT * FROM campus_profile WHERE id=1')->fetch()?:[];$recentNews=$pdo->query('SELECT id,title,category,published_at,created_at FROM news_posts ORDER BY created_at DESC LIMIT 5')->fetchAll();?>
<div class="panel"><h2>Informasi Kampus</h2><form method="post" enctype="multipart/form-data" class="inline"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_campus_profile"><div style="flex-basis:100%;padding:14px;background:#f0f8f2;border:1px solid #cfe8d6;border-radius:12px"><label>Logo Resmi Kampus (JPG, PNG, WebP — maksimal 5 MB)</label><?php if(!empty($campus['logo_image_url'])):?><div style="margin:10px 0"><img src="<?=e($campus['logo_image_url'])?>" alt="Logo kampus saat ini" style="width:92px;height:92px;object-fit:contain;background:white;border-radius:12px;padding:6px"></div><?php endif;?><input type="file" name="logo_image_file" accept="image/jpeg,image/png,image/webp"><small class="muted">Unggah logo resmi untuk header dan footer website.</small><?php if(!empty($campus['logo_image_url'])):?><label style="display:block;margin-top:8px"><input type="checkbox" name="remove_logo_image" value="1"> Gunakan logo bawaan repository</label><?php endif;?></div><div style="flex-basis:100%;padding:14px;background:#f0f8f2;border:1px solid #cfe8d6;border-radius:12px"><label>Foto Gedung Kampus (JPG, PNG, WebP — maksimal 5 MB)</label><?php if(!empty($campus['hero_image_url'])):?><div style="margin:10px 0"><img src="<?=e($campus['hero_image_url'])?>" alt="Foto gedung kampus saat ini" style="width:100%;max-width:520px;max-height:220px;object-fit:cover;border-radius:10px"></div><?php else:?><p class="muted">Belum ada foto gedung. Aplikasi akan menampilkan gambar bawaan.</p><?php endif;?><input type="file" name="hero_image_file" accept="image/jpeg,image/png,image/webp"><small class="muted">Pilih foto baru untuk mengganti gambar yang tampil di banner aplikasi.</small><?php if(!empty($campus['hero_image_url'])):?><label style="display:block;margin-top:8px"><input type="checkbox" name="remove_hero_image" value="1"> Hapus foto gedung dan gunakan gambar bawaan</label><?php endif;?></div><div><label>Tagline</label><input name="tagline" maxlength="220" value="<?=e($campus['tagline']??'')?>" placeholder="Tagline resmi kampus"></div><div><label>Alamat Kampus</label><input name="address" maxlength="500" value="<?=e($campus['address']??'')?>"></div><div><label>Telepon</label><input name="phone" maxlength="60" value="<?=e($campus['phone']??'')?>"></div><div><label>Email</label><input type="email" name="email" value="<?=e($campus['email']??'')?>"></div><div><label>Website</label><input name="website" maxlength="255" value="<?=e($campus['website']??'')?>"></div><div><label>Link PMB</label><input name="pmb_url" maxlength="255" value="<?=e($campus['pmb_url']??'')?>"></div><div style="flex-basis:100%"><label>Tentang Kampus</label><textarea name="about" rows="4" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($campus['about']??'')?></textarea></div><div style="flex-basis:100%"><label>Visi</label><textarea name="vision" rows="2" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($campus['vision']??'')?></textarea></div><div style="flex-basis:100%"><label>Misi</label><textarea name="mission" rows="3" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($campus['mission']??'')?></textarea></div><button>Simpan Informasi Kampus</button></form></div>
<?php
$webDefaults=['history'=>'','pmb_title'=>'Mulai Perjalanan Akademik Anda Bersama STIT Darul Ilmi','pmb_info'=>'','whatsapp'=>'','facebook'=>'','instagram'=>'','youtube'=>'','stats_json'=>'[]','why_cards'=>'[]','gallery_json'=>'[]','testimonials_json'=>'[]','about_image_url'=>''];
$webSettings=$webDefaults;
try{$webRows=$pdo->query('SELECT content_key,content_value FROM website_content')->fetchAll();foreach($webRows as $wr){if(array_key_exists($wr['content_key'],$webSettings))$webSettings[$wr['content_key']]=$wr['content_value'];}}catch(Throwable){}
?>
<div class="panel"><h2>Konten Website Resmi</h2><p class="muted">Kelola sejarah, informasi PMB, statistik, keunggulan, galeri, testimoni, dan media sosial. Gunakan JSON yang valid untuk data berbentuk daftar. Jangan memasukkan angka statistik atau testimoni sebelum diverifikasi.</p>
<form method="post" enctype="multipart/form-data" class="inline" style="align-items:stretch">
<input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_website_content">
<div style="flex-basis:100%;padding:14px;background:#f0f8f2;border:1px solid #cfe8d6;border-radius:12px">
<label>Foto Kedua — Bagian “Mengenal Kampus” (JPG, PNG, WebP — maksimal 5 MB)</label>
<?php if(!empty($webSettings['about_image_url'])):?><div style="margin:10px 0"><img src="<?=e($webSettings['about_image_url'])?>" alt="Foto kedua saat ini" style="width:100%;max-width:520px;max-height:220px;object-fit:cover;border-radius:12px"></div><?php elseif(!empty($campus['hero_image_url'])):?><div style="margin:10px 0"><img src="<?=e($campus['hero_image_url'])?>" alt="Foto hero yang saat ini menjadi fallback foto kedua" style="width:100%;max-width:520px;max-height:220px;object-fit:cover;border-radius:12px"></div><small class="muted">Saat ini masih menggunakan foto banner sebagai gambar cadangan.</small><?php else:?><p class="muted">Belum ada foto kedua. Bagian ini akan menampilkan ikon bawaan.</p><?php endif;?>
<input type="file" name="about_image_file" accept="image/jpeg,image/png,image/webp">
<small class="muted">Unggah foto baru untuk mengganti foto besar tepat di bawah tombol “Jelajahi Kampus” pada halaman utama.</small>
<?php if(!empty($webSettings['about_image_url'])):?><label style="display:block;margin-top:8px"><input type="checkbox" name="remove_about_image" value="1"> Hapus foto kedua dan gunakan foto banner sebagai cadangan</label><?php endif;?>
</div>
<div style="flex-basis:100%"><label>Sejarah Kampus</label><textarea name="history" rows="4" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($webSettings['history'])?></textarea></div>
<div style="flex-basis:100%"><label>Judul PMB</label><input name="pmb_title" style="width:100%" value="<?=e($webSettings['pmb_title'])?>"></div>
<div style="flex-basis:100%"><label>Informasi PMB (persyaratan, jadwal, biaya, alur)</label><textarea name="pmb_info" rows="4" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($webSettings['pmb_info'])?></textarea></div>
<div><label>WhatsApp (format internasional, angka saja atau +)</label><input name="whatsapp" value="<?=e($webSettings['whatsapp'])?>"></div>
<div><label>Facebook URL</label><input name="facebook" value="<?=e($webSettings['facebook'])?>"></div>
<div><label>Instagram URL</label><input name="instagram" value="<?=e($webSettings['instagram'])?>"></div>
<div><label>YouTube URL</label><input name="youtube" value="<?=e($webSettings['youtube'])?>"></div>
<div style="flex-basis:100%"><label>Statistik Kampus (JSON array, contoh: [{"label":"Mahasiswa","value":"Isi setelah diverifikasi"}])</label><textarea name="stats_json" rows="3" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($webSettings['stats_json'])?></textarea></div>
<div style="flex-basis:100%"><label>Keunggulan Kampus (JSON array: icon, title, text)</label><textarea name="why_cards" rows="5" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($webSettings['why_cards'])?></textarea></div>
<div style="flex-basis:100%"><label>Galeri (JSON array: url, alt, caption)</label><textarea name="gallery_json" rows="4" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($webSettings['gallery_json'])?></textarea></div>
<div style="flex-basis:100%"><label>Testimoni terverifikasi (JSON array: quote, name, role)</label><textarea name="testimonials_json" rows="4" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px"><?=e($webSettings['testimonials_json'])?></textarea></div>
<button type="submit">Simpan Konten Website</button></form></div>
<div class="panel"><h2>Berita Terbaru <a class="btn" href="?page=news" style="float:right">Kelola Berita</a></h2><?php if(!$recentNews):?><p class="muted">Belum ada berita. Tambahkan berita resmi melalui menu Kelola Berita.</p><?php else:?><ul><?php foreach($recentNews as $nr):?><li style="padding:8px 0"><?=e($nr['title'])?> — <span class="tag"><?=e($nr['published_at']?'Terbit':'Draft')?></span></li><?php endforeach;?></ul><?php endif;?></div>
<div class="panel"><h2>Informasi Sistem</h2><p>Profil kampus dan berita disimpan di database bersama, lalu disediakan melalui REST API untuk homepage dan aplikasi Android.</p><p class="muted">Isi hanya informasi resmi yang telah diverifikasi oleh pihak kampus.</p></div>
<?php endif;?>
<?php elseif($page==='migrations'):
ensureMigrationRegistry($pdo);
$availableMigrations=availableMigrationFiles($backend);
// Kenali migrasi lama yang sebelumnya dijalankan manual melalui phpMyAdmin,
// atau perubahan yang sudah termasuk dalam schema.sql versi terbaru.
foreach($availableMigrations as $legacyName=>$legacyFile) {
    $known=$pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration_name=:name LIMIT 1');
    $known->execute(['name'=>$legacyName]);
    if(!$known->fetchColumn() && migrationAlreadyPresent($pdo,$legacyName)) {
        $baseline=$pdo->prepare('INSERT IGNORE INTO schema_migrations(migration_name,checksum,executed_by,executed_at) VALUES(:name,:checksum,NULL,UTC_TIMESTAMP())');
        $baseline->execute(['name'=>$legacyName,'checksum'=>$legacyFile['checksum']]);
    }
}
$appliedRows=$pdo->query('SELECT migration_name,checksum,executed_at FROM schema_migrations ORDER BY executed_at DESC')->fetchAll();
$appliedMap=[];foreach($appliedRows as $migrationRow){$appliedMap[$migrationRow['migration_name']]=$migrationRow;}
$pendingCount=0;foreach($availableMigrations as $migrationName=>$migrationFile){if(!isset($appliedMap[$migrationName]))$pendingCount++;}
?>
<h1>Migrasi Database</h1>
<p class="muted">Pilih file SQL dari <code>backend/database/migrations</code> untuk memperbarui struktur database portal.</p>
<div class="cards">
 <div class="card"><span class="muted">Total File</span><strong><?=count($availableMigrations)?></strong></div>
 <div class="card"><span class="muted">Sudah Diterapkan</span><strong><?=count($appliedMap)?></strong></div>
 <div class="card"><span class="muted">Belum Diterapkan</span><strong><?=$pendingCount?></strong></div>
</div>
<div class="panel" style="border-left:4px solid #d69e22;background:#fffdf6">
 <strong style="color:#8a5a08">Penting — Backup sebelum migrasi</strong>
 <p class="muted" style="margin:8px 0 0">Lakukan backup database melalui phpMyAdmin terlebih dahulu. Migrasi ALTER TABLE/UPDATE umumnya tidak dapat dibatalkan otomatis. Jalankan file sesuai urutan tanggal dan hanya pada database yang sesuai. Jangan jalankan ulang file yang sudah bertanda centang biru.</p>
</div>
<form method="post" class="panel">
 <input type="hidden" name="_csrf" value="<?=e(csrf())?>">
 <input type="hidden" name="action" value="run_database_migrations">
 <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
  <h2 style="margin:0">Daftar File Migration</h2>
  <span class="tag">Sumber: repository</span>
 </div>
 <?php if(!$availableMigrations):?><p class="muted">Tidak ada file SQL di folder backend/database/migrations.</p><?php else:?>
 <div style="display:grid;gap:10px">
 <?php foreach($availableMigrations as $migrationName=>$migrationFile):$isApplied=isset($appliedMap[$migrationName]);$checksumChanged=$isApplied&&!hash_equals((string)$appliedMap[$migrationName]['checksum'],(string)$migrationFile['checksum']);?>
 <label style="display:flex;align-items:flex-start;gap:12px;padding:14px;border:1px solid <?=$isApplied?'#cfe1ff':'#e1eee5'?>;border-radius:14px;background:<?=$isApplied?'#f4f8ff':'#fbfefc'?>;margin:0">
  <?php if($isApplied&&!$checksumChanged):?>
   <span aria-label="Sudah diterapkan" title="Sudah diterapkan" style="display:inline-grid;place-items:center;flex:0 0 25px;width:25px;height:25px;border-radius:50%;background:#2563eb;color:white;font-size:16px;font-weight:900">✓</span>
  <?php elseif($checksumChanged):?>
   <span title="Checksum berubah" style="display:inline-grid;place-items:center;flex:0 0 25px;width:25px;height:25px;border-radius:50%;background:#b45309;color:white;font-weight:900">!</span>
  <?php else:?>
   <input type="checkbox" name="migrations[]" value="<?=e($migrationName)?>" style="width:19px;height:19px;flex:0 0 19px;margin:3px 0 0;accent-color:#087a4b">
  <?php endif;?>
  <span style="display:block;min-width:0;flex:1">
   <strong style="display:block;overflow-wrap:anywhere;color:#173b2b"><?=e($migrationName)?></strong>
   <small class="muted"><?= $isApplied&&!$checksumChanged ? 'Sudah diterapkan pada '.e($appliedMap[$migrationName]['executed_at']) : ($checksumChanged ? 'File berubah setelah diterapkan — perlu pemeriksaan manual' : 'Belum diterapkan · dapat dipilih') ?></small>
  </span>
  <?php if($isApplied&&!$checksumChanged):?><span style="color:#2563eb;font-size:12px;font-weight:800;white-space:nowrap">MIGRATED</span><?php elseif($checksumChanged):?><span style="color:#b45309;font-size:12px;font-weight:800">CHECK</span><?php else:?><span class="tag">PENDING</span><?php endif;?>
 </label>
 <?php endforeach;?>
 </div>
 <div style="display:flex;justify-content:flex-end;margin-top:18px"><button type="submit" onclick="return confirm('Jalankan file migrasi yang dipilih? Pastikan backup database sudah tersedia.')">Jalankan Migrasi Terpilih</button></div>
 <?php endif;?>
</form>
<div class="panel"><h2>Riwayat Migrasi</h2><?php if(!$appliedRows):?><p class="muted">Belum ada migrasi yang tercatat melalui dashboard ini. Jika migrasi pernah dijalankan manual melalui phpMyAdmin, statusnya belum otomatis diketahui.</p><?php else:?><div style="overflow:auto"><table><thead><tr><th>File</th><th>Status</th><th>Waktu</th><th>Checksum</th></tr></thead><tbody><?php foreach($appliedRows as $history):$stillExists=isset($availableMigrations[$history['migration_name']]);$same=$stillExists&&hash_equals((string)$history['checksum'],(string)$availableMigrations[$history['migration_name']]['checksum']);?><tr><td><?=e($history['migration_name'])?></td><td><?=$same?'<span style="color:#2563eb;font-weight:800">✓ Applied</span>':'<span style="color:#b45309;font-weight:800">Perlu pemeriksaan</span>'?></td><td><?=e($history['executed_at'])?></td><td><code><?=e(substr((string)$history['checksum'],0,12))?>…</code></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></div>
<?php else: $ds=$datasets[$page];if($u['role']==='ketua_prodi' && $programScopeId!==null && in_array($page,['khs','ipk'],true)) {
    $scopedSql=preg_replace('/ ORDER BY /',' AND s.study_program_id=:scope_program ORDER BY ',$ds['sql'],1);
    if(!is_string($scopedSql) || $scopedSql===$ds['sql']) throw new RuntimeException('Query laporan tidak dapat dibatasi berdasarkan program studi.');
    $stmt=$pdo->prepare($scopedSql);
    $stmt->execute(['scope_program'=>$programScopeId]);
    $rows=$stmt->fetchAll();
} else {
    $rows=$pdo->query($ds['sql'])->fetchAll();
}if($page==='students'){$search=trim((string)($_GET['q']??''));if($search!==''){$q=$pdo->prepare('SELECT s.id,s.user_id,s.nim,u.full_name,u.email,p.name AS program,s.study_program_id,s.entry_year,s.phone,s.address,s.national_id,s.birth_place,s.birth_date,s.gender,s.religion,s.marital_status,s.province,s.city,s.district,s.village,s.postal_code,s.father_name,s.mother_name,s.guardian_name,s.guardian_phone,s.emergency_contact_name,s.emergency_contact_phone,s.admission_pathway,s.registration_date,s.photo_url,s.status FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN study_programs p ON p.id=s.study_program_id WHERE s.nim LIKE :nim_q OR u.full_name LIKE :name_q OR u.email LIKE :email_q ORDER BY u.full_name LIMIT 200');$needle='%'.$search.'%';$q->execute(['nim_q'=>$needle,'name_q'=>$needle,'email_q'=>$needle]);$rows=$q->fetchAll();}}?><h1><?=e($ds['title'])?></h1><p class="muted">Data dari database akademik terpadu.</p>
<?php if($page==='students'):?><div class="panel"><form method="get" class="inline"><input type="hidden" name="page" value="students"><div><label>Cari nama, NIM, atau email</label><input name="q" value="<?=e($_GET['q']??'')?>" placeholder="Masukkan kata kunci"></div><button type="submit">Cari</button><a href="?page=students">Reset</a> <a href="?page=students&export=csv">Ekspor CSV</a></form></div><?php endif;?>
<?php if($page==='news'&&in_array($u['role'],$academicRoles,true)): $editId=(int)($_GET['edit']??0);$editNews=[];if($editId){$eq=$pdo->prepare('SELECT * FROM news_posts WHERE id=:id');$eq->execute(['id'=>$editId]);$editNews=$eq->fetch()?:[];}?><div class="panel"><h2><?= $editNews?'Edit Berita':'Tambah Berita' ?></h2><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_news"><input type="hidden" name="id" value="<?=e($editNews['id']??0)?>"><div><label>Judul</label><input style="width:100%" name="title" maxlength="200" value="<?=e($editNews['title']??'')?>" required></div><div><label>Kategori</label><input name="category" maxlength="60" value="<?=e($editNews['category']??'Berita')?>"></div><div><label>Ringkasan</label><input style="width:100%" name="excerpt" maxlength="400" value="<?=e($editNews['excerpt']??'')?>"></div><div><label>URL gambar (opsional)</label><input style="width:100%" name="image_url" maxlength="500" value="<?=e($editNews['image_url']??'')?>" placeholder="https://..."></div><div><label>Isi Berita</label><textarea name="body" rows="8" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:7px" required><?=e($editNews['body']??'')?></textarea></div><button>Simpan Draft</button></form></div><?php endif;?>
<?php if($page==='news'):?><div class="panel"><h2>Daftar Berita</h2><?php if(!$rows):?><p class="muted">Belum ada berita tersimpan.</p><?php else:?><table><thead><tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Aksi</th></tr></thead><tbody><?php foreach($rows as $nr):?><tr><td><?=e($nr['title'])?></td><td><?=e($nr['category'])?></td><td><?=e($nr['published_at']?'Terbit':'Draft')?></td><td><a href="?page=news&edit=<?=$nr['id']?>">Edit</a> <form method="post" style="display:inline"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="publish_news"><input type="hidden" name="id" value="<?=$nr['id']?>"><input type="hidden" name="publish" value="<?=$nr['published_at']?0:1?>"><button><?=$nr['published_at']?'Tarik':'Terbitkan'?></button></form></td></tr><?php endforeach;?></tbody></table><?php endif;?></div><?php endif;?>
<?php if($page==='students'&&isset($_GET['edit'])):$editQ=$pdo->prepare('SELECT s.*,u.id AS account_user_id,u.full_name,u.email FROM students s JOIN users u ON u.id=s.user_id WHERE s.id=:id');$editQ->execute(['id'=>(int)$_GET['edit']]);$editStudent=$editQ->fetch();$programs=$pdo->query('SELECT id,name FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();if($editStudent):?><div class="student-detail-backdrop" id="studentEditModal" role="dialog" aria-modal="true" aria-labelledby="studentEditTitle"><div class="panel student-detail-modal"><div class="student-detail-heading"><div><span class="eyebrow">PROFIL MAHASISWA</span><h2 id="studentEditTitle">Detail & Edit Mahasiswa — <?=e($editStudent['nim'])?></h2></div><a class="student-detail-close" href="?page=students" aria-label="Tutup detail">×</a></div><form method="post" enctype="multipart/form-data" class="inline"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="update_student"><input type="hidden" name="student_id" value="<?=$editStudent['id']?>"><input type="hidden" name="user_id" value="<?=$editStudent['account_user_id']?>"><?php foreach(['full_name'=>'Nama Lengkap','email'=>'Email','nim'=>'NIM','entry_year'=>'Tahun Masuk','phone'=>'Telepon','address'=>'Alamat','national_id'=>'NIK','birth_place'=>'Tempat Lahir','birth_date'=>'Tanggal Lahir','religion'=>'Agama','marital_status'=>'Status Perkawinan','province'=>'Provinsi','city'=>'Kabupaten/Kota','district'=>'Kecamatan','village'=>'Desa/Kelurahan','postal_code'=>'Kode Pos','father_name'=>'Nama Ayah','mother_name'=>'Nama Ibu','guardian_name'=>'Nama Wali','guardian_phone'=>'Telepon Wali','emergency_contact_name'=>'Kontak Darurat','emergency_contact_phone'=>'Telepon Darurat','admission_pathway'=>'Jalur Masuk','registration_date'=>'Tanggal Registrasi','photo_url'=>'URL Foto'] as $field=>$label):?><div><label><?=e($label)?></label><input name="<?=e($field)?>" value="<?=e($editStudent[$field]??'')?>" <?=in_array($field,['birth_date','registration_date'],true)?'type="date"':($field==='email'?'type="email"':($field==='photo_url'?'type="text"':''))?>></div><?php endforeach;?><div><label>Unggah Foto (JPG/PNG/WebP, maks. 2 MB)</label><input type="file" name="photo_file" accept="image/jpeg,image/png,image/webp"></div><div><label>Program Studi</label><select name="study_program_id" required><?php foreach($programs as $pr):?><option value="<?=$pr['id']?>" <?=$editStudent['study_program_id']==$pr['id']?'selected':''?>><?=e($pr['name'])?></option><?php endforeach;?></select></div><div><label>Jenis Kelamin</label><select name="gender"><option value="">Belum diisi</option><option value="male" <?=$editStudent['gender']==='male'?'selected':''?>>Laki-laki</option><option value="female" <?=$editStudent['gender']==='female'?'selected':''?>>Perempuan</option></select></div><div class="student-detail-actions"><button type="submit">Simpan Perubahan</button><a class="student-detail-cancel" href="?page=students">Batal</a></div></form></div></div><?php endif;endif;?><?php if($page==='students'&&in_array($u['role'],$academicRoles,true)): $programs=$pdo->query('SELECT id,name FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();?>
<div class="panel student-create-panel">
  <div class="student-form-heading"><div><span class="eyebrow">DATA AKADEMIK</span><h2>Tambah Mahasiswa + Akun Login</h2><p class="muted">Isi data secara bertahap melalui tab berikut agar formulir lebih ringkas dan mudah dikelola.</p></div><span class="tag">Formulir Mahasiswa</span></div>
  <form id="studentCreateForm" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_student">
    <div class="student-tabs" role="tablist" aria-label="Bagian formulir mahasiswa">
      <button type="button" class="student-tab active" data-tab="academic" role="tab" aria-selected="true">1. Akademik</button>
      <button type="button" class="student-tab" data-tab="personal" role="tab" aria-selected="false">2. Data Pribadi</button>
      <button type="button" class="student-tab" data-tab="family" role="tab" aria-selected="false">3. Alamat & Keluarga</button>
      <button type="button" class="student-tab" data-tab="account" role="tab" aria-selected="false">4. Akun Login</button>
    </div>
    <section class="student-tab-panel active" data-tab-panel="academic" role="tabpanel">
      <h3>Informasi Akademik</h3><p class="muted">Identitas utama dan program studi mahasiswa.</p>
      <div class="student-fields">
        <div><label>Nama Lengkap</label><input name="full_name" maxlength="160" autocomplete="name" required></div>
        <div><label>NIM</label><input name="nim" maxlength="40" required></div>
        <div><label>Program Studi</label><select name="study_program_id" required><?php foreach($programs as $p):?><option value="<?=$p['id']?>"><?=e($p['name'])?></option><?php endforeach;?></select></div>
        <div><label>Tahun Masuk</label><input type="number" name="entry_year" min="2000" max="2100" value="<?=date('Y')?>" required></div>
        <div><label>Jalur Masuk</label><input name="admission_pathway" maxlength="60" placeholder="Reguler / Beasiswa / Pindahan"></div>
        <div><label>Tanggal Registrasi</label><input type="date" name="registration_date"></div>
      </div>
    </section>
    <section class="student-tab-panel" data-tab-panel="personal" role="tabpanel" hidden>
      <h3>Data Pribadi & Foto Profil</h3><p class="muted">Foto profil akan digunakan pada identitas mahasiswa di layanan akademik.</p>
      <div class="student-profile-upload">
        <div class="student-avatar-preview" id="studentPhotoPreview"><span>FOTO</span></div>
        <div class="student-photo-control"><label>Foto Profil Mahasiswa</label><input id="studentPhotoFile" type="file" name="photo_file" accept="image/jpeg,image/png,image/webp"><small class="muted">Format JPG, PNG, atau WebP. Maksimal 2 MB. Foto berbentuk persegi lebih disarankan.</small></div>
      </div>
      <div class="student-fields">
        <div><label>NIK (16 digit)</label><input name="national_id" inputmode="numeric" maxlength="16" placeholder="Nomor Induk Kependudukan"></div>
        <div><label>Tempat Lahir</label><input name="birth_place" maxlength="100"></div>
        <div><label>Tanggal Lahir</label><input type="date" name="birth_date"></div>
        <div><label>Jenis Kelamin</label><select name="gender"><option value="">Belum diisi</option><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></div>
        <div><label>Agama</label><input name="religion" maxlength="40"></div>
        <div><label>Status Perkawinan</label><input name="marital_status" maxlength="30"></div>
        <div><label>Telepon / WhatsApp</label><input name="phone" maxlength="30" autocomplete="tel"></div>
      </div>
    </section>
    <section class="student-tab-panel" data-tab-panel="family" role="tabpanel" hidden>
      <h3>Alamat & Data Keluarga</h3><p class="muted">Lengkapi alamat domisili, orang tua, dan kontak darurat (opsional).</p>
      <div class="student-fields">
        <div><label>Provinsi</label><input name="province" maxlength="100"></div><div><label>Kabupaten/Kota</label><input name="city" maxlength="100"></div>
        <div><label>Kecamatan</label><input name="district" maxlength="100"></div><div><label>Desa/Kelurahan</label><input name="village" maxlength="100"></div>
        <div><label>Kode Pos</label><input name="postal_code" maxlength="10"></div><div><label>Nama Ayah</label><input name="father_name" maxlength="160"></div>
        <div><label>Nama Ibu</label><input name="mother_name" maxlength="160"></div><div><label>Nama Wali</label><input name="guardian_name" maxlength="160"></div>
        <div><label>Telepon Wali</label><input name="guardian_phone" maxlength="30"></div><div><label>Nama Kontak Darurat</label><input name="emergency_contact_name" maxlength="160"></div>
        <div><label>Telepon Darurat</label><input name="emergency_contact_phone" maxlength="30"></div>
      </div>
    </section>
    <section class="student-tab-panel" data-tab-panel="account" role="tabpanel" hidden>
      <h3>Akun Login Mahasiswa</h3><p class="muted">Data ini digunakan mahasiswa untuk masuk ke Portal Mahasiswa.</p>
      <div class="student-fields">
        <div><label>Username</label><input name="username" maxlength="100" autocomplete="username" required></div>
        <div><label>Email</label><input type="email" name="email" autocomplete="email"></div>
        <div><label>Password Awal (minimal 10 karakter)</label><input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" required></div>
      </div>
      <div class="student-security-note"><strong>Catatan keamanan</strong><span>Berikan username dan password awal kepada mahasiswa secara pribadi. Mahasiswa sebaiknya mengganti password setelah login pertama.</span></div>
    </section>
    <div class="student-form-footer"><span class="muted">Pastikan data utama dan akun login sudah benar sebelum disimpan.</span><div><button type="button" class="student-next" id="studentPrev" hidden>Kembali</button><button type="button" class="student-next" id="studentNext">Lanjutkan <span aria-hidden="true">→</span></button><button type="submit" id="studentSubmit" hidden>Simpan Mahasiswa</button></div></div>
  </form>
</div>
<?php endif;?><?php if($page==='lecturers'&&in_array($u['role'],$academicRoles,true)): $lecturerPrograms=$pdo->query('SELECT id,name FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();?>
<div class="panel student-create-panel lecturer-create-panel">
  <div class="student-form-heading"><div><span class="eyebrow">DATA TENAGA PENDIDIK</span><h2>Tambah Dosen + Akun Login</h2><p class="muted">Lengkapi identitas, data pribadi, alamat, dan riwayat akademik dosen melalui tab berikut.</p></div><span class="tag">Formulir Dosen</span></div>
  <form id="lecturerCreateForm" method="post" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_lecturer">
    <div class="student-tabs" role="tablist" aria-label="Bagian formulir dosen">
      <button type="button" class="student-tab active" role="tab" aria-selected="true">1. Profil & Kepegawaian</button><button type="button" class="student-tab" role="tab" aria-selected="false">2. Data Pribadi</button><button type="button" class="student-tab" role="tab" aria-selected="false">3. Alamat & Kontak</button><button type="button" class="student-tab" role="tab" aria-selected="false">4. Data Akademik</button><button type="button" class="student-tab" role="tab" aria-selected="false">5. Akun Login</button>
    </div>
    <section class="student-tab-panel active" role="tabpanel">
      <h3>Identitas & Kepegawaian</h3><p class="muted">Identitas utama dan informasi status dosen.</p>
      <div class="student-profile-upload"><div class="student-avatar-preview" id="lecturerPhotoPreview"><span>FOTO</span></div><div class="student-photo-control"><label>Foto Profil Dosen</label><input id="lecturerPhotoFile" type="file" name="photo_file" accept="image/jpeg,image/png,image/webp"><small class="muted">JPG, PNG, atau WebP. Maksimal 2 MB. Foto persegi disarankan.</small></div></div>
      <div class="student-fields"><div><label>Nama Lengkap + Gelar</label><input name="full_name" maxlength="160" required></div><div><label>NIDN / Nomor Dosen</label><input name="lecturer_number" maxlength="40" placeholder="NIDN / NIDK / NUPTK"></div><div><label>Status Kepegawaian</label><select name="employment_status"><option value="">Pilih status</option><option>Dosen Tetap</option><option>Dosen Tidak Tetap</option><option>PNS</option><option>PPPK</option><option>Honorer</option></select></div><div><label>Jenis Ikatan Kerja</label><select name="employment_type"><option value="">Pilih jenis</option><option>Penuh Waktu</option><option>Paruh Waktu</option><option>Kontrak</option><option>Tetap</option></select></div><div><label>Tanggal Mulai Bekerja</label><input type="date" name="employment_start_date"></div></div>
    </section>
    <section class="student-tab-panel" role="tabpanel" hidden><h3>Data Pribadi</h3><p class="muted">Informasi identitas dan biodata dosen.</p><div class="student-fields"><div><label>NIK</label><input name="national_id" maxlength="16" inputmode="numeric" placeholder="16 digit NIK"></div><div><label>Tempat Lahir</label><input name="birth_place" maxlength="100"></div><div><label>Tanggal Lahir</label><input type="date" name="birth_date"></div><div><label>Jenis Kelamin</label><select name="gender"><option value="">Pilih jenis kelamin</option><option value="male">Laki-laki</option><option value="female">Perempuan</option></select></div><div><label>Agama</label><select name="religion"><option value="">Pilih agama</option><option>Islam</option><option>Kristen</option><option>Katolik</option><option>Hindu</option><option>Buddha</option><option>Khonghucu</option><option>Lainnya</option></select></div><div><label>Status Perkawinan</label><select name="marital_status"><option value="">Pilih status</option><option>Belum Menikah</option><option>Menikah</option><option>Cerai Hidup</option><option>Cerai Mati</option></select></div></div></section>
    <section class="student-tab-panel" role="tabpanel" hidden><h3>Alamat & Kontak</h3><p class="muted">Alamat domisili, kontak utama, dan kontak darurat.</p><div class="student-fields"><div><label>Telepon / WhatsApp</label><input name="phone" maxlength="30"></div><div><label>Email</label><input type="email" name="email" maxlength="190"></div><div><label>Provinsi</label><input name="province" maxlength="100"></div><div><label>Kabupaten / Kota</label><input name="city" maxlength="100"></div><div><label>Kecamatan</label><input name="district" maxlength="100"></div><div><label>Desa / Kelurahan</label><input name="village" maxlength="100"></div><div><label>Kode Pos</label><input name="postal_code" maxlength="10"></div><div class="full"><label>Alamat Lengkap</label><textarea name="address" rows="3" maxlength="2000"></textarea></div><div><label>Nama Kontak Darurat</label><input name="emergency_contact_name" maxlength="160"></div><div><label>Telepon Kontak Darurat</label><input name="emergency_contact_phone" maxlength="30"></div></div></section>
    <section class="student-tab-panel" role="tabpanel" hidden><h3>Data Akademik & Profesional</h3><p class="muted">Program studi, jabatan akademik, pendidikan, dan keahlian dosen.</p><div class="student-fields"><div><label>Program Studi</label><select name="study_program_id"><option value="">Pilih program studi</option><?php foreach($lecturerPrograms as $lp):?><option value="<?=$lp['id']?>"><?=e($lp['name'])?></option><?php endforeach;?></select></div><div><label>Jabatan Fungsional</label><select name="functional_position"><option value="">Pilih jabatan</option><option>Tenaga Pengajar</option><option>Asisten Ahli</option><option>Lektor</option><option>Lektor Kepala</option><option>Profesor</option></select></div><div><label>Pangkat / Golongan</label><input name="academic_rank" maxlength="100" placeholder="Contoh: III/c"></div><div><label>Pendidikan Tertinggi</label><select name="highest_education"><option value="">Pilih pendidikan</option><option>D4</option><option>S1</option><option>S2</option><option>S3</option><option>Spesialis</option></select></div><div><label>Bidang Keahlian</label><input name="expertise" maxlength="255"></div><div><label>Nomor Sertifikasi Pendidik</label><input name="certification_number" maxlength="100"></div></div></section>
    <section class="student-tab-panel" role="tabpanel" hidden><h3>Akun Login Dosen</h3><p class="muted">Akun ini digunakan dosen untuk masuk ke Portal Akademik.</p><div class="student-fields"><div><label>Username</label><input name="username" maxlength="100" autocomplete="username" required></div><div><label>Password Awal (minimal 10 karakter)</label><input type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password" required></div></div><div class="student-security-note"><strong>Keamanan akun</strong><span>Berikan kredensial kepada dosen secara pribadi. Password awal sebaiknya diganti setelah login pertama.</span></div></section>
    <div class="student-form-footer"><span class="muted">Periksa kembali identitas, data akademik, dan akun sebelum disimpan.</span><div><button type="button" class="student-next" id="lecturerPrev" hidden>Kembali</button><button type="button" class="student-next" id="lecturerNext">Lanjutkan <span aria-hidden="true">→</span></button><button type="submit" id="lecturerSubmit" hidden>Simpan Dosen</button></div></div>
  </form>
</div><?php endif;?><?php if($page==='courses'&&in_array($u['role'],$academicRoles,true)): $programs=$pdo->query('SELECT id,name FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();?><div class="panel"><h2>Tambah Mata Kuliah</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_course"><div><label>Program Studi</label><select name="study_program_id" required><?php foreach($programs as $p):?><option value="<?=$p['id']?>"><?=e($p['name'])?></option><?php endforeach;?></select></div><div><label>Kode</label><input name="code" maxlength="30" required></div><div><label>Nama Mata Kuliah</label><input name="name" maxlength="180" required></div><div><label>SKS</label><input type="number" name="credits" min="1" max="12" required></div><div><label>Semester Rekomendasi</label><select name="recommended_semester"><option value="">Tidak ditentukan</option><?php for($sem=1;$sem<=8;$sem++):?><option value="<?=$sem?>">Semester <?=$sem?></option><?php endfor;?></select></div><button>Simpan Mata Kuliah</button></form></div><?php endif;?>
<?php if($page==='classes'&&in_array($u['role'],$academicRoles,true)): $courses=$pdo->query('SELECT id,code,name FROM courses WHERE is_active=1 ORDER BY name')->fetchAll();$semesters=$pdo->query('SELECT s.id,CONCAT(y.name," - ",s.name) AS label FROM semesters s JOIN academic_years y ON y.id=s.academic_year_id ORDER BY s.start_date DESC')->fetchAll();$lecturers=$pdo->query('SELECT l.id,u.full_name FROM lecturers l JOIN users u ON u.id=l.user_id ORDER BY u.full_name')->fetchAll();?><div class="panel"><h2>Buat Kelas Kuliah</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_class"><div><label>Mata Kuliah</label><select name="course_id" required><?php foreach($courses as $c):?><option value="<?=$c['id']?>"><?=e($c['code'].' - '.$c['name'])?></option><?php endforeach;?></select></div><div><label>Semester</label><select name="semester_id" required><?php foreach($semesters as $sm):?><option value="<?=$sm['id']?>"><?=e($sm['label'])?></option><?php endforeach;?></select></div><div><label>Dosen Pengampu</label><select name="lecturer_id"><option value="">Belum ditentukan</option><?php foreach($lecturers as $l):?><option value="<?=$l['id']?>"><?=e($l['full_name'])?></option><?php endforeach;?></select></div><div><label>Nama Kelas</label><input name="class_name" maxlength="40" placeholder="A" required></div><div><label>Kapasitas</label><input type="number" name="capacity" min="1" max="500"></div><button>Buat Kelas</button></form></div><?php endif;?>
<?php if($page==='schedules'&&in_array($u['role'],$academicRoles,true)): $classes=$pdo->query('SELECT cc.id,CONCAT(c.code," - ",c.name," / ",cc.class_name) AS label FROM course_classes cc JOIN courses c ON c.id=cc.course_id ORDER BY c.name,cc.class_name')->fetchAll();?><div class="panel"><h2>Tambah Jadwal Kuliah</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_schedule"><div><label>Kelas</label><select name="course_class_id" required><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=e($cl['label'])?></option><?php endforeach;?></select></div><div><label>Hari</label><select name="day_of_week"><?php foreach(['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'] as $i=>$day):?><option value="<?=$i+1?>"><?=e($day)?></option><?php endforeach;?></select></div><div><label>Mulai</label><input type="time" name="start_time" required></div><div><label>Selesai</label><input type="time" name="end_time" required></div><div><label>Ruang</label><input name="room" maxlength="100"></div><button>Simpan Jadwal</button></form></div><?php endif;?>
<?php if($page==='krs'&&in_array($u['role'],$academicRoles,true)): $students=$pdo->query('SELECT s.id,s.nim,u.full_name FROM students s JOIN users u ON u.id=s.user_id ORDER BY u.full_name')->fetchAll();$semesters=$pdo->query('SELECT s.id,CONCAT(y.name," - ",s.name) AS label FROM semesters s JOIN academic_years y ON y.id=s.academic_year_id ORDER BY s.start_date DESC')->fetchAll();$classes=$pdo->query('SELECT cc.id,cc.semester_id,CONCAT(c.code," - ",c.name," / ",cc.class_name) AS label FROM course_classes cc JOIN courses c ON c.id=cc.course_id ORDER BY c.name')->fetchAll();?><div class="panel"><h2>Buat Pengajuan KRS</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_krs"><div><label>Mahasiswa</label><select name="student_id" required><?php foreach($students as $st):?><option value="<?=$st['id']?>"><?=e($st['nim'].' - '.$st['full_name'])?></option><?php endforeach;?></select></div><div><label>Semester</label><select name="semester_id" required><?php foreach($semesters as $sm):?><option value="<?=$sm['id']?>"><?=e($sm['label'])?></option><?php endforeach;?></select></div><div><label>Kelas (satu mata kuliah)</label><select name="course_class_id" required><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=e($cl['label'])?></option><?php endforeach;?></select></div><button>Buat KRS</button></form></div><?php endif;?>
<?php if($page==='krs'&&in_array($u['role'],$academicRoles,true)):?><div class="panel"><h2>Review Pengajuan KRS</h2><p class="muted">Setujui atau tolak pengajuan yang masih berstatus submitted.</p><?php foreach($rows as $r):if($r['status']==='submitted'):?><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="review_krs"><input type="hidden" name="plan_id" value="<?=$r['id']?>"><strong><?=e($r['nim'].' - '.$r['mahasiswa'].' / '.$r['tahun_akademik'].' '.$r['semester'])?></strong><button name="decision" value="approved">Setujui</button><button name="decision" value="rejected" style="background:#9b3030">Tolak</button></form><?php endif;endforeach;?></div><?php endif;?>
<?php if($page==='grades'&&in_array($u['role'],['super_admin','admin_akademik','dosen'],true)): $classSql='SELECT cc.id,CONCAT(c.code," - ",c.name," / ",cc.class_name) AS label FROM course_classes cc JOIN courses c ON c.id=cc.course_id';if($u['role']==='dosen'){$q=$pdo->prepare($classSql.' JOIN lecturers l ON l.id=cc.lecturer_id WHERE l.user_id=:uid ORDER BY c.name');$q->execute(['uid'=>$u['id']]);$classes=$q->fetchAll();}else{$classes=$pdo->query($classSql.' ORDER BY c.name')->fetchAll();}$students=$pdo->query("SELECT s.id,s.nim,u.full_name FROM students s JOIN users u ON u.id=s.user_id ORDER BY u.full_name")->fetchAll();?><div class="panel"><h2>Input Nilai</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_grade"><div><label>Kelas</label><select name="course_class_id" required><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=e($cl['label'])?></option><?php endforeach;?></select></div><div><label>Mahasiswa (harus KRS disetujui)</label><select name="student_id" required><?php foreach($students as $st):?><option value="<?=$st['id']?>"><?=e($st['nim'].' - '.$st['full_name'])?></option><?php endforeach;?></select></div><div><label>Nilai angka (0-100)</label><input type="number" name="numeric_score" min="0" max="100" step="0.01" required></div><button>Simpan Draft Nilai</button></form></div><?php endif;?>
<?php if($page==='grades'&&in_array($u['role'],$academicRoles,true)):?><div class="panel"><h2>Publikasi Nilai</h2><?php foreach($rows as $r):if($r['published_at']===null):?><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="publish_grade"><input type="hidden" name="grade_id" value="<?=$r['id']?>"><span><?=e($r['nim'].' - '.$r['mahasiswa'].' / '.$r['mata_kuliah'].' / '.$r['numeric_score'].' / '.$r['letter_grade'])?></span><button>Publikasikan</button></form><?php endif;endforeach;?></div><?php endif;?>
<?php if($page==='attendance'&&in_array($u['role'],['super_admin','admin_akademik','dosen'],true)): $classSql='SELECT cc.id,CONCAT(c.code," - ",c.name," / ",cc.class_name) AS label FROM course_classes cc JOIN courses c ON c.id=cc.course_id';if($u['role']==='dosen'){$q=$pdo->prepare($classSql.' JOIN lecturers l ON l.id=cc.lecturer_id WHERE l.user_id=:uid ORDER BY c.name');$q->execute(['uid'=>$u['id']]);$classes=$q->fetchAll();}else{$classes=$pdo->query($classSql.' ORDER BY c.name')->fetchAll();}$students=$pdo->query("SELECT s.id,s.nim,u.full_name FROM students s JOIN users u ON u.id=s.user_id ORDER BY u.full_name")->fetchAll();?><div class="panel"><h2>Catat Presensi Mahasiswa</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_attendance"><div><label>Kelas</label><select name="course_class_id" required><?php foreach($classes as $cl):?><option value="<?=$cl['id']?>"><?=e($cl['label'])?></option><?php endforeach;?></select></div><div><label>Mahasiswa (KRS disetujui)</label><select name="student_id" required><?php foreach($students as $st):?><option value="<?=$st['id']?>"><?=e($st['nim'].' - '.$st['full_name'])?></option><?php endforeach;?></select></div><div><label>Pertemuan ke</label><input type="number" name="meeting_number" min="1" max="32" required></div><div><label>Status</label><select name="attendance_status"><option value="present">Hadir</option><option value="sick">Sakit</option><option value="permission">Izin</option><option value="absent">Alpa</option></select></div><button>Simpan Presensi</button></form></div><?php endif;?>
<?php if($page==='accounts'&&in_array($u['role'],$academicRoles,true)): $prodiUsers=$pdo->query("SELECT u.id,u.full_name,u.username,s.study_program_id FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN user_program_scopes s ON s.user_id=u.id WHERE r.name='ketua_prodi' AND u.status='active' ORDER BY u.full_name")->fetchAll();$activePrograms=$pdo->query('SELECT id,name FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();?><div class="panel"><h2>Buat Akun Pengelola</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_account"><div><label>Nama Lengkap</label><input name="full_name" maxlength="160" required></div><div><label>Username</label><input name="username" maxlength="100" required></div><div><label>Email</label><input type="email" name="email"></div><div><label>Role</label><select name="role"><option value="admin_akademik">Admin Akademik</option><option value="admin_keuangan">Admin Keuangan</option><option value="ketua_prodi">Ketua Program Studi</option><option value="ketua_stit">Ketua STIT</option></select></div><div><label>Password awal (min. 10 karakter)</label><input type="password" name="password" minlength="10" required></div><button>Buat Akun</button></form></div><div class="panel"><h2>Cakupan Akses Ketua Program Studi</h2><p class="muted">Setiap akun Ketua Prodi wajib ditautkan ke satu program studi. Tanpa penetapan ini, halaman laporan tidak dapat dibuka.</p><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="assign_prodi_scope"><div><label>Akun Ketua Prodi</label><select name="user_id" required><?php foreach($prodiUsers as $pu):?><option value="<?=$pu['id']?>"><?=e($pu['full_name'].' ('.$pu['username'].')')?></option><?php endforeach;?></select></div><div><label>Program Studi</label><select name="study_program_id" required><?php foreach($activePrograms as $ap):?><option value="<?=$ap['id']?>"><?=e($ap['name'])?></option><?php endforeach;?></select></div><button>Simpan Cakupan</button></form><table><thead><tr><th>Ketua Prodi</th><th>Program Studi</th><th>Status Cakupan</th></tr></thead><tbody><?php foreach($prodiUsers as $pu):?><tr><td><?=e($pu['full_name'])?></td><td><?php $scopeName='Belum ditetapkan';foreach($activePrograms as $ap){if((int)$ap['id']===(int)$pu['study_program_id']){$scopeName=$ap['name'];break;}} echo e($scopeName);?></td><td><?= $pu['study_program_id']?'Aktif':'Perlu ditetapkan' ?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
<?php if($page==='programs'&&in_array($u['role'],$academicRoles,true)):?><div class="panel"><h2>Tambah Program Studi</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_program"><div><label>Kode</label><input name="code" maxlength="30" required placeholder="MPI"></div><div><label>Nama Program Studi</label><input name="name" maxlength="160" required placeholder="Manajemen Pendidikan Islam"></div><div><label>Jenjang</label><select name="degree_level"><option>S1</option><option>S2</option><option>S3</option></select></div><button>Simpan</button></form></div><?php endif;?>
<?php if($page==='years'&&in_array($u['role'],$academicRoles,true)):?><div class="panel"><h2>Tambah Tahun Akademik</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_year"><div><label>Nama (contoh 2026/2027)</label><input name="name" pattern="\d{4}/\d{4}" required placeholder="2026/2027"></div><div><label>Tanggal Mulai</label><input type="date" name="start_date" required></div><div><label>Tanggal Selesai</label><input type="date" name="end_date" required></div><label><input type="checkbox" name="is_active" value="1"> Aktif</label><button>Simpan</button></form></div><?php endif;?>
<?php if($page==='semesters'&&in_array($u['role'],$academicRoles,true)): $years=$pdo->query('SELECT id,name FROM academic_years ORDER BY start_date DESC')->fetchAll();?><div class="panel"><h2>Tambah Semester</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_semester"><div><label>Tahun Akademik</label><select name="academic_year_id" required><?php foreach($years as $yr):?><option value="<?=$yr['id']?>"><?=e($yr['name'])?></option><?php endforeach;?></select></div><div><label>Semester</label><select name="name" required><?php for($sem=1;$sem<=8;$sem++):?><option value="Semester <?=$sem?>">Semester <?=$sem?></option><?php endfor;?></select></div><div><label>Mulai</label><input type="date" name="start_date" required></div><div><label>Selesai</label><input type="date" name="end_date" required></div><button>Simpan</button></form></div><?php endif;?>
<?php if($page==='accounts'&&in_array($u['role'],$academicRoles,true)): ?>
<div id="accountDetailModal" class="account-modal" hidden aria-hidden="true">
 <div class="account-modal-backdrop" data-account-close></div>
 <section class="account-modal-card" role="dialog" aria-modal="true" aria-labelledby="accountModalTitle">
  <header class="account-modal-head"><h2 id="accountModalTitle">Detail Akun Pengguna</h2><button type="button" class="account-modal-x" data-account-close aria-label="Tutup">×</button></header>
  <form id="accountEditForm" method="post">
   <input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="update_account"><input type="hidden" name="user_id" id="accountUserId">
   <div class="account-modal-body">
    <div class="account-profile"><div class="account-avatar">👤</div><div><strong id="accountDisplayName">—</strong><span id="accountDisplayRole" class="tag">—</span><small id="accountDisplayUsername" class="muted"></small></div></div>
    <div class="account-fields">
     <div><label>Username</label><input id="accountUsername" readonly></div><div><label>Nama Lengkap</label><input name="full_name" id="accountFullName" maxlength="160" required></div>
     <div><label>Email</label><input type="email" name="email" id="accountEmail" maxlength="190"></div>
     <div><label>Role</label><select name="role" id="accountRole"><option value="admin_akademik">Admin Akademik</option><option value="admin_keuangan">Admin Keuangan</option><option value="ketua_prodi">Ketua Program Studi</option><option value="ketua_stit">Ketua STIT</option><option value="dosen">Dosen</option><option value="mahasiswa">Mahasiswa</option></select></div>
     <div><label>Status</label><select name="status" id="accountStatus"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select></div>
     <div><label>Password Baru (opsional)</label><input type="password" name="password" id="accountPassword" minlength="10" maxlength="200" autocomplete="new-password" placeholder="Kosongkan jika tidak diubah"></div>
    </div>
    <div class="account-scope" id="accountScope"><strong>Cakupan Akses</strong><span id="accountProgram">—</span></div>
    <div class="account-meta"><div><span>Dibuat pada</span><strong id="accountCreated">—</strong></div><div><span>Login terakhir</span><strong id="accountLastLogin">—</strong></div></div>
   </div>
   <footer class="account-modal-actions"><button type="button" class="account-btn account-btn-light" data-account-close>Tutup</button><button type="submit" class="account-btn account-btn-primary">Simpan Edit</button></footer>
  </form>
  <form id="accountDeleteForm" method="post" class="account-delete-form" onsubmit="return confirm('Yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan.')">
   <input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="delete_account"><input type="hidden" name="user_id" id="accountDeleteUserId"><button type="submit" class="account-btn account-btn-danger">Hapus Akun</button>
  </form>
 </section>
</div>
<style>
.account-link{color:#075db5;font-weight:750;text-decoration:underline;text-underline-offset:3px;cursor:pointer;background:none;border:0;padding:4px 0;font:inherit}
.account-modal[hidden]{display:none!important}.account-modal{position:fixed!important;inset:0!important;z-index:2147483000!important;display:flex!important;align-items:center!important;justify-content:center!important;padding:12px!important;box-sizing:border-box!important;isolation:isolate!important;overflow-y:auto!important;background:rgba(10,28,20,.72)!important}.account-modal-backdrop{position:fixed!important;inset:0!important;z-index:0!important;background:rgba(10,28,20,.45)!important;backdrop-filter:none!important;-webkit-backdrop-filter:none!important}.account-modal-card{position:relative!important;z-index:2!important;display:block!important;flex:0 1 720px!important;width:100%!important;max-width:720px!important;max-height:calc(100vh - 24px)!important;overflow-y:auto!important;visibility:visible!important;opacity:1!important;background-color:#fff!important;color:#173b2b!important;border:1px solid #dcebe1!important;border-radius:18px!important;box-shadow:0 18px 55px rgba(0,0,0,.35)!important}.account-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:17px 22px;background:linear-gradient(100deg,#03452f,#087a4b);color:#fff}.account-modal-head h2{margin:0;font-size:19px}.account-modal-x{border:0;background:transparent;color:#fff;font-size:30px;line-height:1;cursor:pointer}.account-modal-body{padding:20px 22px}.account-profile{display:flex;align-items:center;gap:16px;margin-bottom:20px}.account-avatar{width:76px;height:76px;display:grid;place-items:center;border-radius:50%;background:#dff2e7;font-size:34px}.account-profile strong,.account-profile small{display:block}.account-profile strong{font-size:21px;color:#123e2d;margin-bottom:6px}.account-profile small{margin-top:6px}.account-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px}.account-fields label{display:block;font-size:12px;font-weight:700;color:#53685c;margin-bottom:6px}.account-fields input,.account-fields select{width:100%;min-width:0;padding:11px 12px;border:1px solid #d5e4da;border-radius:11px;background:#fbfefc;font:inherit}.account-fields input[readonly]{background:#f0f4f1;color:#64756b}.account-scope{margin-top:16px;padding:13px 15px;background:#edf7f1;border:1px solid #cce5d5;border-radius:12px;display:grid;gap:5px}.account-scope strong{color:#315b43;font-size:12px}.account-meta{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:16px;padding-top:14px;border-top:1px solid #edf1ed}.account-meta div{display:grid;gap:4px}.account-meta span{font-size:12px;color:#64756b}.account-meta strong{font-size:13px;font-weight:600;overflow-wrap:anywhere}.account-modal-actions{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;padding:14px 22px;border-top:1px solid #edf1ed}.account-btn{border:0;border-radius:11px;padding:11px 17px;font-weight:750;cursor:pointer}.account-btn-light{background:#edf1ee;color:#263b30}.account-btn-primary{background:#087a4b;color:white}.account-btn-danger{background:#c92e28;color:white}.account-delete-form{padding:0 22px 18px;text-align:right}.account-delete-form button{min-width:140px}
@media(max-width:600px){.account-modal{padding:10px;align-items:center}.account-modal-card{max-height:94dvh;border-radius:18px}.account-modal-head{padding:14px 16px}.account-modal-body{padding:16px}.account-fields{grid-template-columns:1fr;gap:10px}.account-profile{margin-bottom:14px}.account-avatar{width:62px;height:62px}.account-modal-actions{padding:12px 16px;display:grid;grid-template-columns:1fr 1fr}.account-modal-actions button{width:100%}.account-delete-form{padding:0 16px 16px}.account-delete-form button{width:100%}.account-meta{grid-template-columns:1fr}}

/* FINAL ANDROID DRAWER THEME — STIT DARUL ILMI */
@media (max-width:760px) {
  body { background:#080e17!important; }
  .mobile-bottom-nav {
    position:fixed!important; z-index:1000!important; left:0!important; right:0!important; bottom:0!important;
    display:grid!important; grid-template-columns:repeat(5,minmax(0,1fr))!important;
    padding:7px 8px calc(7px + env(safe-area-inset-bottom))!important;
    background:rgba(8,14,23,.98)!important; border-top:1px solid #263840!important;
    box-shadow:0 -8px 28px rgba(0,0,0,.35)!important; backdrop-filter:blur(16px)!important;
  }
  .mobile-nav-item {
    min-width:0!important; min-height:55px!important; border:0!important; border-radius:12px!important;
    background:transparent!important; color:#91a39d!important; text-decoration:none!important;
    font:600 10px system-ui,sans-serif!important;
  }
  .mobile-nav-icon { color:inherit!important; font-size:21px!important; }
  .mobile-nav-item.active { background:#103c36!important; color:#45e2b2!important; }
  .mobile-menu-backdrop {
    position:fixed!important; inset:0!important; z-index:1001!important;
    background:rgba(1,7,12,.68)!important; backdrop-filter:blur(2px)!important;
  }
  .mobile-menu-sheet,
  #mobileMenuSheet:not([hidden]) {
    position:fixed!important; z-index:1002!important;
    top:0!important; bottom:0!important; left:0!important; right:auto!important;
    width:clamp(300px,59vw,410px)!important; max-width:88vw!important;
    height:100vh!important; height:100dvh!important; min-height:100dvh!important; max-height:100dvh!important;
    box-sizing:border-box!important; margin:0!important;
    padding:calc(14px + env(safe-area-inset-top)) 22px calc(12px + env(safe-area-inset-bottom))!important;
    display:flex!important; flex-direction:column!important; align-items:stretch!important; gap:0!important;
    overflow:hidden!important; overscroll-behavior:contain!important;
    color:#eaf4ef!important;
    background:linear-gradient(160deg,#101820 0%,#0b1119 58%,#080e15 100%)!important;
    border:1px solid rgba(170,205,188,.13)!important; border-left:0!important;
    border-radius:0 22px 22px 0!important;
    box-shadow:18px 0 48px rgba(0,0,0,.48)!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand {
    flex:0 0 auto!important; display:flex!important; min-height:68px!important;
    align-items:center!important; gap:12px!important; padding:3px 0 14px!important;
    border-bottom:1px solid #293740!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand img {
    width:48px!important; height:48px!important; flex:0 0 48px!important;
    object-fit:contain!important; border-radius:12px!important; background:#fff!important; padding:2px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand strong {
    display:block!important; color:#f4faf7!important; font-size:13px!important; font-weight:850!important; letter-spacing:.04em!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand span {
    display:block!important; margin-top:4px!important; color:#b6c8c0!important; font-size:10px!important; letter-spacing:.12em!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-close {
    flex:0 0 36px!important; width:36px!important; height:36px!important; margin-left:auto!important;
    border:1px solid #35434a!important; border-radius:10px!important; background:#1b2930!important; color:#eaf4ef!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile {
    flex:0 0 auto!important; display:flex!important; align-items:center!important; gap:12px!important;
    margin:0 0 13px!important; padding:15px 2px!important; border-bottom:1px solid #293740!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-avatar {
    width:46px!important; height:46px!important; flex:0 0 46px!important;
    display:grid!important; place-items:center!important; border-radius:50%!important;
    background:linear-gradient(145deg,#087a63,#064d43)!important; color:#65edc7!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile strong {
    display:block!important; color:#f2f8f5!important; font-size:14px!important; font-weight:800!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile span {
    display:block!important; margin-top:4px!important; color:#a8bbb4!important; font-size:11px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home {
    flex:0 0 auto!important; display:flex!important; align-items:center!important; gap:12px!important;
    min-height:48px!important; margin:0 0 12px!important; padding:9px 12px!important;
    border:1px solid #155448!important; border-radius:11px!important;
    background:linear-gradient(100deg,#075b4a,#103c3a)!important; color:#f0fff9!important;
    font-size:13px!important; font-weight:750!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    display:block!important; flex:1 1 auto!important; min-height:0!important;
    overflow-x:hidden!important; overflow-y:auto!important; overscroll-behavior:contain!important;
    padding:0 2px 12px!important; scrollbar-width:thin!important; scrollbar-color:#3a6659 transparent!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group {
    display:block!important; margin:0 0 12px!important; padding:0 0 10px!important;
    border-bottom:1px solid #293740!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group h3 {
    display:flex!important; align-items:center!important; gap:9px!important;
    margin:0 0 7px!important; padding:0 2px!important; color:#9eacbd!important;
    font-size:10px!important; font-weight:850!important; letter-spacing:.09em!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group h3:before {
    content:""!important; width:4px!important; height:15px!important; border-radius:4px!important; background:#31dba7!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid {
    display:grid!important; grid-template-columns:minmax(0,1fr)!important; gap:2px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {
    display:flex!important; align-items:center!important; justify-content:flex-start!important; gap:12px!important;
    min-width:0!important; min-height:40px!important; margin:0!important; padding:7px 10px!important;
    border:1px solid transparent!important; border-radius:10px!important;
    background:transparent!important; color:#e1e9e6!important;
    font-size:13px!important; font-weight:550!important; box-shadow:none!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active {
    background:rgba(8,132,102,.25)!important; border-color:rgba(46,211,164,.2)!important;
    color:#f2fff9!important; box-shadow:inset 3px 0 #36dda9!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-icon {
    display:inline-flex!important; align-items:center!important; justify-content:center!important;
    width:23px!important; height:23px!important; flex:0 0 23px!important; color:#39dda9!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-icon svg {
    display:block!important; width:21px!important; height:21px!important;
    fill:none!important; stroke:currentColor!important; stroke-width:1.8!important;
    stroke-linecap:round!important; stroke-linejoin:round!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-label { flex:1 1 auto!important; min-width:0!important; }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-chevron {
    margin-left:auto!important; color:#91a39d!important; font-size:21px!important; line-height:1!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout {
    flex:0 0 auto!important; display:block!important; margin:8px 0 0!important;
    padding:11px 0 calc(2px + env(safe-area-inset-bottom))!important; border-top:1px solid #293740!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button {
    display:flex!important; align-items:center!important; justify-content:flex-start!important; gap:10px!important;
    width:100%!important; min-height:44px!important; margin:0!important; padding:9px 12px!important;
    border:1px solid #5c2a3a!important; border-radius:10px!important;
    background:#351d27!important; color:#ff8798!important; font-size:13px!important; font-weight:750!important;
  }
}

</style>
<script>
(function(){
 const modal=document.getElementById('accountDetailModal');if(!modal)return;
 const byId=id=>document.getElementById(id);
 const close=()=>{modal.hidden=true;modal.setAttribute('aria-hidden','true');document.body.style.overflow='';};
 modal.querySelectorAll('[data-account-close]').forEach(el=>el.addEventListener('click',close));
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.hidden)close();});
 window.openAccountDetail=function(button){
  let d;
  try{
   const raw=button.getAttribute('data-account-json')||'';
   d=JSON.parse(raw);
   if(!d||!d.id)throw new Error('Data akun tidak lengkap (ID akun tidak ditemukan).');
  }catch(error){
   alert('Data akun gagal dibaca. Muat ulang halaman Akun Pengguna. Detail: '+error.message);
   return false;
  }
  byId('accountUserId').value=d.id;byId('accountDeleteUserId').value=d.id;
  byId('accountUsername').value=d.username||'';byId('accountFullName').value=d.full_name||'';
  byId('accountEmail').value=d.email||'';byId('accountRole').value=d.role||'admin_akademik';
  byId('accountStatus').value=d.status||'active';byId('accountPassword').value='';
  byId('accountDisplayName').textContent=d.full_name||d.username||'Akun';
  byId('accountDisplayRole').textContent=d.role_label||d.role||'—';
  byId('accountDisplayUsername').textContent='@'+(d.username||'');
  byId('accountProgram').textContent=d.program_studi||'Tidak ada cakupan program studi';
  byId('accountCreated').textContent=d.created_at||'—';byId('accountLastLogin').textContent=d.last_login_at||'Belum pernah login';
  byId('accountScope').hidden=!d.program_studi;
  const protectedAccount=d.role==='super_admin';
  byId('accountRole').disabled=protectedAccount;byId('accountStatus').disabled=protectedAccount;
  byId('accountEditForm').querySelector('button[type="submit"]').disabled=protectedAccount;
  byId('accountDeleteForm').hidden=protectedAccount;
  modal.hidden=false;modal.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';
  return false;
 };

})();
</script>
<?php endif;?>
<div class="panel"><h2>Daftar <?=e($ds['title'])?> <span class="tag"><?=count($rows)?> data</span></h2><?php if(!$rows):?><p class="muted">Belum ada data untuk ditampilkan.</p><?php else:?><table><thead><tr><?php foreach(array_keys($rows[0]) as $col):if($page==='accounts'&&$col==='id')continue;?><th><?=e(str_replace('_',' ',$col))?></th><?php endforeach;?></tr></thead><tbody><?php foreach($rows as $row):?><tr><?php foreach($row as $column=>$value):if($page==='accounts'&&$column==='id')continue;?><td><?php if($page==='students'&&$column==='full_name'):?><a class="student-name-link" href="?page=students&amp;edit=<?=(int)$row['id']?>#studentEditModal" aria-label="Lihat dan edit data <?=e($value)?>"><?=e($value)?></a><?php elseif($page==='accounts'&&$column==='username'):$accountJson=['id'=>(int)$row['id'],'username'=>$row['username'],'full_name'=>$row['full_name'],'email'=>$row['email'],'role'=>$row['role'],'role_label'=>['super_admin'=>'Super Admin','admin_akademik'=>'Admin Akademik','admin_keuangan'=>'Admin Keuangan','ketua_prodi'=>'Ketua Program Studi','ketua_stit'=>'Ketua STIT','dosen'=>'Dosen','mahasiswa'=>'Mahasiswa'][$row['role']]??$row['role'],'status'=>$row['status'],'program_studi'=>$row['program_studi'],'created_at'=>$row['created_at']??null,'last_login_at'=>$row['last_login_at']];?><button type="button" class="account-link" onclick="return window.openAccountDetail(this)" data-account-json="<?=e(json_encode($accountJson,JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP))?>"><?=e($value)?></button><?php else:?><?=e($value===null?'—':$value)?><?php endif;?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table><?php endif;?></div><?php endif;?></section></main></div><script>
;
</script><script>
(function(){
 const form=document.getElementById('studentCreateForm');if(!form)return;
 const tabs=[...form.querySelectorAll('.student-tab')],panels=[...form.querySelectorAll('.student-tab-panel')];
 const prev=document.getElementById('studentPrev'),next=document.getElementById('studentNext'),submit=document.getElementById('studentSubmit');
 let active=0;
 function show(index){active=Math.max(0,Math.min(index,tabs.length-1));tabs.forEach((tab,i)=>{const selected=i===active;tab.classList.toggle('active',selected);tab.setAttribute('aria-selected',selected?'true':'false');});panels.forEach((panel,i)=>{panel.hidden=i!==active;panel.classList.toggle('active',i===active);});prev.hidden=active===0;next.hidden=active===tabs.length-1;submit.hidden=active!==tabs.length-1;}
 tabs.forEach((tab,i)=>tab.addEventListener('click',()=>show(i)));
 prev.addEventListener('click',()=>show(active-1));
 next.addEventListener('click',()=>show(active+1));
 form.addEventListener('submit',function(event){if(!form.checkValidity()){event.preventDefault();const invalid=[...form.elements].find(el=>el.willValidate&&!el.checkValidity());if(invalid){const panel=invalid.closest('.student-tab-panel');if(panel){show(panels.indexOf(panel));}invalid.focus();invalid.reportValidity();}}});
 const photo=document.getElementById('studentPhotoFile'),preview=document.getElementById('studentPhotoPreview');
 photo.addEventListener('change',function(){const file=this.files&&this.files[0];if(!file){preview.innerHTML='<span>FOTO</span>';return;}if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>2*1024*1024){this.value='';preview.innerHTML='<span>FOTO</span>';alert('Pilih foto JPG, PNG, atau WebP dengan ukuran maksimal 2 MB.');return;}const reader=new FileReader();reader.onload=()=>{preview.innerHTML='';const img=document.createElement('img');img.src=reader.result;img.alt='Pratinjau foto profil';preview.appendChild(img);};reader.readAsDataURL(file);});
 show(0);
 const lecturerForm=document.getElementById('lecturerCreateForm');
 if(lecturerForm){
  const lecturerTabs=[...lecturerForm.querySelectorAll('.student-tab')],lecturerPanels=[...lecturerForm.querySelectorAll('.student-tab-panel')];
  const lecturerPrev=document.getElementById('lecturerPrev'),lecturerNext=document.getElementById('lecturerNext'),lecturerSubmit=document.getElementById('lecturerSubmit');let lecturerActive=0;
  function showLecturer(i){lecturerActive=Math.max(0,Math.min(i,lecturerTabs.length-1));lecturerTabs.forEach((t,n)=>{const yes=n===lecturerActive;t.classList.toggle('active',yes);t.setAttribute('aria-selected',yes?'true':'false');});lecturerPanels.forEach((p,n)=>{p.hidden=n!==lecturerActive;p.classList.toggle('active',n===lecturerActive);});lecturerPrev.hidden=lecturerActive===0;lecturerNext.hidden=lecturerActive===lecturerTabs.length-1;lecturerSubmit.hidden=lecturerActive!==lecturerTabs.length-1;}
  lecturerTabs.forEach((t,i)=>t.addEventListener('click',()=>showLecturer(i)));lecturerPrev.addEventListener('click',()=>showLecturer(lecturerActive-1));lecturerNext.addEventListener('click',()=>showLecturer(lecturerActive+1));
  lecturerForm.addEventListener('submit',function(ev){if(!lecturerForm.checkValidity()){ev.preventDefault();const bad=[...lecturerForm.elements].find(el=>el.willValidate&&!el.checkValidity());if(bad){const p=bad.closest('.student-tab-panel');if(p)showLecturer(lecturerPanels.indexOf(p));bad.focus();bad.reportValidity();}}});
  const file=document.getElementById('lecturerPhotoFile'),preview=document.getElementById('lecturerPhotoPreview');file.addEventListener('change',function(){const f=this.files&&this.files[0];if(!f){preview.innerHTML='<span>FOTO</span>';return;}if(!['image/jpeg','image/png','image/webp'].includes(f.type)||f.size>2*1024*1024){this.value='';preview.innerHTML='<span>FOTO</span>';alert('Pilih foto JPG, PNG, atau WebP dengan ukuran maksimal 2 MB.');return;}const reader=new FileReader();reader.onload=()=>{preview.innerHTML='';const img=document.createElement('img');img.src=reader.result;img.alt='Pratinjau foto profil dosen';preview.appendChild(img);};reader.readAsDataURL(f);});showLecturer(0);
 }
})();
</script><script>
(function(){
 const key='stit-admin-theme',root=document.documentElement;
 let current='green';try{current=localStorage.getItem(key)||'green'}catch(e){}
 function apply(theme){
  current=theme==='black'?'black':'green';root.dataset.theme=current;
  try{localStorage.setItem(key,current)}catch(e){}
  document.querySelectorAll('[data-theme-choice]').forEach(btn=>btn.setAttribute('aria-pressed',String(btn.dataset.themeChoice===current)));
  const status=document.getElementById('themeStatus');
  if(status)status.textContent='Tema aktif: '+(current==='black'?'Hitam':'Hijau')+'. Pengaturan tersimpan di browser ini.';
 }
 apply(current);
 document.querySelectorAll('[data-theme-choice]').forEach(btn=>btn.addEventListener('click',()=>apply(btn.dataset.themeChoice)));
})();
</script>
<style id="android-green-white-final">
html[data-theme="green"] body{background:#f3faf5!important;color:#174b35!important}
@media(max-width:760px){
 html[data-theme="green"] body{background:#f3faf5!important}
 html[data-theme="green"] .mobile-bottom-nav{background:rgba(255,255,255,.98)!important;border-top:1px solid #cfe5d6!important;box-shadow:0 -8px 24px rgba(7,91,61,.10)!important}
 html[data-theme="green"] .mobile-nav-item{background:transparent!important;color:#547564!important}
 html[data-theme="green"] .mobile-nav-item.active{background:#e4f5e9!important;color:#087a4b!important}
 html[data-theme="green"] .mobile-menu-backdrop{background:rgba(7,65,43,.38)!important;backdrop-filter:blur(2px)!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]){background:#fff!important;color:#174b35!important;border:1px solid #cfe5d6!important;border-radius:0 20px 20px 0!important;box-shadow:18px 0 48px rgba(7,91,61,.18)!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-brand{background:linear-gradient(135deg,#075b3d,#0b955d)!important;border-color:#d5e9dc!important;color:#fff!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-brand strong{color:#fff!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-brand span{color:#dff7e8!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile{border-color:#d5e9dc!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile strong{color:#174b35!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile span{color:#60796b!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-avatar{background:linear-gradient(145deg,#b9e6c8,#e5f4e9)!important;color:#075b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-home{background:#e6f5eb!important;border:1px solid #cfe5d6!important;color:#075b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-group{border-color:#e0eee4!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-group h3{color:#087a4b!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a{background:#f4faf6!important;border-color:#d8ebdf!important;color:#175b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active{background:#dff3e5!important;border-color:#9bcfae!important;color:#05613e!important;box-shadow:inset 3px 0 #0b955d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-icon{color:#087a4b!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-chevron{color:#60796b!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-close{background:#e5f4e9!important;border-color:#c6e2d0!important;color:#075b3d!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-logout{border-color:#d5e9dc!important}
 html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button{background:#fff1ee!important;border-color:#f2c8bf!important;color:#a23a2a!important}
}

/* LAST OVERRIDE: remove legacy dark surfaces only when GREEN theme is active */
html[data-theme="green"],html[data-theme="green"] body,html[data-theme="green"] .app,html[data-theme="green"] .layout,html[data-theme="green"] .main,html[data-theme="green"] .main-content,html[data-theme="green"] .content,html[data-theme="green"] .page-content,html[data-theme="green"] .dashboard,html[data-theme="green"] .dashboard-content,html[data-theme="green"] .workspace{background:#f3faf5!important;background-color:#f3faf5!important;color:#174b35!important}
html[data-theme="green"] .card,html[data-theme="green"] .panel,html[data-theme="green"] .stat,html[data-theme="green"] .stat-card,html[data-theme="green"] .widget,html[data-theme="green"] .item,html[data-theme="green"] .table-wrap,html[data-theme="green"] .content-box,html[data-theme="green"] .section-card,html[data-theme="green"] .modal-content,html[data-theme="green"] .dialog{background:#fff!important;background-color:#fff!important;color:#174b35!important;border-color:#d5e9dc!important}
@media(max-width:760px){
html[data-theme="green"] .mobile-bottom-nav{background:#fff!important;background-color:#fff!important;border-color:#cfe5d6!important}
html[data-theme="green"] .mobile-nav-item{background:transparent!important;color:#547564!important}
html[data-theme="green"] .mobile-nav-item.active{background:#e4f5e9!important;color:#087a4b!important}
html[data-theme="green"] .mobile-menu-backdrop{background:rgba(7,65,43,.35)!important}
html[data-theme="green"] .mobile-menu-sheet,html[data-theme="green"] #mobileMenuSheet:not([hidden]){background:#fff!important;background-image:none!important;color:#174b35!important;border-color:#cfe5d6!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-group,html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile,html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-logout{border-color:#d5e9dc!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a{background:#f4faf6!important;color:#175b3d!important;border-color:#d8ebdf!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-menu-grid a.active{background:#dff3e5!important;color:#05613e!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile strong,html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-label{color:#174b35!important}
html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-profile span,html[data-theme="green"] #mobileMenuSheet:not([hidden]) .mobile-drawer-chevron{color:#60796b!important}
}

/* FIX FORM TAB PANELS: student and lecturer forms must never inherit dark theme */
html[data-theme="green"] .student-tab-panel,
html[data-theme="green"] .lecturer-create-panel .student-tab-panel,
html[data-theme="green"] #studentCreateForm .student-tab-panel,
html[data-theme="green"] #lecturerCreateForm .student-tab-panel {
  background:#ffffff!important;
  background-color:#ffffff!important;
  color:#174b35!important;
  border:1px solid #d5e9dc!important;
  border-radius:14px!important;
  padding:18px!important;
}
html[data-theme="green"] .student-tab-panel h3,
html[data-theme="green"] .student-tab-panel h4 {color:#075b3d!important}
html[data-theme="green"] .student-tab-panel p,
html[data-theme="green"] .student-tab-panel .muted {color:#60796b!important}
html[data-theme="green"] .student-tab-panel label,
html[data-theme="green"] .student-tab-panel .student-fields label,
html[data-theme="green"] .student-tab-panel .student-photo-control label {color:#315b43!important}
html[data-theme="green"] .student-tab-panel input,
html[data-theme="green"] .student-tab-panel select,
html[data-theme="green"] .student-tab-panel textarea {
  background:#ffffff!important;background-color:#ffffff!important;color:#174b35!important;border:1px solid #bddbc8!important;
}
html[data-theme="green"] .student-tab-panel input::placeholder,
html[data-theme="green"] .student-tab-panel textarea::placeholder {color:#819b8c!important}
html[data-theme="green"] .student-tab-panel .student-security-note,
html[data-theme="green"] .student-tab-panel .student-profile-upload {background:#f2faf4!important;color:#47624e!important;border-color:#d5e9dc!important}
html[data-theme="green"] .student-tab-panel[hidden] {display:none!important}

/* NAVIGATION HOTFIX — authoritative mobile drawer layout (overrides legacy rules) */
@media (max-width:760px) {
  #mobileMenuSheet:not([hidden]) {
    position:fixed!important;
    inset:0 auto 0 0!important;
    width:min(86vw,380px)!important;
    max-width:calc(100vw - 44px)!important;
    height:100vh!important;
    height:100dvh!important;
    min-height:100dvh!important;
    max-height:100dvh!important;
    box-sizing:border-box!important;
    margin:0!important;
    padding:calc(14px + env(safe-area-inset-top)) 18px calc(18px + env(safe-area-inset-bottom))!important;
    display:flex!important;
    flex-direction:column!important;
    align-items:stretch!important;
    gap:0!important;
    overflow-x:hidden!important;
    overflow-y:auto!important;
    overscroll-behavior:contain!important;
    border:0!important;
    border-right:1px solid #cfe5d6!important;
    border-radius:0 22px 22px 0!important;
    background:#fff!important;
    color:#174b35!important;
    box-shadow:18px 0 48px rgba(7,55,35,.22)!important;
    z-index:1002!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand {
    flex:0 0 auto!important;
    min-height:76px!important;
    margin:0!important;
    padding:8px 0 16px!important;
    background:transparent!important;
    border-bottom:1px solid #d5e9dc!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    flex:0 0 auto!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    display:flex!important;
    flex-direction:column!important;
    gap:14px!important;
    min-height:0!important;
    overflow:visible!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid {
    display:grid!important;
    grid-template-columns:minmax(0,1fr)!important;
    gap:8px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {
    display:flex!important;
    align-items:center!important;
    min-height:48px!important;
    width:100%!important;
    box-sizing:border-box!important;
  }
  #mobileMenuBackdrop:not([hidden]),.mobile-menu-backdrop:not([hidden]) {
    position:fixed!important;
    inset:0!important;
    z-index:1001!important;
  }
  .mobile-bottom-nav {
    bottom:0!important;
    padding-bottom:calc(7px + env(safe-area-inset-bottom))!important;
    z-index:1000!important;
  }
  body { padding-bottom:calc(76px + env(safe-area-inset-bottom))!important; }
}



/* COMPACT ALL-MENU VIEW — show every permitted menu without drawer scrolling */
@media (max-width:760px) {
  #mobileMenuSheet:not([hidden]) {
    width:min(94vw,480px)!important;
    max-width:100vw!important;
    height:100dvh!important;
    max-height:100dvh!important;
    overflow:hidden!important;
    padding:calc(8px + env(safe-area-inset-top)) 12px calc(8px + env(safe-area-inset-bottom))!important;
    gap:0!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand {
    min-height:54px!important;
    padding:2px 0 7px!important;
    margin:0!important;
    gap:9px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand img {
    width:38px!important;height:38px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand strong {font-size:12px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand span {font-size:9px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-close {
    width:34px!important;height:34px!important;flex:0 0 34px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile {
    padding:7px 2px 7px!important;
    margin:0 0 6px!important;
    gap:8px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-avatar {
    width:30px!important;height:30px!important;flex:0 0 30px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-avatar svg {width:17px!important;height:17px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile strong {font-size:11px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile span {font-size:9px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home {
    min-height:34px!important;padding:5px 9px!important;margin:0 0 7px!important;
    border-radius:9px!important;font-size:11px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    display:grid!important;
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    align-content:start!important;
    align-items:start!important;
    gap:7px!important;
    min-height:0!important;
    overflow:hidden!important;
    flex:1 1 auto!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group {
    min-width:0!important;
    padding:6px!important;
    border:1px solid #dcece1!important;
    border-radius:10px!important;
    background:#fbfefc!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group:first-child {
    grid-column:1 / -1!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group h3 {
    margin:0 0 5px!important;
    font-size:9px!important;
    line-height:1.2!important;
    letter-spacing:.06em!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid {
    display:grid!important;
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    gap:4px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group:first-child .mobile-menu-grid {
    grid-template-columns:repeat(3,minmax(0,1fr))!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {
    min-width:0!important;
    min-height:31px!important;
    height:31px!important;
    padding:3px 5px!important;
    gap:4px!important;
    border-radius:7px!important;
    font-size:9px!important;
    line-height:1.1!important;
    white-space:normal!important;
    overflow:hidden!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-icon svg {
    width:14px!important;height:14px!important;flex:0 0 14px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-label {
    min-width:0!important;
    font-size:9px!important;
    line-height:1.1!important;
    overflow-wrap:anywhere!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-chevron {display:none!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout {
    flex:0 0 auto!important;
    margin-top:6px!important;
    padding-top:6px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button {
    min-height:32px!important;
    margin:0!important;
    padding:6px 10px!important;
    font-size:10px!important;
  }
}
@media (max-width:380px) {
  #mobileMenuSheet:not([hidden]) {padding-left:8px!important;padding-right:8px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-group {padding:4px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {font-size:8px!important;padding:2px 3px!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-label {font-size:8px!important}
}
@media (max-width:760px) and (max-height:700px) {
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile {display:none!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home {margin-bottom:4px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-groups {gap:4px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-group {padding:4px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid {gap:3px!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {min-height:27px!important;height:27px!important}
}


/* FINAL MOBILE DRAWER FIX: full-height panel, natural vertical menu, no clipped groups */
@media (max-width:760px) {
  #mobileMenuSheet:not([hidden]) {
    position:fixed!important;top:0!important;right:auto!important;bottom:0!important;left:0!important;
    width:min(88vw,410px)!important;max-width:calc(100vw - 24px)!important;
    height:100vh!important;height:100dvh!important;min-height:100dvh!important;max-height:100dvh!important;
    box-sizing:border-box!important;display:flex!important;flex-direction:column!important;
    align-items:stretch!important;gap:0!important;margin:0!important;
    padding:calc(10px + env(safe-area-inset-top)) 16px calc(14px + env(safe-area-inset-bottom))!important;
    overflow-x:hidden!important;overflow-y:auto!important;overscroll-behavior:contain!important;
    -webkit-overflow-scrolling:touch!important;
    background:#fff!important;color:#174b35!important;border:0!important;
    border-right:1px solid #cfe5d6!important;border-radius:0 20px 20px 0!important;
    box-shadow:18px 0 48px rgba(7,55,35,.22)!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand {
    position:relative!important;top:auto!important;flex:0 0 auto!important;
    min-height:76px!important;height:auto!important;display:flex!important;
    align-items:center!important;gap:12px!important;margin:0!important;padding:8px 0 14px!important;
    background:linear-gradient(135deg,#075b3d,#0b955d)!important;
    border:0!important;border-radius:12px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout {flex:0 0 auto!important;min-width:0!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile {
    display:flex!important;align-items:center!important;padding:12px 2px!important;margin:0 0 8px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home {
    display:flex!important;min-height:46px!important;height:auto!important;margin:0 0 12px!important;padding:10px 12px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    display:flex!important;flex-direction:column!important;align-items:stretch!important;gap:12px!important;
    min-height:0!important;height:auto!important;overflow:visible!important;overflow-y:visible!important;padding:0 0 10px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group {
    display:block!important;flex:0 0 auto!important;width:100%!important;min-width:0!important;height:auto!important;
    margin:0!important;padding:10px!important;overflow:visible!important;
    background:#fbfefc!important;border:1px solid #dcece1!important;border-radius:12px!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group h3 {display:block!important;margin:0 0 8px!important;font-size:10px!important;line-height:1.3!important}
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid {
    display:grid!important;grid-template-columns:minmax(0,1fr)!important;grid-auto-flow:row!important;
    gap:6px!important;width:100%!important;height:auto!important;overflow:visible!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {
    display:flex!important;align-items:center!important;width:100%!important;min-width:0!important;
    min-height:46px!important;height:auto!important;box-sizing:border-box!important;
    padding:10px 11px!important;gap:10px!important;overflow:visible!important;white-space:normal!important;
    font-size:13px!important;line-height:1.35!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-label {
    display:block!important;flex:1 1 auto!important;min-width:0!important;
    font-size:13px!important;line-height:1.35!important;white-space:normal!important;
    overflow:visible!important;overflow-wrap:anywhere!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-chevron {display:block!important}
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout {
    display:block!important;position:static!important;margin:8px 0 0!important;padding:12px 0 0!important;
    border-top:1px solid #d8ebdf!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button {
    width:100%!important;min-height:46px!important;height:auto!important;margin:0!important;padding:10px 12px!important;font-size:13px!important;
  }
}

/* VIEWPORT EDGE FIX — drawer starts at screen top and extends to screen bottom */
@media screen and (max-width:760px) {
  html, body { min-height:100%!important; }
  #mobileMenuSheet:not([hidden]) {
    position:fixed!important;
    inset:0 auto 0 0!important;
    top:0!important;bottom:0!important;left:0!important;right:auto!important;
    width:min(92vw,440px)!important;
    max-width:100vw!important;
    height:100vh!important;
    height:100dvh!important;
    min-height:100vh!important;
    min-height:100dvh!important;
    max-height:100vh!important;
    max-height:100dvh!important;
    margin:0!important;
    padding:0 14px calc(12px + env(safe-area-inset-bottom))!important;
    box-sizing:border-box!important;
    transform:none!important;
    translate:0 0!important;
    display:flex!important;
    flex-direction:column!important;
    align-items:stretch!important;
    justify-content:flex-start!important;
    gap:0!important;
    overflow-x:hidden!important;
    overflow-y:auto!important;
    overscroll-behavior-y:contain!important;
    -webkit-overflow-scrolling:touch!important;
    border:0!important;
    border-right:1px solid #cfe5d6!important;
    border-radius:0 16px 16px 0!important;
    background:#fff!important;
    background-image:none!important;
    color:#174b35!important;
    z-index:2147483000!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-brand {
    position:relative!important;
    inset:auto!important;
    top:0!important;
    flex:0 0 auto!important;
    width:calc(100% + 28px)!important;
    min-height:112px!important;
    height:auto!important;
    box-sizing:border-box!important;
    margin:0 -14px 10px!important;
    padding:calc(12px + env(safe-area-inset-top)) 16px 14px!important;
    display:flex!important;
    align-items:center!important;
    gap:12px!important;
    border:0!important;
    border-radius:0!important;
    background:linear-gradient(135deg,#075b3d,#0b955d)!important;
    color:#fff!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-profile,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-home,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups,
  #mobileMenuSheet:not([hidden]) .mobile-drawer-logout {
    position:relative!important;
    flex:0 0 auto!important;
    transform:none!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    display:flex!important;
    flex-direction:column!important;
    gap:10px!important;
    min-height:0!important;
    height:auto!important;
    overflow:visible!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-group {
    display:block!important;
    flex:0 0 auto!important;
    width:100%!important;
    height:auto!important;
    overflow:visible!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid {
    display:grid!important;
    grid-template-columns:minmax(0,1fr)!important;
    grid-auto-flow:row!important;
    height:auto!important;
    overflow:visible!important;
  }
  #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {
    min-height:44px!important;
    height:auto!important;
    overflow:visible!important;
  }
}

/* DRAWER VISIBILITY + FULL HEIGHT — body-level overlay, all menu groups reachable */
@media screen and (max-width:760px) {
  #mobileMenuBackdrop:not([hidden]) {
    display:block!important;position:fixed!important;inset:0!important;
    width:100vw!important;height:100vh!important;height:100dvh!important;
    z-index:2147482000!important;background:rgba(7,65,43,.38)!important;
    backdrop-filter:blur(2px)!important;-webkit-backdrop-filter:blur(2px)!important;
  }
  body > #mobileMenuSheet:not([hidden]) {
    display:flex!important;visibility:visible!important;opacity:1!important;
    position:fixed!important;inset:0 auto 0 0!important;
    width:min(88vw,420px)!important;max-width:calc(100vw - 24px)!important;
    height:100vh!important;height:100dvh!important;
    min-height:100dvh!important;max-height:100dvh!important;
    box-sizing:border-box!important;margin:0!important;
    padding:0 14px calc(12px + env(safe-area-inset-bottom))!important;
    flex-direction:column!important;align-items:stretch!important;justify-content:flex-start!important;
    gap:0!important;overflow-x:hidden!important;overflow-y:auto!important;
    overscroll-behavior:contain!important;-webkit-overflow-scrolling:touch!important;
    transform:none!important;translate:0 0!important;
    background:#fff!important;background-image:none!important;color:#174b35!important;
    border:0!important;border-right:1px solid #cfe5d6!important;
    border-radius:0 16px 16px 0!important;
    box-shadow:18px 0 48px rgba(7,55,35,.24)!important;
    z-index:2147483001!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-brand {
    flex:0 0 auto!important;width:calc(100% + 28px)!important;
    min-height:108px!important;height:auto!important;box-sizing:border-box!important;
    margin:0 -14px 10px!important;
    padding:calc(12px + env(safe-area-inset-top)) 16px 14px!important;
    display:flex!important;align-items:center!important;gap:12px!important;
    background:linear-gradient(135deg,#075b3d,#0b955d)!important;
    border:0!important;border-radius:0!important;color:#fff!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-profile,
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-home {
    display:flex!important;flex:0 0 auto!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    display:flex!important;flex:1 1 auto!important;flex-direction:column!important;
    align-items:stretch!important;gap:10px!important;min-height:0!important;
    overflow-x:hidden!important;overflow-y:auto!important;
    padding:0 2px 12px!important;-webkit-overflow-scrolling:touch!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-menu-group {
    display:block!important;flex:0 0 auto!important;width:100%!important;
    height:auto!important;overflow:visible!important;padding:10px!important;
    background:#fbfefc!important;border:1px solid #dcece1!important;border-radius:12px!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-menu-grid {
    display:grid!important;grid-template-columns:minmax(0,1fr)!important;
    grid-auto-flow:row!important;gap:5px!important;height:auto!important;overflow:visible!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {
    display:flex!important;width:100%!important;min-height:44px!important;height:auto!important;
    box-sizing:border-box!important;padding:10px!important;overflow:visible!important;
    white-space:normal!important;font-size:13px!important;line-height:1.35!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-label {
    display:block!important;min-width:0!important;flex:1 1 auto!important;
    white-space:normal!important;overflow-wrap:anywhere!important;font-size:13px!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-logout {
    display:block!important;flex:0 0 auto!important;position:static!important;
    margin:8px 0 0!important;padding:10px 0 calc(4px + env(safe-area-inset-bottom))!important;
    background:#fff!important;border-top:1px solid #d8ebdf!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button {
    width:100%!important;min-height:44px!important;height:auto!important;margin:0!important;
  }
}

/* FINAL ANDROID DRAWER FIX: use the complete viewport height without clipping */
@media screen and (max-width:760px) {
  html, body { min-height:100%; }
  body > #mobileMenuBackdrop:not([hidden]) {
    position:fixed!important; inset:0!important;
    width:100vw!important; height:100vh!important; height:100dvh!important;
    display:block!important; z-index:2147483000!important;
    background:rgba(7,65,43,.42)!important;
  }
  body > #mobileMenuSheet:not([hidden]) {
    position:fixed!important; inset:0!important;
    display:flex!important; flex-direction:column!important;
    align-items:stretch!important; justify-content:flex-start!important;
    width:100vw!important; max-width:100vw!important;
    height:100vh!important; height:100dvh!important;
    min-height:100vh!important; min-height:100dvh!important;
    max-height:100dvh!important; box-sizing:border-box!important;
    margin:0!important;
    padding:env(safe-area-inset-top) 16px calc(12px + env(safe-area-inset-bottom))!important;
    overflow:hidden!important; overflow-x:hidden!important;
    background:#fff!important; color:#174b35!important;
    border:0!important; border-radius:0!important;
    box-shadow:none!important; transform:none!important;
    z-index:2147483001!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-brand {
    flex:0 0 auto!important; width:calc(100% + 32px)!important;
    min-height:104px!important; height:auto!important;
    margin:0 -16px 10px!important;
    padding:calc(14px + env(safe-area-inset-top)) 18px 14px!important;
    display:flex!important; align-items:center!important;
    box-sizing:border-box!important; overflow:visible!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-profile,
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-home {
    display:flex!important; flex:0 0 auto!important;
    min-width:0!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-groups {
    display:flex!important; flex:1 1 0!important;
    flex-direction:column!important; align-items:stretch!important;
    min-height:0!important; height:auto!important; max-height:none!important;
    overflow-x:hidden!important; overflow-y:auto!important;
    overscroll-behavior:contain!important;
    -webkit-overflow-scrolling:touch!important;
    padding:0 2px 14px!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-menu-group {
    display:block!important; flex:0 0 auto!important;
    width:100%!important; height:auto!important; overflow:visible!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-menu-grid {
    display:grid!important; grid-template-columns:minmax(0,1fr)!important;
    grid-auto-flow:row!important; height:auto!important; overflow:visible!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-menu-grid a {
    display:flex!important; width:100%!important; min-height:46px!important;
    height:auto!important; box-sizing:border-box!important;
    white-space:normal!important; overflow:visible!important;
    line-height:1.4!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-label {
    min-width:0!important; flex:1 1 auto!important;
    white-space:normal!important; overflow-wrap:anywhere!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-logout {
    display:block!important; position:static!important;
    flex:0 0 auto!important; margin:8px 0 0!important;
    padding:10px 0 calc(4px + env(safe-area-inset-bottom))!important;
    background:#fff!important;
  }
  body > #mobileMenuSheet:not([hidden]) .mobile-drawer-logout button {
    width:100%!important; min-height:48px!important; height:auto!important;
  }
}



/* Keep the admin header actions below Android's status/notification bar. */
@media screen and (max-width:760px) {
  .top {
    position:relative!important;
    z-index:5!important;
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    box-sizing:border-box!important;
    width:100%!important;
    height:auto!important;
    min-height:92px!important;
    padding:calc(24px + env(safe-area-inset-top, 0px)) 16px 10px!important;
    gap:12px!important;
  }
  .top > strong {
    min-width:0!important;
    line-height:1.35!important;
    overflow-wrap:anywhere!important;
  }
  .top > form {
    display:flex!important;
    flex:0 0 auto!important;
    align-items:center!important;
    margin:0!important;
    padding:0!important;
  }
  .top .logout {
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    min-height:44px!important;
    padding:10px 18px!important;
    line-height:1.2!important;
    white-space:nowrap!important;
  }
}

/* Admin Android navigation: no drawer/popup; retain four direct destinations. */
@media screen and (max-width:760px) {
  .mobile-bottom-nav { grid-template-columns:repeat(4,minmax(0,1fr))!important; }
  .mobile-menu-sheet,.mobile-menu-backdrop,.mobile-more-trigger,
  #mobileMenuSheet,#mobileMenuBackdrop,#mobileMenuToggle { display:none!important; }
}


/* Ensure the student editor escapes dashboard containers/overflow on Android WebView. */
body>.student-detail-backdrop{position:fixed!important;inset:0!important;z-index:2147483647!important;display:flex!important;align-items:center!important;justify-content:center!important;overflow-y:auto!important;overscroll-behavior:contain!important;padding:12px!important;box-sizing:border-box!important}
body>.student-detail-backdrop>.student-detail-modal{position:relative!important;display:block!important;visibility:visible!important;opacity:1!important;width:min(920px,100%)!important;max-height:calc(100dvh - 24px)!important;overflow-x:hidden!important;overflow-y:auto!important;margin:auto!important;transform:none!important;box-sizing:border-box!important;-webkit-overflow-scrolling:touch!important}
@media(max-width:760px){body>.student-detail-backdrop{align-items:flex-end!important;padding:0!important}body>.student-detail-backdrop>.student-detail-modal{width:100%!important;max-height:92vh!important;max-height:92dvh!important;border-radius:22px 22px 0 0!important;padding:18px 16px calc(20px + env(safe-area-inset-bottom))!important}}
</style>
<script>
(function(){
  function mountStudentEditor(){
    var backdrop=document.querySelector('.student-detail-backdrop');
    if(!backdrop) return;
    /* Move the overlay out of dashboard wrappers that may clip fixed elements in Android WebView. */
    if(backdrop.parentElement!==document.body) document.body.appendChild(backdrop);
    document.body.classList.add('student-detail-open');
    backdrop.style.setProperty('display','flex','important');
    backdrop.style.setProperty('position','fixed','important');
    backdrop.style.setProperty('inset','0','important');
    backdrop.style.setProperty('z-index','2147483647','important');
    var modal=backdrop.querySelector('.student-detail-modal');
    if(modal){modal.setAttribute('tabindex','-1');try{modal.focus({preventScroll:true});}catch(e){modal.focus();}}
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',mountStudentEditor,{once:true}); else mountStudentEditor();
})();
</script>
</body></html>
