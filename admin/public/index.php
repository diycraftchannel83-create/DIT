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
function tableCount(PDO $pdo, string $table): int {
    $allowed=['users','students','lecturers','study_programs','courses','course_classes','pmb_applicants','tuition_bills','payments'];
    if (!in_array($table,$allowed,true)) return 0;
    try { return (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn(); } catch(Throwable) { return 0; }
}

$page = (string)($_GET['page'] ?? (user() ? 'dashboard' : 'login'));
$academicRoles=['super_admin','admin_akademik'];
$viewRoles=['super_admin','admin_akademik','ketua_prodi','ketua_stit'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    checkCsrf();
    $action=(string)($_POST['action']??'');
    if ($action==='login') {
        $username=trim((string)($_POST['username']??'')); $password=(string)($_POST['password']??'');
        $q=$pdo->prepare('SELECT u.id,u.username,u.full_name,u.email,u.password_hash,u.status,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=:username LIMIT 1');
        $q->execute(['username'=>$username]); $u=$q->fetch();
        if (!$u || $u['status']!=='active' || !password_verify($password,$u['password_hash'])) {
            flash('Username atau password tidak valid.','error'); go('login');
        }
        if (!in_array($u['role'],['super_admin','admin_akademik','admin_keuangan','ketua_prodi','ketua_stit'],true)) {
            flash('Akun ini tidak memiliki akses ke Admin Web.','error'); go('login');
        }
        session_regenerate_id(true);
        $_SESSION['admin_user']=['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'role'=>$u['role']];
        $_SESSION['csrf']=bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE users SET last_login_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$u['id']]);
        go('dashboard');
    }
    if ($action==='logout') { $_SESSION=[]; session_regenerate_id(true); go('login'); }
    $u=requireLogin();
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
    if ($action==='save_semester') {
        allow($academicRoles);
        $year=(int)($_POST['academic_year_id']??0); $name=(string)($_POST['name']??''); $start=(string)($_POST['start_date']??''); $end=(string)($_POST['end_date']??'');
        if(!$year || !in_array($name,['Ganjil','Genap','Antara'],true) || !$start || !$end || $start>$end) { flash('Data semester tidak valid.','error'); go('semesters'); }
        try {$q=$pdo->prepare('INSERT INTO semesters(academic_year_id,name,start_date,end_date,is_active) VALUES(:year,:name,:start,:end,0)');$q->execute(['year'=>$year,'name'=>$name,'start'=>$start,'end'=>$end]);flash('Semester berhasil ditambahkan.');}
        catch(Throwable $e){flash('Gagal menambahkan semester. Kombinasi tahun dan semester mungkin sudah ada.','error');}
        go('semesters');
    }
}

$u=user();
if ($page!=='login' && !$u) go('login');
if ($page==='login' && $u) go('dashboard');
$flash=$_SESSION['flash']??null; unset($_SESSION['flash']);
$labels=['super_admin'=>'Super Admin','admin_akademik'=>'Admin Akademik','admin_keuangan'=>'Admin Keuangan','ketua_prodi'=>'Ketua Program Studi','ketua_stit'=>'Ketua STIT'];
$menus=[
 'dashboard'=>'Dashboard','programs'=>'Program Studi','years'=>'Tahun Akademik','semesters'=>'Semester',
 'students'=>'Mahasiswa','lecturers'=>'Dosen','courses'=>'Mata Kuliah','classes'=>'Kelas Kuliah','pmb'=>'PMB'
];
if ($page==='login') {
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login Admin - STIT Darul Ilmi</title><style>
*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#0c2545,#126b70);font:15px system-ui;color:#172033}.login{width:min(420px,92vw);background:white;padding:34px;border-radius:18px;box-shadow:0 20px 70px #0019}.mark{width:54px;height:54px;border-radius:15px;background:#0d5b67;color:white;display:grid;place-items:center;font-weight:800;font-size:19px}.muted{color:#667085}label{display:block;font-weight:600;margin:16px 0 6px}input{width:100%;padding:13px;border:1px solid #d0d5dd;border-radius:9px;font:inherit}button{width:100%;margin-top:22px;padding:13px;border:0;border-radius:9px;background:#0d5b67;color:white;font-weight:700;font-size:15px;cursor:pointer}.alert{padding:11px;border-radius:8px;background:#fff1f0;color:#a32118;margin:14px 0}</style></head><body><main class="login"><div class="mark">STIT</div><h1>Admin Portal</h1><p class="muted">STIT Darul Ilmi Tasikmalaya</p><?php if($flash):?><div class="alert"><?=e($flash['message'])?></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="login"><label>Username</label><input name="username" autocomplete="username" required><label>Password</label><input type="password" name="password" autocomplete="current-password" required><button type="submit">Masuk ke Admin Portal</button></form><p class="muted" style="font-size:12px;margin-top:22px">Gunakan akun admin yang dibuat melalui prosedur setup backend. Tidak ada akun bawaan.</p></main></body></html><?php exit;
}
$datasets=[
 'programs'=>['title'=>'Program Studi','sql'=>'SELECT id,code,name,degree_level,is_active FROM study_programs ORDER BY name'],
 'years'=>['title'=>'Tahun Akademik','sql'=>'SELECT id,name,start_date,end_date,is_active FROM academic_years ORDER BY start_date DESC'],
 'semesters'=>['title'=>'Semester','sql'=>'SELECT s.id,y.name AS academic_year,s.name,s.start_date,s.end_date,s.is_active FROM semesters s JOIN academic_years y ON y.id=s.academic_year_id ORDER BY s.start_date DESC'],
 'students'=>['title'=>'Mahasiswa','sql'=>'SELECT s.nim,u.full_name,p.name AS program,s.entry_year,s.status FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN study_programs p ON p.id=s.study_program_id ORDER BY u.full_name LIMIT 200'],
 'lecturers'=>['title'=>'Dosen','sql'=>'SELECT l.lecturer_number,u.full_name,u.email,l.phone FROM lecturers l JOIN users u ON u.id=l.user_id ORDER BY u.full_name LIMIT 200'],
 'courses'=>['title'=>'Mata Kuliah','sql'=>'SELECT c.code,c.name,c.credits,p.name AS program,c.recommended_semester FROM courses c JOIN study_programs p ON p.id=c.study_program_id ORDER BY p.name,c.recommended_semester,c.name LIMIT 300'],
 'classes'=>['title'=>'Kelas Kuliah','sql'=>'SELECT c.class_name,co.code,co.name AS course,s.name AS semester,u.full_name AS lecturer,c.capacity FROM course_classes c JOIN courses co ON co.id=c.course_id JOIN semesters s ON s.id=c.semester_id LEFT JOIN lecturers l ON l.id=c.lecturer_id LEFT JOIN users u ON u.id=l.user_id ORDER BY s.start_date DESC LIMIT 300'],
 'pmb'=>['title'=>'Penerimaan Mahasiswa Baru','sql'=>'SELECT registration_number,full_name,email,phone,status,created_at FROM pmb_applicants ORDER BY created_at DESC LIMIT 300']
];
if ($page!=='dashboard' && !isset($datasets[$page])) $page='dashboard';
if (in_array($page,['programs','years','semesters'],true)) allow($academicRoles); elseif ($page==='dashboard') allow(['super_admin','admin_akademik','admin_keuangan','ketua_prodi','ketua_stit']); else allow($viewRoles);
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($menus[$page]??'Dashboard')?> - STIT Darul Ilmi</title><style>
:root{--navy:#102b46;--teal:#0d6870;--bg:#f3f6fa;--ink:#1d2939;--muted:#667085}*{box-sizing:border-box}body{margin:0;background:var(--bg);font:14px system-ui;color:var(--ink)}.layout{display:flex;min-height:100vh}.side{width:245px;background:var(--navy);color:#fff;padding:24px 16px;flex-shrink:0}.brand{font-size:17px;font-weight:800;padding:5px 10px 25px}.brand small{display:block;color:#b6c9d9;font-weight:400;font-size:12px;margin-top:5px}.nav a{display:block;color:#dce8f1;text-decoration:none;padding:11px 13px;border-radius:8px;margin:3px 0}.nav a.active,.nav a:hover{background:#ffffff1b;color:#fff}.main{flex:1;min-width:0}.top{height:72px;background:white;border-bottom:1px solid #e5eaf0;display:flex;align-items:center;justify-content:space-between;padding:0 30px}.content{padding:28px 30px;max-width:1400px;margin:auto}h1{font-size:25px;margin:0 0 7px}.muted{color:var(--muted)}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:15px;margin:24px 0}.card,.panel{background:white;border:1px solid #e7ecf2;border-radius:12px;padding:19px}.card strong{display:block;font-size:28px;margin-top:7px}.panel{margin-top:20px;overflow:auto}.panel h2{font-size:17px;margin:0 0 16px}table{border-collapse:collapse;width:100%;min-width:580px}th,td{text-align:left;padding:12px 13px;border-bottom:1px solid #edf0f4}th{background:#f8fafc;color:#475467;font-size:12px;text-transform:uppercase;letter-spacing:.03em}form.inline{display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin-bottom:18px}form.inline label{display:block;font-size:12px;color:#475467;margin-bottom:5px}input,select{padding:10px;border:1px solid #d0d5dd;border-radius:7px;font:inherit}button,.btn{padding:10px 14px;border:0;border-radius:7px;background:var(--teal);color:white;font-weight:650;cursor:pointer;text-decoration:none}.alert{padding:12px 14px;border-radius:8px;margin:0 0 18px;background:#e8f6ee;color:#17663a}.alert.error{background:#fff0ee;color:#a32118}.logout{background:#eef2f6;color:#344054}.tag{background:#edf7f7;color:#0d6870;border-radius:20px;padding:4px 9px;font-size:12px}@media(max-width:760px){.layout{display:block}.side{width:100%;padding:12px}.brand{padding:5px 8px 12px}.nav{display:flex;overflow:auto;gap:4px}.nav a{white-space:nowrap}.top{height:58px;padding:0 16px}.content{padding:20px 14px}}
</style></head><body><div class="layout"><aside class="side"><div class="brand">STIT DARUL ILMI<small>Academic Portal</small></div><nav class="nav"><?php foreach($menus as $key=>$label): if(in_array($key,['programs','years','semesters'],true)&&!in_array($u['role'],$academicRoles,true))continue;?><a class="<?=$page===$key?'active':''?>" href="?page=<?=$key?>"><?=e($label)?></a><?php endforeach;?></nav></aside><main class="main"><header class="top"><strong><?=e($labels[$u['role']]??$u['role'])?></strong><form method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="logout"><button class="logout">Keluar</button></form></header><section class="content"><?php if($flash):?><div class="alert <?=$flash['type']==='error'?'error':''?>"><?=e($flash['message'])?></div><?php endif;?>
<?php if($page==='dashboard'):?><h1>Dashboard Akademik</h1><p class="muted">Ringkasan data STIT Darul Ilmi Tasikmalaya.</p><div class="cards"><?php foreach(['students'=>'Mahasiswa','lecturers'=>'Dosen','study_programs'=>'Program Studi','courses'=>'Mata Kuliah','course_classes'=>'Kelas Kuliah','pmb_applicants'=>'Pendaftar PMB'] as $t=>$label):?><div class="card"><span class="muted"><?=e($label)?></span><strong><?=number_format(tableCount($pdo,$t))?></strong></div><?php endforeach;?></div><div class="panel"><h2>Informasi Sistem</h2><p>Dashboard terhubung ke database akademik bersama dengan REST API Phase 1. Gunakan menu di samping untuk membuka data yang tersedia.</p><p class="muted">Data yang ditampilkan mengikuti isi database saat ini. Pastikan konfigurasi dan backup database sudah benar sebelum memasukkan data riil.</p></div>
<?php else: $ds=$datasets[$page];$rows=$pdo->query($ds['sql'])->fetchAll();?><h1><?=e($ds['title'])?></h1><p class="muted">Data dari database akademik terpadu.</p>
<?php if($page==='programs'&&in_array($u['role'],$academicRoles,true)):?><div class="panel"><h2>Tambah Program Studi</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_program"><div><label>Kode</label><input name="code" maxlength="30" required placeholder="MPI"></div><div><label>Nama Program Studi</label><input name="name" maxlength="160" required placeholder="Manajemen Pendidikan Islam"></div><div><label>Jenjang</label><select name="degree_level"><option>S1</option><option>S2</option><option>S3</option></select></div><button>Simpan</button></form></div><?php endif;?>
<?php if($page==='years'&&in_array($u['role'],$academicRoles,true)):?><div class="panel"><h2>Tambah Tahun Akademik</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_year"><div><label>Nama (contoh 2026/2027)</label><input name="name" pattern="\d{4}/\d{4}" required placeholder="2026/2027"></div><div><label>Tanggal Mulai</label><input type="date" name="start_date" required></div><div><label>Tanggal Selesai</label><input type="date" name="end_date" required></div><label><input type="checkbox" name="is_active" value="1"> Aktif</label><button>Simpan</button></form></div><?php endif;?>
<?php if($page==='semesters'&&in_array($u['role'],$academicRoles,true)): $years=$pdo->query('SELECT id,name FROM academic_years ORDER BY start_date DESC')->fetchAll();?><div class="panel"><h2>Tambah Semester</h2><form class="inline" method="post"><input type="hidden" name="_csrf" value="<?=e(csrf())?>"><input type="hidden" name="action" value="save_semester"><div><label>Tahun Akademik</label><select name="academic_year_id" required><?php foreach($years as $yr):?><option value="<?=$yr['id']?>"><?=e($yr['name'])?></option><?php endforeach;?></select></div><div><label>Semester</label><select name="name"><option>Ganjil</option><option>Genap</option><option>Antara</option></select></div><div><label>Mulai</label><input type="date" name="start_date" required></div><div><label>Selesai</label><input type="date" name="end_date" required></div><button>Simpan</button></form></div><?php endif;?>
<div class="panel"><h2>Daftar <?=e($ds['title'])?> <span class="tag"><?=count($rows)?> data</span></h2><?php if(!$rows):?><p class="muted">Belum ada data untuk ditampilkan.</p><?php else:?><table><thead><tr><?php foreach(array_keys($rows[0]) as $col):?><th><?=e(str_replace('_',' ',$col))?></th><?php endforeach;?></tr></thead><tbody><?php foreach($rows as $row):?><tr><?php foreach($row as $value):?><td><?=e($value===null?'—':$value)?></td><?php endforeach;?></tr><?php endforeach;?></tbody></table><?php endif;?></div><?php endif;?></section></main></div></body></html>
