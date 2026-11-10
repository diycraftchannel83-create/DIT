<?php
declare(strict_types=1);
// Mobile-first portal navigation is rendered only for authenticated users.

/**
 * STIT Darul Ilmi - Student & Lecturer Portal
 * Read-only academic self-service portal. Admin Web remains separate.
 */
$backend = dirname(__DIR__, 2) . '/backend';
$configFile = $backend . '/config/database.local.php';
if (!is_file($configFile)) { http_response_code(503); exit('Backend belum dikonfigurasi.'); }
$cfg = require $configFile;
$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['database'], $cfg['charset'] ?? 'utf8mb4');
try {
    $pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    error_log('[STIT PORTAL DB] ' . $e->getMessage());
    http_response_code(503); exit('Koneksi database gagal. Periksa konfigurasi server.');
}
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('STITPORTAL');
session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Lax']);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function redirectTo(string $page='dashboard'): never { header('Location: ?page=' . rawurlencode($page)); exit; }
function csrfToken(): string { if (empty($_SESSION['portal_csrf'])) $_SESSION['portal_csrf']=bin2hex(random_bytes(32)); return $_SESSION['portal_csrf']; }
function verifyCsrf(): void {
    if (!isset($_POST['_csrf']) || !hash_equals((string)($_SESSION['portal_csrf']??''),(string)$_POST['_csrf'])) {
        http_response_code(419); exit('Sesi formulir kedaluwarsa. Muat ulang halaman.');
    }
}
function portalUser(): ?array { return $_SESSION['portal_user'] ?? null; }
function queryOne(PDO $pdo, string $sql, array $params=[]): ?array { $q=$pdo->prepare($sql);$q->execute($params);$r=$q->fetch();return $r?:null; }
function queryAll(PDO $pdo, string $sql, array $params=[]): array { $q=$pdo->prepare($sql);$q->execute($params);return $q->fetchAll(); }

$flash = $_SESSION['portal_flash'] ?? null; unset($_SESSION['portal_flash']);
$campusLogoUrl='../../website/assets/stit-darul-ilmi-icon.svg';
try { $savedLogo=$pdo->query('SELECT logo_image_url FROM campus_profile WHERE id=1')->fetchColumn(); if(is_string($savedLogo)&&trim($savedLogo)!=='') $campusLogoUrl=trim($savedLogo); } catch(Throwable $logoError) { error_log('[STIT PORTAL LOGO] '.$logoError->getMessage()); }
$page = (string)($_GET['page'] ?? (portalUser() ? 'dashboard' : 'login'));
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf();
    $action=(string)($_POST['action']??'');
    if ($action==='login') {
        $username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');
        $q=$pdo->prepare('SELECT u.id,u.username,u.full_name,u.email,u.password_hash,u.status,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=:username LIMIT 1');
        $q->execute(['username'=>$username]);$u=$q->fetch();
        if (!$u || $u['status']!=='active' || !password_verify($password,$u['password_hash']) || !in_array($u['role'],['mahasiswa','dosen'],true)) {
            $_SESSION['portal_flash']=['type'=>'error','message'=>'Username/password salah atau akun bukan akun mahasiswa/dosen.'];
            redirectTo('login');
        }
        session_regenerate_id(true);
        $_SESSION['portal_user']=['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role']];
        $_SESSION['portal_csrf']=bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE users SET last_login_at=UTC_TIMESTAMP() WHERE id=:id')->execute(['id'=>$u['id']]);
        redirectTo('dashboard');
    }
    if ($action==='update_student_profile' && ($user=portalUser()) && $user['role']==='mahasiswa') {
        $student=queryOne($pdo,'SELECT id,photo_url FROM students WHERE user_id=:uid LIMIT 1',['uid'=>$user['id']]);
        $studentColumns=[];foreach($pdo->query('SHOW COLUMNS FROM students')->fetchAll(PDO::FETCH_COLUMN) as $columnName)$studentColumns[$columnName]=true;
        if (!$student) { http_response_code(403); exit('Profil mahasiswa tidak ditemukan.'); }
        $fullName=trim((string)($_POST['full_name']??''));$email=trim((string)($_POST['email']??''));
        $fields=['phone'=>30,'address'=>5000,'national_id'=>32,'birth_place'=>100,'religion'=>40,'marital_status'=>30,'province'=>100,'city'=>100,'district'=>100,'village'=>100,'postal_code'=>10,'father_name'=>160,'mother_name'=>160,'guardian_name'=>160,'guardian_phone'=>30,'emergency_contact_name'=>160,'emergency_contact_phone'=>30,'admission_pathway'=>60];
        $data=[];foreach($fields as $key=>$max){$value=trim((string)($_POST[$key]??''));if(mb_strlen($value)>$max){$_SESSION['portal_flash']=['type'=>'error','message'=>'Isian '.str_replace('_',' ',$key).' terlalu panjang.'];redirectTo('profile');}$data[$key]=$value!==''?$value:null;}
        $birthDate=trim((string)($_POST['birth_date']??''));$registrationDate=trim((string)($_POST['registration_date']??''));
        foreach(['Tanggal lahir'=>$birthDate,'Tanggal registrasi'=>$registrationDate] as $label=>$date){if($date!==''&&(!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$date)||!checkdate((int)substr($date,5,2),(int)substr($date,8,2),(int)substr($date,0,4)))){$_SESSION['portal_flash']=['type'=>'error','message'=>$label.' tidak valid.'];redirectTo('profile');}}
        $gender=(string)($_POST['gender']??'');if(!in_array($gender,['','male','female'],true)){$_SESSION['portal_flash']=['type'=>'error','message'=>'Pilihan jenis kelamin tidak valid.'];redirectTo('profile');}
        if ($fullName==='' || mb_strlen($fullName)>160 || ($email!=='' && (!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>190))) {
            $_SESSION['portal_flash']=['type'=>'error','message'=>'Periksa kembali nama lengkap dan email.'];redirectTo('profile');
        }
        if ($email!=='') {
            $exists=queryOne($pdo,'SELECT id FROM users WHERE email=:email AND id<>:id LIMIT 1',['email'=>$email,'id'=>$user['id']]);
            if ($exists) { $_SESSION['portal_flash']=['type'=>'error','message'=>'Email tersebut sudah digunakan akun lain.'];redirectTo('profile'); }
        }
        $photoPath=$student['photo_url']??null;
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error']!==UPLOAD_ERR_NO_FILE) {
            $file=$_FILES['profile_photo'];
            if ($file['error']!==UPLOAD_ERR_OK || $file['size']>3*1024*1024) { $_SESSION['portal_flash']=['type'=>'error','message'=>'Foto gagal diunggah atau ukurannya melebihi 3 MB.'];redirectTo('profile'); }
            $image=@getimagesize($file['tmp_name']);$mime=$image['mime']??'';
            $extensions=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!$image || !isset($extensions[$mime])) { $_SESSION['portal_flash']=['type'=>'error','message'=>'Format foto harus JPG, PNG, atau WEBP.'];redirectTo('profile'); }
            $uploadDir=__DIR__.'/uploads/profiles';
            if (!is_dir($uploadDir) && !mkdir($uploadDir,0755,true) && !is_dir($uploadDir)) { $_SESSION['portal_flash']=['type'=>'error','message'=>'Folder penyimpanan foto tidak dapat dibuat.'];redirectTo('profile'); }
            $filename='student_'.$user['id'].'_'.bin2hex(random_bytes(8)).'.'.$extensions[$mime];
            if (!move_uploaded_file($file['tmp_name'],$uploadDir.'/'.$filename)) { $_SESSION['portal_flash']=['type'=>'error','message'=>'Foto tidak berhasil disimpan.'];redirectTo('profile'); }
            $photoPath='uploads/profiles/'.$filename;
        }
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE users SET full_name=:name,email=:email WHERE id=:id')->execute(['name'=>$fullName,'email'=>$email!==''?$email:null,'id'=>$user['id']]);
            $availableFields=['phone','address','national_id','birth_place','birth_date','gender','religion','marital_status','province','city','district','village','postal_code','father_name','mother_name','guardian_name','guardian_phone','emergency_contact_name','emergency_contact_phone','admission_pathway','registration_date','photo_url'];
            $sets=[];$params=['id'=>$student['id']];
            foreach($availableFields as $field){if(!isset($studentColumns[$field])) continue;$sets[]=$field.'=:'.$field;$params[$field]=$field==='birth_date'?($birthDate!==''?$birthDate:null):($field==='gender'?($gender!==''?$gender:null):($field==='registration_date'?($registrationDate!==''?$registrationDate:null):($field==='photo_url'?$photoPath:($data[$field]??null))));}
            if($sets){$pdo->prepare('UPDATE students SET '.implode(',',$sets).' WHERE id=:id')->execute($params);}
            $pdo->commit();
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
        $_SESSION['portal_user']['name']=$fullName;$_SESSION['portal_user']['email']=$email;
        $_SESSION['portal_flash']=['type'=>'success','message'=>'Data pribadi berhasil diperbarui.'];redirectTo('profile');
    }
    if ($action==='logout') {
        $_SESSION=[];session_regenerate_id(true);$_SESSION['portal_flash']=['type'=>'success','message'=>'Anda telah keluar.'];redirectTo('login');
    }
}
$user=portalUser();
if ($page!=='login' && !$user) redirectTo('login');
if ($page==='login' && $user) redirectTo('dashboard');
if ($page==='profile' && $user && $user['role']!=='mahasiswa') redirectTo('dashboard');
if ($user && !in_array($user['role'],['mahasiswa','dosen'],true)) { $_SESSION=[]; redirectTo('login'); }

$profile=null;$stats=[];$rows=[];$roleLabel=$user ? ($user['role']==='mahasiswa'?'Mahasiswa':'Dosen') : '';
if ($user) {
    if ($user['role']==='mahasiswa') {
        $studentColumns=[];
        foreach($pdo->query('SHOW COLUMNS FROM students')->fetchAll(PDO::FETCH_COLUMN) as $columnName) $studentColumns[$columnName]=true;
        $optionalStudentFields=['phone','address','national_id','birth_place','birth_date','gender','religion','marital_status','province','city','district','village','postal_code','father_name','mother_name','guardian_name','guardian_phone','emergency_contact_name','emergency_contact_phone','admission_pathway','registration_date','photo_url'];
        $studentSelect=['s.id','s.nim','s.entry_year','s.status AS student_status'];
        foreach($optionalStudentFields as $field) $studentSelect[] = isset($studentColumns[$field]) ? 's.'.$field : 'NULL AS '.$field;
        $profileSql='SELECT '.implode(',',$studentSelect).',u.full_name,u.email,p.name AS program,p.degree_level FROM students s JOIN users u ON u.id=s.user_id LEFT JOIN study_programs p ON p.id=s.study_program_id WHERE s.user_id=:uid LIMIT 1';
        $profile=queryOne($pdo,$profileSql,['uid'=>$user['id']]);
        if (!$profile) { http_response_code(403); exit('Profil mahasiswa belum terhubung. Hubungi Admin Akademik.'); }
        $sid=(int)$profile['id'];
        $stats=[
            ['label'=>'Kelas KRS disetujui','value'=>(int)$pdo->query("SELECT COUNT(*) FROM study_plans sp JOIN study_plan_details d ON d.study_plan_id=sp.id WHERE sp.student_id=".$sid." AND sp.status='approved'")->fetchColumn()],
            ['label'=>'Nilai terbit','value'=>(int)$pdo->query("SELECT COUNT(*) FROM grades WHERE student_id=".$sid." AND published_at IS NOT NULL")->fetchColumn()],
            ['label'=>'Tagihan belum lunas','value'=>(int)$pdo->query("SELECT COUNT(*) FROM tuition_bills WHERE student_id=".$sid." AND status IN ('unpaid','partial')")->fetchColumn()],
        ];
        $rows=queryAll($pdo,"SELECT y.name AS academic_year,se.name AS semester,sp.status,c.code,c.name AS course,cc.class_name,c.credits,u.full_name AS lecturer FROM study_plans sp JOIN semesters se ON se.id=sp.semester_id JOIN academic_years y ON y.id=se.academic_year_id JOIN study_plan_details d ON d.study_plan_id=sp.id JOIN course_classes cc ON cc.id=d.course_class_id JOIN courses c ON c.id=cc.course_id LEFT JOIN lecturers l ON l.id=cc.lecturer_id LEFT JOIN users u ON u.id=l.user_id WHERE sp.student_id=:sid ORDER BY y.name DESC,CAST(SUBSTRING_INDEX(se.name,' ',-1) AS UNSIGNED),c.name LIMIT 100",['sid'=>$sid]);
        $grades=queryAll($pdo,"SELECT c.code,c.name AS course,c.credits,g.numeric_score,g.letter_grade,g.grade_points,se.name AS semester,y.name AS academic_year FROM grades g JOIN course_classes cc ON cc.id=g.course_class_id JOIN courses c ON c.id=cc.course_id JOIN semesters se ON se.id=cc.semester_id JOIN academic_years y ON y.id=se.academic_year_id WHERE g.student_id=:sid AND g.published_at IS NOT NULL ORDER BY y.name DESC,CAST(SUBSTRING_INDEX(se.name,' ',-1) AS UNSIGNED),c.name LIMIT 100",['sid'=>$sid]);
        $bills=queryAll($pdo,"SELECT bill_code,description,amount,due_date,status FROM tuition_bills WHERE student_id=:sid ORDER BY due_date DESC,bill_code DESC LIMIT 50",['sid'=>$sid]);
        $attendance=queryAll($pdo,"SELECT c.name AS course,COUNT(*) AS total,SUM(a.attendance_status='present') AS present,SUM(a.attendance_status='sick') AS sick,SUM(a.attendance_status='permission') AS permission,SUM(a.attendance_status='absent') AS absent FROM attendance a JOIN course_classes cc ON cc.id=a.course_class_id JOIN courses c ON c.id=cc.course_id WHERE a.student_id=:sid GROUP BY c.id,c.name ORDER BY c.name",['sid'=>$sid]);
    } else {
        $profile=queryOne($pdo,'SELECT id,lecturer_number,phone FROM lecturers WHERE user_id=:uid LIMIT 1',['uid'=>$user['id']]);
        if (!$profile) { http_response_code(403); exit('Profil dosen belum terhubung. Hubungi Admin Akademik.'); }
        $lid=(int)$profile['id'];
        $stats=[
            ['label'=>'Kelas diampu','value'=>(int)$pdo->query("SELECT COUNT(*) FROM course_classes WHERE lecturer_id=".$lid)->fetchColumn()],
            ['label'=>'Mahasiswa pada kelas','value'=>(int)$pdo->query("SELECT COUNT(DISTINCT d.study_plan_id) FROM course_classes cc JOIN study_plan_details d ON d.course_class_id=cc.id JOIN study_plans sp ON sp.id=d.study_plan_id WHERE cc.lecturer_id=".$lid." AND sp.status='approved'")->fetchColumn()],
        ];
        $rows=queryAll($pdo,"SELECT cc.id,cc.class_name,c.code,c.name AS course,c.credits,se.name AS semester,y.name AS academic_year,COUNT(DISTINCT CASE WHEN sp.status='approved' THEN sp.student_id END) AS students FROM course_classes cc JOIN courses c ON c.id=cc.course_id JOIN semesters se ON se.id=cc.semester_id JOIN academic_years y ON y.id=se.academic_year_id LEFT JOIN study_plan_details d ON d.course_class_id=cc.id LEFT JOIN study_plans sp ON sp.id=d.study_plan_id WHERE cc.lecturer_id=:lid GROUP BY cc.id,cc.class_name,c.code,c.name,c.credits,se.name,y.name ORDER BY y.name DESC,se.name,c.name LIMIT 100",['lid'=>$lid]);
        $roster=queryAll($pdo,"SELECT cc.class_name,c.name AS course,s.nim,u.full_name AS student,sp.status,g.letter_grade,g.numeric_score FROM course_classes cc JOIN courses c ON c.id=cc.course_id JOIN study_plan_details d ON d.course_class_id=cc.id JOIN study_plans sp ON sp.id=d.study_plan_id JOIN students s ON s.id=sp.student_id JOIN users u ON u.id=s.user_id LEFT JOIN grades g ON g.student_id=s.id AND g.course_class_id=cc.id WHERE cc.lecturer_id=:lid AND sp.status='approved' ORDER BY c.name,u.full_name LIMIT 250",['lid'=>$lid]);
        $attendance=queryAll($pdo,"SELECT c.name AS course,a.meeting_number,a.attendance_status,COUNT(*) AS total FROM attendance a JOIN course_classes cc ON cc.id=a.course_class_id JOIN courses c ON c.id=cc.course_id WHERE cc.lecturer_id=:lid GROUP BY c.name,a.meeting_number,a.attendance_status ORDER BY c.name,a.meeting_number DESC LIMIT 100",['lid'=>$lid]);
    }
}
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#176b45"><title><?= $user ? h($roleLabel.' - STIT Darul Ilmi') : 'Login Portal Akademik - STIT Darul Ilmi' ?></title><link rel="icon" href="<?=h($campusLogoUrl)?>">
<style>
:root{--green:#176b45;--dark:#12432f;--mint:#eaf5ee;--line:#dce9df;--ink:#20372a;--muted:#6c7e72}*{box-sizing:border-box}body{margin:0;background:#f4f8f5;color:var(--ink);font:15px Inter,system-ui,Arial,sans-serif}a{color:var(--green)}.shell{max-width:1180px;margin:auto;padding:26px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;background:white;border:1px solid var(--line);border-radius:18px;padding:18px 22px;margin-bottom:22px}.brand{display:flex;align-items:center;gap:11px;font-weight:850;color:var(--dark);letter-spacing:.02em}.brand img{width:44px;height:44px;object-fit:contain;flex:0 0 44px}.brand-copy small{display:block;color:var(--muted);font-weight:500;margin-top:3px}.user{display:flex;align-items:center;gap:14px}.logout{border:0;background:var(--green);color:white;padding:10px 17px;border-radius:22px;font-weight:700;cursor:pointer}.hero{background:linear-gradient(120deg,#12432f,#218454);color:white;border-radius:22px;padding:28px;margin-bottom:20px}.hero h1{margin:0 0 7px;font-size:clamp(24px,4vw,34px)}.hero p{margin:0;color:#e1f1e6}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;margin:18px 0}.stat,.panel{background:white;border:1px solid var(--line);border-radius:16px;padding:19px}.stat strong{display:block;font-size:30px;color:var(--green);margin-top:7px}.muted{color:var(--muted)}.panel{margin:16px 0;overflow:hidden}.panel h2{font-size:18px;margin:0 0 14px}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;min-width:620px}th,td{text-align:left;padding:11px 12px;border-bottom:1px solid #edf2ee;font-size:13px}th{background:#f5faf6;color:#486452}.pill{display:inline-block;border-radius:20px;padding:4px 9px;background:var(--mint);color:var(--green);font-size:12px;font-weight:700}.login-wrap{min-height:100vh;display:grid;place-items:center;padding:22px;background:radial-gradient(circle at 10% 10%,#b7e2c4,transparent 32%),linear-gradient(135deg,#eaf5ee,#f8fbf8)}.login{width:min(460px,100%);background:white;border:1px solid var(--line);border-radius:24px;padding:34px;box-shadow:0 22px 65px #174b3020}.logo-wrap{display:flex;justify-content:center;align-items:center;margin:0 auto 22px}.campus-logo{display:block;width:142px;height:142px;max-width:48%;object-fit:contain}.login h1{color:var(--dark);margin:22px 0 8px;font-size:27px}.login label{display:block;margin:17px 0 7px;font-weight:700}.login input{width:100%;padding:13px;border:1px solid #cfded3;border-radius:11px;font:inherit}.login button{width:100%;padding:14px;margin-top:22px;border:0;border-radius:24px;background:var(--green);color:white;font-weight:800;font-size:15px}.alert{padding:12px;border-radius:10px;background:#fff0ee;color:#a32118;margin:14px 0}.foot{font-size:12px;color:var(--muted);margin-top:22px;border-top:1px solid var(--line);padding-top:15px}@media(max-width:600px){.shell{padding:14px}.top{align-items:flex-start;padding:15px}.user{align-items:flex-end;flex-direction:column}.hero{padding:22px}}
@media(max-width:760px){
 body{padding-bottom:calc(78px + env(safe-area-inset-bottom))}
 .shell{padding:12px 12px 28px}
 .top{border-radius:15px;padding:12px;gap:8px;margin-bottom:14px;align-items:center}
 .brand{font-size:12px;gap:8px}
 .brand img{width:38px;height:38px;flex-basis:38px}
 .brand-copy small{font-size:10px}
 .user{gap:8px;align-items:flex-end;flex-direction:column;font-size:12px}
 .logout{padding:8px 12px;font-size:12px}
 .hero{border-radius:17px;padding:20px;margin-bottom:14px}
 .hero h1{font-size:23px}
 .grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px;margin:12px 0}
 .stat,.panel{padding:14px;border-radius:14px}
 .stat strong{font-size:25px}
 .panel{margin:12px 0;scroll-margin-top:72px}
 .panel h2{font-size:16px}
 .table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
 table{min-width:620px}
 .mobile-bottom-nav{position:fixed;z-index:1000;left:0;right:0;bottom:0;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:2px;padding:7px 6px calc(7px + env(safe-area-inset-bottom));background:rgba(255,255,255,.97);border-top:1px solid var(--line);box-shadow:0 -8px 24px rgba(14,55,32,.10);backdrop-filter:blur(14px)}
 .mobile-bottom-nav a{display:flex;min-width:0;min-height:54px;align-items:center;justify-content:center;flex-direction:column;gap:3px;padding:4px 2px;border-radius:12px;color:var(--muted);text-decoration:none;font-size:10px;font-weight:750;white-space:nowrap}
 .mobile-bottom-nav a span:first-child{font-size:20px;line-height:1.1}
 .mobile-bottom-nav a:active,.mobile-bottom-nav a:focus{background:var(--mint);color:var(--green)}
}
.student-identity{display:flex;align-items:center;gap:16px;background:#fff;border:1px solid var(--line);border-radius:16px;padding:15px 18px;margin:0 0 18px}.student-identity img{width:68px;height:68px;border-radius:50%;object-fit:cover;border:3px solid var(--mint);background:var(--mint)}.student-identity strong,.student-identity span{display:block}.student-identity span{font-size:13px;color:var(--muted);margin:4px 0}.student-identity a{font-size:13px;font-weight:750;text-decoration:none}.profile-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.profile-section-title{font-weight:850;color:var(--dark);border-bottom:1px solid var(--line);padding:15px 0 8px;margin-top:8px}.profile-tabs{display:flex;gap:8px;overflow-x:auto;padding:4px 2px 12px;margin:8px 0 12px;border-bottom:1px solid var(--line);scrollbar-width:thin;-webkit-overflow-scrolling:touch}.profile-tab{flex:0 0 auto;border:1px solid var(--line);background:#f7fbf8;color:var(--muted);border-radius:22px;padding:10px 15px;font:700 13px inherit;cursor:pointer;white-space:nowrap}.profile-tab.active{background:var(--green);border-color:var(--green);color:#fff;box-shadow:0 4px 12px #176b4525}.profile-tab-panel{display:none;min-height:230px;animation:profileFade .18s ease}.profile-tab-panel.active{display:block}@keyframes profileFade{from{opacity:.5;transform:translateY(3px)}to{opacity:1;transform:translateY(0)}}.profile-edit select{display:block;width:100%;margin-top:7px;padding:12px;border:1px solid #cfded3;border-radius:10px;font:inherit;background:white;color:var(--ink)}.full-field{grid-column:1/-1}.profile-edit label{display:block;font-weight:700;font-size:13px;color:var(--dark);margin:12px 0}.profile-edit input,.profile-edit textarea{display:block;width:100%;margin-top:7px;padding:12px;border:1px solid #cfded3;border-radius:10px;font:inherit;color:var(--ink);background:#fff}.photo-upload input{border-style:dashed}.profile-actions{display:flex;align-items:center;gap:16px;margin-top:16px}.save-profile{border:0;border-radius:22px;padding:12px 20px;background:var(--green);color:#fff;font-weight:800;cursor:pointer}.profile-actions a{font-size:13px;font-weight:700;text-decoration:none}@media(max-width:760px){.student-identity{padding:12px;gap:12px}.student-identity img{width:58px;height:58px}.profile-form-grid{grid-template-columns:1fr}.profile-actions{align-items:stretch;flex-direction:column}.mobile-bottom-nav a{font-size:9px}}@media(min-width:761px){.mobile-bottom-nav{display:none}}
</style></head><body>
<?php if (!$user): ?>
<div class="login-wrap"><main class="login"><div class="logo-wrap"><img class="campus-logo" src="<?=h($campusLogoUrl)?>" alt="Logo STIT Darul Ilmi Tasikmalaya"></div><p class="muted" style="font-size:11px;font-weight:800;letter-spacing:.14em;margin:20px 0 0">PORTAL AKADEMIK TERPADU</p><h1>Login Mahasiswa & Dosen</h1><p class="muted">Gunakan akun akademik yang telah diberikan oleh Admin STIT Darul Ilmi Tasikmalaya.</p><?php if($flash):?><div class="alert"><?=h($flash['message'])?></div><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrfToken())?>"><input type="hidden" name="action" value="login"><label>Username</label><input name="username" autocomplete="username" required maxlength="100" placeholder="Masukkan username"><label>Password</label><input type="password" name="password" autocomplete="current-password" required maxlength="200" placeholder="Masukkan password"><button type="submit">Masuk ke Portal →</button></form><div class="foot">Khusus akun mahasiswa dan dosen. Admin menggunakan halaman Admin Web.</div><p class="foot"><a href="../../website/">Kembali ke Website Kampus</a></p></main></div>
<?php else: ?>
<div class="shell"><header class="top"><div class="brand"><img src="<?=h($campusLogoUrl)?>" alt="Logo STIT Darul Ilmi"><span class="brand-copy">STIT DARUL ILMI<small>Portal Akademik · <?=h($roleLabel)?></small></span></div><div class="user"><div><strong><?=h($user['name'])?></strong><div class="muted" style="font-size:12px"><?=h($user['username'])?></div></div><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrfToken())?>"><input type="hidden" name="action" value="logout"><button class="logout">Keluar</button></form></div></header>
<section class="hero" id="dashboard"><h1>Selamat datang, <?=h($user['name'])?></h1><p><?= $user['role']==='mahasiswa' ? 'Pantau aktivitas akademik, KRS, hasil studi, presensi, dan informasi tagihan Anda.' : 'Pantau kelas yang Anda ampu, peserta kelas, dan ringkasan presensi akademik.' ?></p></section>
<?php if($user['role']==='mahasiswa'): ?><section class="student-identity"><img src="<?=h($profile['photo_url']?:'assets/profile-placeholder.svg')?>" alt="Foto profil mahasiswa" onerror="this.src='assets/profile-placeholder.svg'"><div><strong><?=h($user['name'])?></strong><span><?=h($profile['nim'])?> · <?=h($profile['program']??'Mahasiswa')?></span><a href="?page=profile">Kelola data pribadi →</a></div></section><?php endif;?>
<?php if($user['role']==='mahasiswa' && $page==='profile'): ?><section class="panel profile-edit" id="data-pribadi"><h2>Edit Data Pribadi</h2><p class="muted">Perbarui informasi kontak dan foto profil. Data akademik seperti NIM dan program studi dikelola oleh Admin Akademik.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="_csrf" value="<?=h(csrfToken())?>"><input type="hidden" name="action" value="update_student_profile"><div class="profile-tabs" role="tablist" aria-label="Bagian data pribadi">
<button type="button" class="profile-tab active" role="tab" aria-selected="true" data-tab="akun">Akun & Akademik</button>
<button type="button" class="profile-tab" role="tab" aria-selected="false" data-tab="identitas">Identitas</button>
<button type="button" class="profile-tab" role="tab" aria-selected="false" data-tab="alamat">Alamat & Kontak</button>
<button type="button" class="profile-tab" role="tab" aria-selected="false" data-tab="wali">Orang Tua / Wali</button>
<button type="button" class="profile-tab" role="tab" aria-selected="false" data-tab="darurat">Darurat & Registrasi</button>
<button type="button" class="profile-tab" role="tab" aria-selected="false" data-tab="foto">Foto Profil</button>
</div><section class="profile-tab-panel" data-tab-panel="akun"><div class="profile-section-title">Informasi Akun & Akademik</div><div class="profile-form-grid"><label>Nama lengkap<input name="full_name" maxlength="160" required value="<?=h($profile['full_name'])?>"></label><label>Email<input type="email" name="email" maxlength="190" value="<?=h($profile['email']??'')?>"></label><label>NIM<input readonly value="<?=h($profile['nim'])?>"></label><label>Program studi<input readonly value="<?=h($profile['program']??'-')?>"></label><label>Tahun masuk<input readonly value="<?=h($profile['entry_year']??'')?>"></label><label>Status mahasiswa<input readonly value="<?=h($profile['student_status'])?>"></label></div></section><section class="profile-tab-panel" data-tab-panel="identitas"><div class="profile-section-title">Identitas Pribadi</div><div class="profile-form-grid"><label>NIK<input name="national_id" maxlength="32" value="<?=h($profile['national_id']??'')?>"></label><label>Tempat lahir<input name="birth_place" maxlength="100" value="<?=h($profile['birth_place']??'')?>"></label><label>Tanggal lahir<input type="date" name="birth_date" value="<?=h($profile['birth_date']??'')?>"></label><label>Jenis kelamin<select name="gender"><option value="">Pilih</option><option value="male" <?=$profile['gender']==='male'?'selected':''?>>Laki-laki</option><option value="female" <?=$profile['gender']==='female'?'selected':''?>>Perempuan</option></select></label><label>Agama<input name="religion" maxlength="40" value="<?=h($profile['religion']??'')?>"></label><label>Status perkawinan<input name="marital_status" maxlength="30" value="<?=h($profile['marital_status']??'')?>"></label></div></section><section class="profile-tab-panel" data-tab-panel="alamat"><div class="profile-section-title">Alamat & Kontak</div><div class="profile-form-grid"><label>Nomor telepon<input name="phone" maxlength="30" value="<?=h($profile['phone']??'')?>"></label><label>Provinsi<input name="province" maxlength="100" value="<?=h($profile['province']??'')?>"></label><label>Kabupaten / Kota<input name="city" maxlength="100" value="<?=h($profile['city']??'')?>"></label><label>Kecamatan<input name="district" maxlength="100" value="<?=h($profile['district']??'')?>"></label><label>Desa / Kelurahan<input name="village" maxlength="100" value="<?=h($profile['village']??'')?>"></label><label>Kode pos<input name="postal_code" maxlength="10" value="<?=h($profile['postal_code']??'')?>"></label><label class="full-field">Alamat lengkap<textarea name="address" rows="3"><?=h($profile['address']??'')?></textarea></label></div></section><section class="profile-tab-panel" data-tab-panel="wali"><div class="profile-section-title">Data Orang Tua / Wali</div><div class="profile-form-grid"><label>Nama ayah<input name="father_name" maxlength="160" value="<?=h($profile['father_name']??'')?>"></label><label>Nama ibu<input name="mother_name" maxlength="160" value="<?=h($profile['mother_name']??'')?>"></label><label>Nama wali<input name="guardian_name" maxlength="160" value="<?=h($profile['guardian_name']??'')?>"></label><label>Telepon wali<input name="guardian_phone" maxlength="30" value="<?=h($profile['guardian_phone']??'')?>"></label></div></section><section class="profile-tab-panel" data-tab-panel="darurat"><div class="profile-section-title">Kontak Darurat & Registrasi</div><div class="profile-form-grid"><label>Nama kontak darurat<input name="emergency_contact_name" maxlength="160" value="<?=h($profile['emergency_contact_name']??'')?>"></label><label>Telepon kontak darurat<input name="emergency_contact_phone" maxlength="30" value="<?=h($profile['emergency_contact_phone']??'')?>"></label><label>Jalur masuk<input name="admission_pathway" maxlength="60" value="<?=h($profile['admission_pathway']??'')?>"></label><label>Tanggal registrasi<input type="date" name="registration_date" value="<?=h($profile['registration_date']??'')?>"></label></div></section><section class="profile-tab-panel" data-tab-panel="foto"><div class="profile-section-title">Foto Profil</div><label class="photo-upload">Foto profil (JPG, PNG, WEBP · Maks. 3 MB)<input type="file" name="profile_photo" accept="image/jpeg,image/png,image/webp"></label></section><div class="profile-actions"><button class="save-profile" type="submit">Simpan Perubahan</button><a href="?page=dashboard">Kembali ke Dashboard</a></div></form></section><?php endif;?>
<?php if($profile && $page!=='profile'):?><section class="panel" id="profil"><h2>Profil Akademik</h2><div class="grid" style="margin:0"><?php if($user['role']==='mahasiswa'):?><div><span class="muted">NIM</span><br><strong><?=h($profile['nim'])?></strong></div><div><span class="muted">Program Studi</span><br><strong><?=h($profile['program']??'-')?></strong></div><div><span class="muted">Tahun Masuk</span><br><strong><?=h($profile['entry_year']??'-')?></strong></div><div><span class="muted">Status</span><br><span class="pill"><?=h($profile['student_status'])?></span></div><?php else:?><div><span class="muted">Nomor Induk Dosen</span><br><strong><?=h($profile['lecturer_number']??'-')?></strong></div><div><span class="muted">Telepon</span><br><strong><?=h($profile['phone']??'-')?></strong></div><?php endif;?></div></section><?php endif;?>
<?php if($page!=='profile'): ?>
<div class="grid"><?php foreach($stats as $stat):?><article class="stat"><span class="muted"><?=h($stat['label'])?></span><strong><?=number_format((int)$stat['value'])?></strong></article><?php endforeach;?></div>
<?php if($user['role']==='mahasiswa'):?>
<section class="panel" id="krs"><h2>Rencana Studi (KRS)</h2><div class="table-wrap"><table><thead><tr><th>Tahun / Semester</th><th>Kode</th><th>Mata Kuliah</th><th>Kelas</th><th>SKS</th><th>Dosen</th><th>Status KRS</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=h($r['academic_year'].' / '.$r['semester'])?></td><td><?=h($r['code'])?></td><td><?=h($r['course'])?></td><td><?=h($r['class_name'])?></td><td><?=h($r['credits'])?></td><td><?=h($r['lecturer']??'-')?></td><td><span class="pill"><?=h($r['status'])?></span></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="7" class="muted">Belum ada data KRS.</td></tr><?php endif;?></tbody></table></div></section>
<section class="panel" id="nilai"><h2>Nilai / KHS (nilai yang telah diterbitkan)</h2><div class="table-wrap"><table><thead><tr><th>Tahun / Semester</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Nilai</th><th>Huruf</th><th>Bobot</th></tr></thead><tbody><?php foreach($grades as $r):?><tr><td><?=h($r['academic_year'].' / '.$r['semester'])?></td><td><?=h($r['code'])?></td><td><?=h($r['course'])?></td><td><?=h($r['credits'])?></td><td><?=h($r['numeric_score']??'-')?></td><td><?=h($r['letter_grade']??'-')?></td><td><?=h($r['grade_points']??'-')?></td></tr><?php endforeach;?><?php if(!$grades):?><tr><td colspan="7" class="muted">Belum ada nilai yang diterbitkan.</td></tr><?php endif;?></tbody></table></div></section>
<section class="panel" id="presensi"><h2>Presensi</h2><div class="table-wrap"><table><thead><tr><th>Mata Kuliah</th><th>Total Pertemuan</th><th>Hadir</th><th>Sakit</th><th>Izin</th><th>Alpa</th></tr></thead><tbody><?php foreach($attendance as $r):?><tr><td><?=h($r['course'])?></td><td><?=h($r['total'])?></td><td><?=h($r['present'])?></td><td><?=h($r['sick'])?></td><td><?=h($r['permission'])?></td><td><?=h($r['absent'])?></td></tr><?php endforeach;?><?php if(!$attendance):?><tr><td colspan="6" class="muted">Belum ada data presensi.</td></tr><?php endif;?></tbody></table></div></section>
<section class="panel" id="tagihan"><h2>Tagihan Pendidikan</h2><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Deskripsi</th><th>Jumlah</th><th>Jatuh Tempo</th><th>Status</th></tr></thead><tbody><?php foreach($bills as $r):?><tr><td><?=h($r['bill_code'])?></td><td><?=h($r['description'])?></td><td>Rp <?=number_format((float)$r['amount'],0,',','.')?></td><td><?=h($r['due_date']??'-')?></td><td><span class="pill"><?=h($r['status'])?></span></td></tr><?php endforeach;?><?php if(!$bills):?><tr><td colspan="5" class="muted">Belum ada data tagihan.</td></tr><?php endif;?></tbody></table></div></section>
<?php else:?>
<section class="panel" id="kelas"><h2>Kelas yang Diampu</h2><div class="table-wrap"><table><thead><tr><th>Tahun / Semester</th><th>Kode</th><th>Mata Kuliah</th><th>Kelas</th><th>SKS</th><th>Jumlah Mahasiswa</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=h($r['academic_year'].' / '.$r['semester'])?></td><td><?=h($r['code'])?></td><td><?=h($r['course'])?></td><td><?=h($r['class_name'])?></td><td><?=h($r['credits'])?></td><td><?=h($r['students'])?></td></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="6" class="muted">Belum ada kelas yang ditugaskan kepada Anda.</td></tr><?php endif;?></tbody></table></div></section>
<section class="panel" id="mahasiswa"><h2>Daftar Mahasiswa pada Kelas Anda</h2><div class="table-wrap"><table><thead><tr><th>Kelas</th><th>Mata Kuliah</th><th>NIM</th><th>Nama Mahasiswa</th><th>Status KRS</th><th>Nilai</th></tr></thead><tbody><?php foreach($roster as $r):?><tr><td><?=h($r['class_name'])?></td><td><?=h($r['course'])?></td><td><?=h($r['nim'])?></td><td><?=h($r['student'])?></td><td><span class="pill"><?=h($r['status'])?></span></td><td><?=h($r['letter_grade']??'Belum ada')?></td></tr><?php endforeach;?><?php if(!$roster):?><tr><td colspan="6" class="muted">Belum ada mahasiswa pada kelas Anda.</td></tr><?php endif;?></tbody></table></div></section>
<section class="panel" id="presensi"><h2>Ringkasan Presensi Kelas</h2><div class="table-wrap"><table><thead><tr><th>Mata Kuliah</th><th>Pertemuan</th><th>Status</th><th>Jumlah</th></tr></thead><tbody><?php foreach($attendance as $r):?><tr><td><?=h($r['course'])?></td><td><?=h($r['meeting_number'])?></td><td><?=h($r['attendance_status'])?></td><td><?=h($r['total'])?></td></tr><?php endforeach;?><?php if(!$attendance):?><tr><td colspan="4" class="muted">Belum ada data presensi.</td></tr><?php endif;?></tbody></table></div></section>
<?php endif;?>
<?php endif; ?><p class="muted" style="font-size:12px;text-align:center;padding:18px">STIT Darul Ilmi Tasikmalaya · Portal Akademik · Hubungi Admin Akademik jika data Anda tidak sesuai.</p></div>
<nav class="mobile-bottom-nav" aria-label="Navigasi portal akademik">
<a href="#dashboard"><span aria-hidden="true">⌂</span><span>Beranda</span></a>
<?php if($user['role']==='mahasiswa'): ?>
<a href="#krs"><span aria-hidden="true">▤</span><span>KRS</span></a>
<a href="#nilai"><span aria-hidden="true">▧</span><span>Nilai</span></a>
<a href="#presensi"><span aria-hidden="true">◷</span><span>Presensi</span></a>
<?php else: ?>
<a href="#kelas"><span aria-hidden="true">▤</span><span>Kelas</span></a>
<a href="#mahasiswa"><span aria-hidden="true">♙</span><span>Mahasiswa</span></a>
<a href="#presensi"><span aria-hidden="true">◷</span><span>Presensi</span></a>
<?php endif; ?>
<?php if($user['role']==='mahasiswa'): ?><a href="?page=profile"><span aria-hidden="true">◎</span><span>Akun</span></a><?php else: ?><a href="#profil"><span aria-hidden="true">◎</span><span>Profil</span></a><?php endif;?>
</nav>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const tabs=[...document.querySelectorAll('.profile-tab')];
 const panels=[...document.querySelectorAll('.profile-tab-panel')];
 if(!tabs.length||!panels.length)return;
 function activate(id){
  tabs.forEach(tab=>{const active=tab.dataset.tab===id;tab.classList.toggle('active',active);tab.setAttribute('aria-selected',active?'true':'false');});
  panels.forEach(panel=>panel.classList.toggle('active',panel.dataset.tabPanel===id));
 }
 tabs.forEach(tab=>tab.addEventListener('click',()=>activate(tab.dataset.tab)));
 activate('akun');
});
</script>
<?php endif;?></body></html>
