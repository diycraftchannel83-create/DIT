<?php
declare(strict_types=1);
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
$cfgPath=dirname(__DIR__).'/backend/config/database.local.php';
if(!is_file($cfgPath)){http_response_code(503);exit('Pendaftaran belum dikonfigurasi.');}
$cfg=require $cfgPath;
$pdo=new PDO(sprintf('mysql:host=%s;dbname=%s;charset=%s',$cfg['host'],$cfg['database'],$cfg['charset']??'utf8mb4'),$cfg['username'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$pmbPrograms=$pdo->query('SELECT id,name FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();
if(empty($_SESSION['pmb_csrf']))$_SESSION['pmb_csrf']=bin2hex(random_bytes(32));
$pmbCsrf=$_SESSION['pmb_csrf'];$pmbMessage='';$pmbRegistration='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!isset($_POST['declaration'])||!hash_equals($pmbCsrf,(string)($_POST['csrf']??'')))$pmbMessage='Pernyataan dan sesi formulir wajib divalidasi.';
 else{
  $f=[];$keys=['full_name','national_id','email','phone','whatsapp','birth_place','religion','marital_status','postal_code','birth_date','gender','address','province','city','district','village','previous_school','school_major','diploma_number','father_name','father_occupation','father_phone','mother_name','mother_occupation','mother_phone','guardian_name','guardian_phone','admission_pathway'];
  foreach($keys as $k)$f[$k]=trim((string)($_POST[$k]??''));
  $f['study_program_id']=(int)($_POST['study_program_id']??0);$f['entry_year']=(int)($_POST['entry_year']??date('Y'));$f['graduation_year']=(int)($_POST['graduation_year']??0);
  $required=['full_name','national_id','email','phone','birth_place','birth_date','gender','address','province','city','district','village','previous_school','father_name','mother_name'];
  foreach($required as $k)if($f[$k]===''){$pmbMessage='Lengkapi seluruh data wajib yang bertanda wajib.';break;}
  if(!$pmbMessage&&(!preg_match('/^\d{16}$/',$f['national_id'])||!filter_var($f['email'],FILTER_VALIDATE_EMAIL)||!in_array($f['gender'],['male','female'],true)))$pmbMessage='Periksa kembali NIK, email, dan jenis kelamin.';
  if(!$pmbMessage&&($f['study_program_id']<1||$f['graduation_year']<1980||$f['graduation_year']>(int)date('Y')+1))$pmbMessage='Program studi atau tahun lulus tidak valid.';
  $files=[];
  foreach(['ktp'=>'Fotokopi KTP','kk'=>'Kartu Keluarga','ijazah'=>'Ijazah SMA/SMK/MA sederajat','pas_foto'=>'Pas foto (opsional)'] as $key=>$label){
   if($pmbMessage)break;$up=$_FILES[$key]??null;
   if(!$up||($up['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE){if($key==='pas_foto')continue;$pmbMessage='Dokumen wajib belum lengkap: '.$label.'.';break;}if(($up['error']??UPLOAD_ERR_OK)!==UPLOAD_ERR_OK){$pmbMessage='Unggahan '.$label.' gagal diproses.';break;}
   if((int)$up['size']<1||(int)$up['size']>5242880){$pmbMessage='Ukuran '.$label.' maksimal 5 MB.';break;}
   $mime=(new finfo(FILEINFO_MIME_TYPE))->file($up['tmp_name']);
   $ext=['application/pdf'=>'pdf','image/jpeg'=>'jpg','image/png'=>'png'][$mime]??'';
   if($ext===''){$pmbMessage='Format '.$label.' harus PDF, JPG, atau PNG.';break;}
   $files[$key]=['tmp'=>$up['tmp_name'],'name'=>basename((string)$up['name']),'mime'=>$mime,'size'=>(int)$up['size'],'ext'=>$ext];
  }
  if(!$pmbMessage){
   $docRoot=(string)($_SERVER['DOCUMENT_ROOT']??dirname(__DIR__));$storage=dirname($docRoot).'/stit-pmb-private';
   if(!is_dir($storage)&&!mkdir($storage,0750,true)&&!is_dir($storage))$pmbMessage='Folder penyimpanan dokumen tidak dapat dibuat.';
   else{
    $pmbRegistration='PMB-'.date('Y').'-'.strtoupper(bin2hex(random_bytes(4)));$saved=[];
    try{
     $pdo->beginTransaction();
     $sql="INSERT INTO pmb_applicants(registration_number,full_name,email,phone,whatsapp,study_program_id,status,national_id,birth_place,birth_date,gender,religion,marital_status,address,province,city,district,village,postal_code,previous_school,school_major,graduation_year,diploma_number,father_name,father_occupation,father_phone,mother_name,mother_occupation,mother_phone,guardian_name,guardian_phone,entry_year,admission_pathway,document_status) VALUES(:reg,:name,:email,:phone,:whatsapp,:program,'registered',:nik,:birth_place,:birth_date,:gender,:religion,:marital_status,:address,:province,:city,:district,:village,:postal_code,:school,:major,:grad,:diploma,:father,:father_job,:father_phone,:mother,:mother_job,:mother_phone,:guardian,:guardian_phone,:entry_year,:pathway,'pending')";
     $q=$pdo->prepare($sql);$q->execute(['reg'=>$pmbRegistration,'name'=>$f['full_name'],'email'=>$f['email'],'phone'=>$f['phone'],'whatsapp'=>$f['whatsapp']?:null,'program'=>$f['study_program_id'],'nik'=>$f['national_id'],'birth_place'=>$f['birth_place'],'birth_date'=>$f['birth_date'],'gender'=>$f['gender'],'religion'=>$f['religion']?:null,'marital_status'=>$f['marital_status']?:null,'address'=>$f['address'],'province'=>$f['province'],'city'=>$f['city'],'district'=>$f['district'],'village'=>$f['village'],'postal_code'=>$f['postal_code']?:null,'school'=>$f['previous_school'],'major'=>$f['school_major']?:null,'grad'=>$f['graduation_year'],'diploma'=>$f['diploma_number']?:null,'father'=>$f['father_name'],'father_job'=>$f['father_occupation']?:null,'father_phone'=>$f['father_phone']?:null,'mother'=>$f['mother_name'],'mother_job'=>$f['mother_occupation']?:null,'mother_phone'=>$f['mother_phone']?:null,'guardian'=>$f['guardian_name']?:null,'guardian_phone'=>$f['guardian_phone']?:null,'entry_year'=>$f['entry_year'],'pathway'=>$f['admission_pathway']?:'Reguler']);
     $appId=(int)$pdo->lastInsertId();
     foreach($files as $type=>$file){
      $filename=$pmbRegistration.'-'.$type.'-'.bin2hex(random_bytes(5)).'.'.$file['ext'];$dest=$storage.'/'.$filename;
      if(!move_uploaded_file($file['tmp'],$dest))throw new RuntimeException('Gagal menyimpan berkas.');
      $saved[]=$dest;
      $d=$pdo->prepare('INSERT INTO pmb_documents(applicant_id,document_type,original_name,storage_path,mime_type,file_size) VALUES(:app,:type,:name,:path,:mime,:size)');
      $d->execute(['app'=>$appId,'type'=>$type,'name'=>$file['name'],'path'=>$filename,'mime'=>$file['mime'],'size'=>$file['size']]);
     }
     $pdo->commit();$pmbMessage='Pendaftaran berhasil dikirim. Simpan nomor registrasi Anda.';$_SESSION['pmb_csrf']=bin2hex(random_bytes(32));$pmbCsrf=$_SESSION['pmb_csrf'];
    }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();foreach($saved as $path)if(is_file($path))@unlink($path);error_log('[STIT PMB SUBMIT] '.$e->getMessage());$pmbMessage='Pendaftaran gagal disimpan. Silakan coba kembali.';$pmbRegistration='';}
   }
  }
 }
}

$pmbStatusResult=null;$pmbStatusChecked=false;
if(isset($_GET['registration'])&&isset($_GET['email'])){
 $pmbStatusChecked=true;
 $check=$pdo->prepare('SELECT a.registration_number,a.full_name,a.status,a.document_status,a.created_at,p.name AS program FROM pmb_applicants a LEFT JOIN study_programs p ON p.id=a.study_program_id WHERE a.registration_number=:reg AND a.email=:email LIMIT 1');
 $check->execute(['reg'=>trim((string)$_GET['registration']),'email'=>trim((string)$_GET['email'])]);
 $pmbStatusResult=$check->fetch()?:false;
}
