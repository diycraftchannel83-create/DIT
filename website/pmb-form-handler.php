<?php
declare(strict_types=1);
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
// Support both deployment layouts:
// 1) repository root with website/ as document root
// 2) website files copied directly into public_html alongside backend/
$cfgCandidates=[
 dirname(__DIR__).'/backend/config/database.local.php',
 __DIR__.'/backend/config/database.local.php',
 dirname(__DIR__,2).'/backend/config/database.local.php'
];
$cfgPath='';
foreach($cfgCandidates as $candidate){if(is_file($candidate)){$cfgPath=$candidate;break;}}
$pmbUnavailable=static function(string $message):never{
 http_response_code(503);
 $safe=htmlspecialchars($message,ENT_QUOTES,'UTF-8');
 echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>PMB belum tersedia</title><style>body{margin:0;background:#f3f8f4;font:15px system-ui;color:#173b2b;display:grid;min-height:100vh;place-items:center;padding:20px;box-sizing:border-box}.panel{max-width:620px;background:#fff;border:1px solid #dcebe0;border-radius:20px;padding:28px;box-shadow:0 12px 36px #123a2412}h1{font-size:22px;color:#075b3d;margin:0 0 10px}.note{background:#fff8e8;border:1px solid #f1dfb7;border-radius:12px;padding:14px;line-height:1.6}</style></head><body><main class="panel"><h1>Pendaftaran Mahasiswa Baru</h1><p>STIT Darul Ilmi Tasikmalaya</p><div class="note">'.$safe.'</div><p>Silakan hubungi administrator SIAKAD untuk mengaktifkan layanan pendaftaran.</p></main></body></html>';
 exit;
};
if($cfgPath==='')$pmbUnavailable('Konfigurasi database belum ditemukan. Pastikan file backend/config/database.local.php sudah dibuat di folder backend yang sejajar dengan folder website atau public_html.');
$cfg=require $cfgPath;
try{$pdo=new PDO(sprintf('mysql:host=%s;dbname=%s;charset=%s',$cfg['host'],$cfg['database'],$cfg['charset']??'utf8mb4'),$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}catch(Throwable $e){error_log('[STIT PMB DB] '.$e->getMessage());$pmbUnavailable('Koneksi database pendaftaran belum berhasil. Periksa host, nama database, username, dan password pada backend/config/database.local.php.');}
$pmbPrograms=$pdo->query('SELECT id,name FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();
if(empty($_SESSION['pmb_csrf']))$_SESSION['pmb_csrf']=bin2hex(random_bytes(32));
$pmbCsrf=$_SESSION['pmb_csrf'];$pmbMessage='';$pmbRegistration='';
$iniBytes=static function(string $value):int{
 $value=trim($value);if($value==='')return 0;
 $unit=strtolower(substr($value,-1));$number=(float)$value;
 return (int)($number*match($unit){'g'=>1073741824,'m'=>1048576,'k'=>1024,default=>1});
};
if($_SERVER['REQUEST_METHOD']==='POST'){
 $contentLength=(int)($_SERVER['CONTENT_LENGTH']??0);
 $postLimit=$iniBytes((string)ini_get('post_max_size'));
 if($postLimit>0&&$contentLength>$postLimit){
  $pmbMessage='Total ukuran seluruh data dan dokumen melebihi batas server ('.ini_get('post_max_size').'). Kecilkan ukuran berkas atau minta admin hosting menaikkan post_max_size.';
 }elseif(!hash_equals($pmbCsrf,(string)($_POST['csrf']??'')))$pmbMessage='Sesi formulir kedaluwarsa atau data unggahan melebihi batas server. Muat ulang halaman dan periksa ukuran semua dokumen.';
 else{
  $f=[];$keys=['full_name','national_id','email','phone','whatsapp','birth_place','religion','marital_status','postal_code','birth_date','gender','address','province','city','district','village','previous_school','school_major','diploma_number','father_name','father_occupation','father_phone','mother_name','mother_occupation','mother_phone','guardian_name','guardian_phone','emergency_contact_name','emergency_contact_phone','admission_pathway'];
  foreach($keys as $k)$f[$k]=trim((string)($_POST[$k]??''));
  $f['study_program_id']=(int)($_POST['study_program_id']??0);$f['entry_year']=(int)($_POST['entry_year']??date('Y'));$f['graduation_year']=(int)($_POST['graduation_year']??0);
  $required=['full_name','national_id','email','phone','birth_place','birth_date','gender','address','province','city','district','village','previous_school','school_major','father_name','father_phone','mother_name','mother_phone'];
  foreach($required as $k)if($f[$k]===''){$pmbMessage='Lengkapi seluruh data wajib yang bertanda wajib.';break;}
  if(!$pmbMessage&&(!preg_match('/^\\d{16}$/',$f['national_id'])||!filter_var($f['email'],FILTER_VALIDATE_EMAIL)||!in_array($f['gender'],['male','female'],true)))$pmbMessage='Periksa kembali NIK (16 digit), email, dan jenis kelamin.';
  if(!$pmbMessage&&mb_strlen($f['full_name'])<2)$pmbMessage='Nama lengkap minimal 2 karakter.';
  if(!$pmbMessage&&($f['study_program_id']<1||$f['graduation_year']<1980||$f['graduation_year']>(int)date('Y')+1))$pmbMessage='Program studi atau tahun lulus tidak valid.';
  if(!$pmbMessage&&empty($_POST['declaration']))$pmbMessage='Pernyataan kebenaran data wajib dicentang.';

  $files=[];
  foreach(['ktp'=>'Fotokopi KTP','kk'=>'Kartu Keluarga','ijazah'=>'Ijazah SMA/SMK/MA sederajat','pas_foto'=>'Pas foto terbaru ukuran 3x4'] as $key=>$label){

   if($pmbMessage)break;$up=$_FILES[$key]??null;
   if(!$up||($up['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE){$pmbMessage='Dokumen wajib belum lengkap: '.$label.'.';break;}if(($up['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK){
    $uploadError=(int)$up['error'];
    $pmbMessage=match($uploadError){
     UPLOAD_ERR_INI_SIZE=>'Ukuran '.$label.' melewati batas upload_max_filesize server ('.ini_get('upload_max_filesize').').',
     UPLOAD_ERR_FORM_SIZE=>'Ukuran '.$label.' melewati batas formulir.',
     UPLOAD_ERR_PARTIAL=>'Unggahan '.$label.' terputus. Coba unggah ulang.',
     UPLOAD_ERR_NO_TMP_DIR=>'Server tidak memiliki folder sementara untuk unggahan.',
     UPLOAD_ERR_CANT_WRITE=>'Server gagal menulis berkas '.$label.'. Periksa ruang disk dan izin folder.',
     UPLOAD_ERR_EXTENSION=>'Unggahan '.$label.' dihentikan oleh konfigurasi ekstensi PHP.',
     default=>'Unggahan '.$label.' gagal diproses (kode '.$uploadError.').'
    };break;
   }
   if((int)$up['size']<1||(int)$up['size']>5242880){$pmbMessage='Ukuran '.$label.' maksimal 5 MB.';break;}
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($up['tmp_name']);
   $ext=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime]??'';
   if($key==='pas_foto'){
    if(!in_array($mime,['image/jpeg','image/png'],true)){$pmbMessage='Pas foto harus berupa JPG atau PNG, bukan PDF.';break;}
    $dimensions=@getimagesize($up['tmp_name']);
    if(!$dimensions||empty($dimensions[0])||empty($dimensions[1])){$pmbMessage='File pas foto tidak dapat dibaca sebagai gambar.';break;}
   }
   if($ext===''){$pmbMessage='Format '.$label.' harus PDF, JPG, atau PNG.';break;}
   $files[$key]=['tmp'=>$up['tmp_name'],'name'=>substr(basename((string)$up['name']),0,255),'mime'=>$mime,'size'=>(int)$up['size'],'ext'=>$ext];
  }
  if(!$pmbMessage){
   // Simpan berkas pada direktori privat proyek yang ikut ter-deploy, bukan
   // di luar DOCUMENT_ROOT yang umumnya tidak writable pada shared hosting.
   $storageCandidates=[
    dirname(__DIR__).'/backend/storage/pmb-private',
    __DIR__.'/backend/storage/pmb-private',
    (string)($_SERVER['DOCUMENT_ROOT']??'').'/backend/storage/pmb-private'
   ];
   $storage='';
   foreach($storageCandidates as $candidate){
    if($candidate!==''&&(is_dir($candidate)||@mkdir($candidate,0750,true))&&is_writable($candidate)){
     $storage=$candidate;break;
    }
   }
   if($storage===''){
    $pmbMessage='Penyimpanan dokumen belum siap di server. Admin perlu memastikan folder backend/storage/pmb-private tersedia dan memiliki izin tulis.';
    error_log('[STIT PMB STORAGE] Tidak ada direktori penyimpanan privat yang writable. Periksa backend/storage/pmb-private.');
   }else{
    $pmbRegistration='PMB-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));$saved=[];
    try{
     $pdo->beginTransaction();
     $sql="INSERT INTO pmb_applicants(registration_number,full_name,email,phone,whatsapp,study_program_id,status,registration_fee_amount,payment_status,national_id,birth_place,birth_date,gender,religion,marital_status,address,province,city,district,village,postal_code,previous_school,school_major,graduation_year,diploma_number,father_name,father_occupation,father_phone,mother_name,mother_occupation,mother_phone,guardian_name,guardian_phone,emergency_contact_name,emergency_contact_phone,entry_year,admission_pathway,document_status) VALUES(:reg,:name,:email,:phone,:whatsapp,:program,'registered',200000,'unpaid',:nik,:birth_place,:birth_date,:gender,:religion,:marital_status,:address,:province,:city,:district,:village,:postal_code,:school,:major,:grad,:diploma,:father,:father_job,:father_phone,:mother,:mother_job,:mother_phone,:guardian,:guardian_phone,:emergency_name,:emergency_phone,:entry_year,:pathway,'pending')";
     $q=$pdo->prepare($sql);$q->execute(['reg'=>$pmbRegistration,'name'=>$f['full_name'],'email'=>$f['email'],'phone'=>$f['phone'],'whatsapp'=>$f['whatsapp']?:null,'program'=>$f['study_program_id'],'nik'=>$f['national_id']?:'TEST'.strtoupper(bin2hex(random_bytes(6))),'birth_place'=>$f['birth_place']?:'Data Uji','birth_date'=>$f['birth_date']?:'2000-01-01','gender'=>$f['gender']?:'male','religion'=>$f['religion']?:null,'marital_status'=>$f['marital_status']?:null,'address'=>$f['address'],'province'=>$f['province'],'city'=>$f['city'],'district'=>$f['district'],'village'=>$f['village'],'postal_code'=>$f['postal_code']?:null,'school'=>$f['previous_school'],'major'=>$f['school_major'],'grad'=>$f['graduation_year'],'diploma'=>$f['diploma_number']?:null,'father'=>$f['father_name'],'father_job'=>$f['father_occupation']?:null,'father_phone'=>$f['father_phone'],'mother'=>$f['mother_name'],'mother_job'=>$f['mother_occupation']?:null,'mother_phone'=>$f['mother_phone'],'guardian'=>$f['guardian_name']?:null,'guardian_phone'=>$f['guardian_phone']?:null,'emergency_name'=>$f['emergency_contact_name']?:null,'emergency_phone'=>$f['emergency_contact_phone']?:null,'entry_year'=>$f['entry_year'],'pathway'=>$f['admission_pathway']?:'Reguler']);
     $appId=(int)$pdo->lastInsertId();
     foreach($files as $type=>$file){
      $filename=$pmbRegistration.'-'.$type.'-'.bin2hex(random_bytes(5)).'.'.$file['ext'];$dest=$storage.'/'.$filename;
      if(!move_uploaded_file($file['tmp'],$dest))throw new RuntimeException('Gagal menyimpan berkas.');
      $saved[]=$dest;
      $d=$pdo->prepare('INSERT INTO pmb_documents(applicant_id,document_type,original_name,storage_path,mime_type,file_size) VALUES(:app,:type,:name,:path,:mime,:size)');
      $d->execute(['app'=>$appId,'type'=>$type,'name'=>$file['name'],'path'=>$filename,'mime'=>$file['mime'],'size'=>$file['size']]);
     }
     // Catat pengiriman PMB ke audit log pusat (best-effort, tidak menggagalkan pendaftaran).
     try{
      $audit=$pdo->prepare("INSERT INTO audit_logs(user_id,action,entity_type,entity_id,ip_address,user_agent,metadata) VALUES(NULL,'pmb.registration_submitted','pmb_applicant',:entity,:ip,:agent,:metadata)");
      $audit->execute(['entity'=>$appId,'ip'=>substr((string)($_SERVER['REMOTE_ADDR']??''),0,45)?:null,'agent'=>substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,255)?:null,'metadata'=>json_encode(['registration_number'=>$pmbRegistration,'email'=>$f['email']],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)]);
     }catch(Throwable $auditError){error_log('[STIT PMB AUDIT] '.$auditError->getMessage());}
     $pdo->commit();$_SESSION['pmb_confirmation']=['registration'=>$pmbRegistration,'email'=>$f['email'],'created_at'=>date('Y-m-d H:i:s')];$_SESSION['pmb_csrf']=bin2hex(random_bytes(32));$scriptPath=(string)($_SERVER['SCRIPT_NAME']??$_SERVER['PHP_SELF']??'/pmb.php');
     $scriptDir=str_replace('\\\\','/',dirname($scriptPath));
     $basePath=($scriptDir==='/'||$scriptDir==='.'||$scriptDir==='\\\\')?'':rtrim($scriptDir,'/');
     $confirmationPath=$basePath.'/pmb-confirmation.php';
     // Gunakan direktori entry point yang benar; tetap berfungsi jika portal dipasang di subfolder.
     header('Location: '.$confirmationPath,true,303);exit;
    }catch(Throwable $e){
     if($pdo->inTransaction())$pdo->rollBack();
     foreach($saved as $path)if(is_file($path))@unlink($path);
     $errorRef='PMB-ERR-'.strtoupper(bin2hex(random_bytes(3)));
     error_log('[STIT PMB SUBMIT]['.$errorRef.'] '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
     $pmbMessage='Pendaftaran gagal disimpan. Kode pemeriksaan: '.$errorRef.'. Silakan kirim kode ini kepada administrator.';
     $pmbRegistration='';
    }
   }
  }
 }
}

$pmbStatusResult=null;$pmbStatusChecked=false;
if(isset($_GET['registration'])&&isset($_GET['email'])){
 $pmbStatusChecked=true;
 $check=$pdo->prepare('SELECT a.registration_number,a.full_name,a.status,a.document_status,a.payment_status,a.registration_fee_amount,a.payment_note,a.created_at,p.name AS program FROM pmb_applicants a LEFT JOIN study_programs p ON p.id=a.study_program_id WHERE a.registration_number=:reg AND a.email=:email LIMIT 1');
 $check->execute(['reg'=>trim((string)$_GET['registration']),'email'=>trim((string)$_GET['email'])]);
 $pmbStatusResult=$check->fetch()?:false;
}
