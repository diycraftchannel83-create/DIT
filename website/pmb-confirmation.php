<?php
declare(strict_types=1);
require __DIR__.'/pmb-form-handler.php';
$confirmation=$_SESSION['pmb_confirmation']??null;
$pmbCampus=$pdo->query('SELECT logo_image_url FROM campus_profile WHERE id=1')->fetch()?:[];
$pmbLogo=trim((string)($pmbCampus['logo_image_url']??''))?:'assets/stit-darul-ilmi-icon.svg';
if(!is_array($confirmation)||empty($confirmation['registration'])||empty($confirmation['email'])){
 http_response_code(403);
 $confirmation=null;
}else{
 $q=$pdo->prepare("SELECT a.registration_number,a.full_name,a.email,a.phone,a.status,a.document_status,a.payment_status,a.registration_fee_amount,a.created_at,p.name AS program FROM pmb_applicants a LEFT JOIN study_programs p ON p.id=a.study_program_id WHERE a.registration_number=:reg AND a.email=:email LIMIT 1");
 $q->execute(['reg'=>$confirmation['registration'],'email'=>$confirmation['email']]);
 $applicant=$q->fetch();
 if(!$applicant){http_response_code(404);$confirmation=null;}
}
function pmb_h($value):string{return htmlspecialchars((string)$value,ENT_QUOTES,'UTF-8');}
function pmb_pdf_text(string $value):string{
 $value=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value;
 return str_replace(['\\\\','(',')',"\\r","\\n"],['\\\\\\\\','\\\\(','\\\\)',' ',' '],$value);
}
function pmb_make_pdf(array $lines):string{
 $stream="BT\n/F1 11 Tf\n50 790 Td\n";
 foreach($lines as $index=>$line){
  if($index>0)$stream.="0 -24 Td\n";
  $stream.='('.pmb_pdf_text((string)$line).") Tj\n";
 }
 $stream.="ET";
 $objects=[];
 $objects[]='<< /Type /Catalog /Pages 2 0 R >>';
 $objects[]='<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
 $objects[]='<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>';
 $objects[]='<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
 $objects[]='<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream";
 $pdf="%PDF-1.4\n";$offsets=[0];
 foreach($objects as $i=>$obj){$offsets[]=strlen($pdf);$pdf.=($i+1)." 0 obj\n".$obj."\nendobj\n";}
 $xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";
 for($i=1;$i<count($offsets);$i++)$pdf.=sprintf('%010d 00000 n ',$offsets[$i])."\n";
 $pdf.="trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
 return $pdf;
}
if($applicant && (($_GET['download']??'')==='pdf')){
 $lines=[
  'STIT DARUL ILMI TASIKMALAYA',
  'SEKOLAH TINGGI ILMU TARBIYAH',
  'BUKTI PENDAFTARAN MAHASISWA BARU',
  '-----------------------------------------------',
  'Nomor Registrasi: '.$applicant['registration_number'],
  'Nama Lengkap: '.$applicant['full_name'],
  'Email: '.$applicant['email'],
  'Telepon: '.$applicant['phone'],
  'Program Studi: '.($applicant['program']??'-'),
  'Tanggal Pendaftaran: '.date('d-m-Y H:i',strtotime((string)$applicant['created_at'])),
  'Status Pendaftaran: '.str_replace('_',' ',$applicant['status']),
  'Status Dokumen: '.str_replace('_',' ',$applicant['document_status']),
  'Biaya Pendaftaran: Rp'.number_format((float)($applicant['registration_fee_amount']??200000),0,',','.'),
  'Status Pembayaran: '.str_replace('_',' ',$applicant['payment_status']??'unpaid'),
  '',
  'Simpan nomor registrasi untuk memantau proses seleksi.',
  'Bukti ini bukan surat kelulusan atau bukti pembayaran.'
 ];
 $pdf=pmb_make_pdf($lines);
 header('Content-Type: application/pdf');
 header('Content-Disposition: attachment; filename="Bukti-Pendaftaran-'.preg_replace('/[^A-Za-z0-9_-]/','',$applicant['registration_number']).'.pdf"');
 header('Content-Length: '.strlen($pdf));
 echo $pdf;exit;
}

?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Konfirmasi Pendaftaran PMB - STIT Darul Ilmi</title>
<style>
*{box-sizing:border-box}body{margin:0;padding:24px;background:#f3f8f4;color:#173b2b;font:15px/1.6 system-ui,-apple-system,Segoe UI,sans-serif}.wrap{max-width:760px;margin:28px auto}.receipt{background:#fff;border:1px solid #dcebe0;border-radius:20px;padding:30px;box-shadow:0 14px 40px #123a2410}.head{text-align:center;border-bottom:1px dashed #b8d7c1;padding-bottom:22px}.mark{width:58px;height:58px;border-radius:50%;display:grid;place-items:center;background:#e5f6eb;color:#087a4b;font-size:32px;margin:0 auto 12px}.head h1{font-size:24px;color:#075b3d;margin:0}.head p{margin:4px 0;color:#65766b}.success{display:inline-block;margin-top:14px;padding:6px 12px;border-radius:30px;background:#e5f6eb;color:#087a4b;font-weight:800;font-size:13px}.number{margin:22px 0;padding:18px;background:#f1f8f3;border:1px solid #d8ecde;border-radius:14px;text-align:center}.number small{display:block;color:#64756b;font-weight:700;letter-spacing:.1em}.number strong{font-size:25px;color:#075b3d;letter-spacing:.04em;overflow-wrap:anywhere}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:18px 0}.item{padding:12px;border:1px solid #e6efe8;border-radius:12px;min-width:0}.item small{display:block;color:#718176;margin-bottom:4px}.item strong{overflow-wrap:anywhere}.steps{background:#fff9e9;border:1px solid #f0e3bd;border-radius:14px;padding:16px 18px;margin-top:20px}.steps h2{font-size:16px;margin:0 0 8px;color:#725313}.steps ol{margin:0;padding-left:20px}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}.btn{border:0;border-radius:11px;padding:12px 17px;background:#087a4b;color:#fff;font:700 14px system-ui;text-decoration:none;cursor:pointer}.btn.secondary{background:#eaf4ed;color:#075b3d}.note{font-size:12px;color:#718176;text-align:center;margin-top:18px}@media(max-width:560px){body{padding:12px}.wrap{margin:8px auto}.receipt{padding:20px}.grid{grid-template-columns:1fr}.number strong{font-size:21px}}@media print{body{padding:0;background:#fff}.wrap{margin:0;max-width:none}.receipt{border:0;box-shadow:none;border-radius:0;padding:12mm}.actions,.note{display:none}.steps{break-inside:avoid}}
.campus-header{display:grid;grid-template-columns:90px minmax(0,1fr) 90px;align-items:center;gap:12px;background:#fff;border:1px solid #e2eee5;border-radius:14px;padding:14px 18px;margin:0 0 18px}.campus-logo{width:78px;height:78px;object-fit:contain;grid-column:1;grid-row:1}.campus-identity{grid-column:2;grid-row:1;text-align:center;min-width:0;color:#111}.campus-eyebrow{font-size:12px;font-weight:800;letter-spacing:.04em}.campus-name{font-size:19px;font-weight:900;line-height:1.25;margin:2px 0}.campus-meta,.campus-address{font-size:11px;font-weight:650;line-height:1.45}.campus-rule{border-bottom:2px dashed #26352b;margin:7px 4% 0}@media(max-width:560px){.campus-header{grid-template-columns:58px minmax(0,1fr);gap:10px;padding:12px}.campus-logo{width:58px;height:58px;grid-column:1}.campus-identity{grid-column:2}.campus-eyebrow{font-size:9px}.campus-name{font-size:14px}.campus-meta,.campus-address{font-size:9px}}</style>
</head><body><main class="wrap"><header class="campus-header"><img src="<?=pmb_h($pmbLogo)?>" alt="Logo STIT Darul Ilmi" class="campus-logo"><div class="campus-identity"><div class="campus-eyebrow">SEKOLAH TINGGI ILMU TARBIYAH</div><div class="campus-name">STIT DARUL ILMI TASIKMALAYA</div><div class="campus-meta">SK Menteri Agama Republik Indonesia No. 536 Tahun 2026</div><div class="campus-address">Alamat: Jl. Cirahayu Sindangraja Jamanis Tasikmalaya, Jawa Barat 46175</div><div class="campus-rule"></div></div></header>
<?php if(!$confirmation):?>
<section class="receipt"><h1>Konfirmasi tidak tersedia</h1><p>Sesi konfirmasi tidak ditemukan atau telah berakhir. Silakan cek status menggunakan nomor registrasi dan email pada halaman PMB.</p><a class="btn" href="pmb.php">Kembali ke PMB</a></section>
<?php else:?>
<section class="receipt">
<header class="head"><div class="mark">✓</div><h1>Pendaftaran Berhasil Dikirim</h1><p>STIT Darul Ilmi Tasikmalaya</p><span class="success">Data pendaftaran telah tersimpan</span></header>
<div class="number"><small>NOMOR REGISTRASI PMB</small><strong><?=pmb_h($applicant['registration_number'])?></strong></div>
<h2 style="font-size:17px;color:#075b3d">Bukti Pendaftaran Calon Mahasiswa</h2>
<div class="grid">
<div class="item"><small>Nama lengkap</small><strong><?=pmb_h($applicant['full_name'])?></strong></div>
<div class="item"><small>Email</small><strong><?=pmb_h($applicant['email'])?></strong></div>
<div class="item"><small>Nomor telepon</small><strong><?=pmb_h($applicant['phone'])?></strong></div>
<div class="item"><small>Program studi</small><strong><?=pmb_h($applicant['program']??'—')?></strong></div>
<div class="item"><small>Tanggal pendaftaran</small><strong><?=pmb_h(date('d-m-Y H:i',strtotime((string)$applicant['created_at'])))?></strong></div>
<div class="item"><small>Status pendaftaran</small><strong>Menunggu pemeriksaan</strong></div>
<div class="item"><small>Status dokumen</small><strong>Menunggu verifikasi</strong></div>
<div class="item"><small>Biaya pendaftaran</small><strong>Rp<?=number_format((float)($applicant['registration_fee_amount']??200000),0,',','.')?></strong></div>
<div class="item"><small>Status pembayaran</small><strong>Belum bayar</strong></div>
</div>
<section class="steps"><h2>Langkah selanjutnya</h2><ol><li>Simpan nomor registrasi sebagai bukti pendaftaran.</li><li>Hubungi admin PMB untuk memperoleh petunjuk pembayaran resmi sebesar Rp<?=number_format((float)($applicant['registration_fee_amount']??200000),0,',','.')?>.</li><li>Tunggu admin memeriksa kelengkapan dokumen dan pembayaran.</li><li>Pantau hasil seleksi melalui menu Cek Status Pendaftaran.</li></ol></section>
<p style="font-size:13px;color:#64756b;margin-top:18px">Simpan atau cetak halaman ini sebagai bukti pendaftaran. Untuk menyimpan sebagai PDF, pilih tombol Cetak / Simpan PDF lalu pilih tujuan “Save as PDF” pada dialog cetak browser.</p>
<div class="actions"><a class="btn" href="pmb-confirmation.php?download=pdf">Unduh Bukti PDF</a><button class="btn secondary" type="button" onclick="window.print()">Cetak Bukti</button><a class="btn secondary" href="pmb.php">Kembali ke Halaman PMB</a></div>
<p class="note">Bukti ini merupakan konfirmasi penerimaan data pendaftaran, bukan surat kelulusan seleksi atau bukti pembayaran.</p>
</section>
<?php endif;?>
</main></body></html>
