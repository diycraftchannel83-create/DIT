<?php
declare(strict_types=1);
$cfgPath = dirname(__DIR__) . '/backend/config/database.local.php';
if (!is_file($cfgPath)) { http_response_code(503); exit('Website belum dikonfigurasi.'); }
$cfg = require $cfgPath;
try {
    $pdo = new PDO(sprintf('mysql:host=%s;dbname=%s;charset=%s',$cfg['host'],$cfg['database'],$cfg['charset']??'utf8mb4'),$cfg['username'],$cfg['password'],[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
    ]);
} catch(Throwable $e) { error_log('[STIT WEBSITE] '.$e->getMessage()); http_response_code(503); exit('Layanan sementara tidak tersedia.'); }
function h(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function webContent(PDO $pdo,string $key,string $default=''): string {
    try {$q=$pdo->prepare('SELECT content_value FROM website_content WHERE content_key=:k LIMIT 1');$q->execute(['k'=>$key]);$v=$q->fetchColumn();return $v===false?$default:(string)$v;}
    catch(Throwable){return $default;}
}
$campus=$pdo->query('SELECT * FROM campus_profile WHERE id=1')->fetch()?:[];
$programs=$pdo->query('SELECT code,name,degree_level FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();
$news=$pdo->query('SELECT id,title,excerpt,body,category,image_url,published_at FROM news_posts WHERE published_at IS NOT NULL AND published_at<=UTC_TIMESTAMP() ORDER BY published_at DESC LIMIT 6')->fetchAll();
$name='STIT Darul Ilmi Tasikmalaya';
$tagline=$campus['tagline']??'Membangun Generasi Unggul, Berilmu, Berakhlak, dan Berlandaskan Nilai-Nilai Islam.';
$about=$campus['about']??'STIT Darul Ilmi Tasikmalaya hadir sebagai lembaga pendidikan tinggi yang berkomitmen mencetak generasi pendidik dan pemimpin masa depan.';
$hero=$campus['hero_image_url']??'';
$aboutImage=webContent($pdo,'about_image_url',$hero);
if ($aboutImage==='') $aboutImage=$hero;
$logo=($campus['logo_image_url']??'')?:'assets/stit-darul-ilmi-icon.svg';
$history=webContent($pdo,'history','');
$why=json_decode(webContent($pdo,'why_cards','[]'),true); if(!is_array($why)||!$why)$why=[
 ['icon'=>'☪','title'=>'Pendidikan Berbasis Nilai Islam','text'=>'Mengintegrasikan nilai keislaman dalam proses pendidikan dan pembentukan karakter.'],
 ['icon'=>'✦','title'=>'Dosen Profesional dan Kompeten','text'=>'Pembelajaran didukung tenaga pendidik sesuai bidang keilmuan.'],
 ['icon'=>'⌂','title'=>'Lingkungan Akademik Kondusif','text'=>'Mendorong budaya belajar, dialog, dan kolaborasi.'],
 ['icon'=>'◎','title'=>'Karakter dan Kepemimpinan','text'=>'Mengembangkan tanggung jawab, integritas, dan kepedulian sosial.'],
 ['icon'=>'↗','title'=>'Pembelajaran Masa Depan','text'=>'Mendorong adaptasi terhadap perkembangan ilmu pengetahuan dan teknologi.'],
 ['icon'=>'✧','title'=>'Kompetensi Profesional','text'=>'Menguatkan kemampuan akademik dan praktik profesional.']
];
$stats=json_decode(webContent($pdo,'stats_json','[]'),true); if(!is_array($stats))$stats=[];
$pmbTitle=webContent($pdo,'pmb_title','Mulai Perjalanan Akademik Anda Bersama STIT Darul Ilmi');
$pmbInfo=webContent($pdo,'pmb_info','Informasi persyaratan, jadwal, dan biaya pendaftaran.');
$pmbUrl=$campus['pmb_url']??'';
$whatsapp=webContent($pdo,'whatsapp','');
$gallery=json_decode(webContent($pdo,'gallery_json','[]'),true); if(!is_array($gallery))$gallery=[];
$testimonials=json_decode(webContent($pdo,'testimonials_json','[]'),true); if(!is_array($testimonials))$testimonials=[];
$facebook=webContent($pdo,'facebook','');$instagram=webContent($pdo,'instagram','');$youtube=webContent($pdo,'youtube','');
$description='Website resmi STIT Darul Ilmi Tasikmalaya. Informasi profil kampus, S1 Manajemen Pendidikan Islam, akademik, berita, dan penerimaan mahasiswa baru.';
$canonical=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off'?'https':'http').'://'.($_SERVER['HTTP_HOST']??'').strtok($_SERVER['REQUEST_URI']??'/','?');
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#70B77E"><meta name="description" content="<?=h($description)?>">
<meta name="keywords" content="STIT Darul Ilmi Tasikmalaya, Kampus STIT Darul Ilmi, Kuliah Manajemen Pendidikan Islam, S1 Manajemen Pendidikan Islam Tasikmalaya, Pendaftaran Mahasiswa Baru STIT Darul Ilmi">
<meta property="og:type" content="website"><meta property="og:title" content="<?=h($name)?> | Website Resmi"><meta property="og:description" content="<?=h($description)?>"><meta property="og:image" content="<?=h($hero?:$logo)?>"><link rel="canonical" href="<?=h($canonical)?>">
<title><?=h($name)?> | Website Resmi</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script type="application/ld+json"><?=json_encode(['@context'=>'https://schema.org','@type'=>'CollegeOrUniversity','name'=>$name,'url'=>$canonical,'description'=>$description,'logo'=>$logo,'address'=>['@type'=>'PostalAddress','streetAddress'=>$campus['address']??'','addressCountry'=>'ID'],'telephone'=>$campus['phone']??'','email'=>$campus['email']??''],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?></script>
<style>
:root{--primary:#70B77E;--light:#A8DDB5;--mint:#E8F5E9;--pale:#F4FAF4;--forest:#245C3A;--white:#fff;--ink:#26352B;--muted:#718078;--gold:#D6B56D;--line:#e3eee4;--shadow:0 14px 40px rgba(36,92,58,.10)}
*{box-sizing:border-box}html{scroll-behavior:smooth;scroll-padding-top:88px}body{margin:0;background:#fff;color:var(--ink);font-family:'DM Sans',sans-serif;line-height:1.65}a{color:inherit}.container{width:min(1160px,calc(100% - 44px));margin:auto}
.topline{background:var(--forest);color:#e9f6eb;font-size:12px}.topline .container{height:34px;display:flex;align-items:center;justify-content:space-between;gap:15px}.topline a{color:#d9efbd;text-decoration:none}
header{position:sticky;top:0;z-index:50;background:rgba(255,255,255,.96);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}.nav{min-height:82px;display:flex;align-items:center;gap:24px}.brand{display:flex;align-items:center;gap:13px;text-decoration:none;min-width:330px;flex-shrink:0}.brand img{width:58px;height:58px;object-fit:contain;flex:0 0 auto}.brand-copy{display:flex;flex-direction:column;line-height:1.2}.brand .brand-kicker{display:block;color:#526b5b;font:700 10px/1.25 'DM Sans',sans-serif;letter-spacing:.105em;text-transform:uppercase;margin-bottom:3px}.brand strong{display:block;font:800 17px/1.2 Manrope;color:var(--forest);letter-spacing:-.025em;white-space:nowrap}.brand small{display:block;color:var(--muted);font-size:9px;line-height:1.35;letter-spacing:.035em;margin-top:4px}.links{display:flex;align-items:center;justify-content:center;gap:19px;flex:1}.links a{text-decoration:none;font-size:12px;font-weight:700;color:#52645a;white-space:nowrap}.links a:hover{color:var(--forest)}.links a.admin-login{background:#e8f5e9;color:#245c3a;border:1px solid #cfe6d2;border-radius:8px;padding:8px 11px;font-weight:800}.links a.admin-login:hover{background:#245c3a;color:#fff}.nav-cta,.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border-radius:10px;padding:12px 18px;text-decoration:none;font-size:13px;font-weight:800;transition:.2s}.nav-cta,.btn-green{background:var(--primary);color:#fff;box-shadow:0 8px 20px rgba(112,183,126,.24)}.btn:hover,.nav-cta:hover{transform:translateY(-2px)}.hamb{display:none;background:var(--mint);color:var(--forest);border:0;border-radius:10px;padding:10px;font-size:21px}
.hero{position:relative;overflow:hidden;background:linear-gradient(115deg,#eff9ed 0%,#dff2df 53%,#c7e8c9 100%);padding:65px 0 72px}.hero:before{content:"";position:absolute;width:480px;height:480px;border:1px solid rgba(36,92,58,.12);border-radius:50%;right:-140px;top:-210px;box-shadow:0 0 0 45px rgba(36,92,58,.035),0 0 0 90px rgba(36,92,58,.025)}.hero-grid{position:relative;display:grid;grid-template-columns:1fr 1.02fr;gap:45px;align-items:center}.eyebrow{display:inline-flex;align-items:center;gap:9px;color:var(--forest);font-size:11px;letter-spacing:.14em;font-weight:800;text-transform:uppercase}.eyebrow:before{content:"";width:24px;height:3px;border-radius:4px;background:var(--gold)}h1,h2,h3{font-family:Manrope,sans-serif}.hero h1{font-size:inherit;line-height:1.35;letter-spacing:-.02em;color:var(--forest);margin:17px 0;font-weight:800}.hero h1 .hero-level{display:block;font-size:clamp(12px,1.45vw,19px);line-height:1.4;letter-spacing:.015em}.hero h1 .hero-institution{display:block;font-size:clamp(27px,3.2vw,42px);line-height:1.13;letter-spacing:-.04em;color:#68a975;margin:5px 0}.hero h1 .hero-license{display:block;font-size:clamp(10px,1.1vw,14px);line-height:1.45;letter-spacing:.025em;color:#52695a;margin-top:8px}.hero h1 span{color:#68a975}.hero p{max-width:570px;color:#53695a;font-size:15px;line-height:1.85}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:25px}.btn-outline{border:1px solid #9acba1;color:var(--forest);background:rgba(255,255,255,.65)}.hero-photo{height:380px;border-radius:34px 110px 34px 34px;overflow:hidden;position:relative;background:linear-gradient(145deg,#8dc99a,#e7f5e6);box-shadow:0 25px 55px rgba(36,92,58,.18)}.hero-photo img{width:100%;height:100%;object-fit:cover}.photo-fallback{height:100%;display:grid;place-items:center;color:var(--forest);font-size:72px;background:radial-gradient(circle at 50% 35%,#fff9,#fff0 55%),linear-gradient(145deg,#a8ddb5,#e8f5e9)}.photo-label{position:absolute;bottom:18px;left:18px;background:#ffffffed;padding:10px 15px;border-radius:12px;color:var(--forest);font-size:12px;font-weight:800}
.stats{position:relative;margin-top:-25px;z-index:2}.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);background:#fff;border:1px solid var(--line);border-radius:18px;box-shadow:var(--shadow);padding:20px}.stat{text-align:center;padding:10px}.stat+.stat{border-left:1px solid var(--line)}.stat strong{display:block;color:var(--forest);font:800 27px Manrope}.stat span{font-size:12px;color:var(--muted)}
section.content{padding:78px 0}.soft{background:var(--pale)}.section-head{text-align:center;max-width:720px;margin:0 auto 38px}.kicker{font-size:10px;letter-spacing:.18em;text-transform:uppercase;color:#589d67;font-weight:800}.section-head h2{font-size:clamp(26px,3vw,36px);line-height:1.2;color:var(--forest);margin:9px 0}.section-head p{color:var(--muted);font-size:14px;margin:0}.about-grid{display:grid;grid-template-columns:1fr 1fr;gap:54px;align-items:center}.about-photo{height:370px;border-radius:22px 80px 22px 22px;overflow:hidden;background:var(--mint);display:grid;place-items:center}.about-photo img{width:100%;height:100%;object-fit:cover}.about-photo span{font-size:70px;color:var(--forest)}.about-copy h2{font-size:32px;line-height:1.2;color:var(--forest);margin:10px 0 15px}.about-copy p{font-size:14px;color:#637269;line-height:1.9}.vm{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:20px}.vm article{padding:17px;background:#f2f9f1;border:1px solid var(--line);border-radius:13px}.vm h3{color:var(--forest);font-size:15px;margin:0 0 6px}.vm p{font-size:12px;margin:0}
.program-grid,.feature-grid,.news-grid,.gallery-grid,.quote-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.program,.feature,.quote{padding:24px;background:#fff;border:1px solid var(--line);border-radius:16px;box-shadow:0 5px 18px rgba(36,92,58,.045);transition:.22s}.program:hover,.feature:hover{transform:translateY(-4px);box-shadow:var(--shadow)}.program-icon,.feature-icon{width:48px;height:48px;border-radius:14px;background:var(--mint);display:grid;place-items:center;color:var(--forest);font-size:22px}.program h3{font-size:17px;color:var(--forest);margin:15px 0 7px}.program p,.feature p{font-size:12px;color:var(--muted);margin:0}.program a{display:inline-block;color:#589d67;text-decoration:none;font-size:12px;font-weight:800;margin-top:14px}
.feature h3{font-size:15px;color:var(--forest);margin:13px 0 7px}
.pmb{padding:48px;border-radius:22px;background:linear-gradient(115deg,#245c3a,#70b77e);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:30px}.pmb h2{font-size:30px;line-height:1.2;margin:8px 0}.pmb p{max-width:650px;color:#eff9ee;font-size:13px}.pmb .btn{background:#fff;color:var(--forest)}
.news{border:1px solid var(--line);border-radius:16px;overflow:hidden;background:#fff;transition:.2s}.news img{width:100%;height:190px;object-fit:cover;display:block;background:var(--mint)}.news-body{padding:18px}.meta{color:#68a975;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}.news h3{font-size:16px;color:var(--forest);line-height:1.4;margin:8px 0}.news p{font-size:12px;color:var(--muted);margin:0}
.gallery-grid{grid-template-columns:repeat(3,1fr)}.gallery-grid img{width:100%;height:220px;object-fit:cover;border-radius:14px;cursor:zoom-in}.gallery-item{position:relative}.gallery-item span{position:absolute;left:12px;bottom:12px;color:#fff;background:#245c3acc;padding:5px 10px;border-radius:7px;font-size:11px}
.quote{border-left:4px solid var(--primary)}.quote p{font-size:13px;color:#53665a;font-style:italic}.quote strong{display:block;color:var(--forest);font-size:13px}.quote small{color:var(--muted)}
.cta{padding:42px;border-radius:22px;background:var(--mint);display:flex;justify-content:space-between;align-items:center;gap:24px}.cta h2{font-size:27px;color:var(--forest);margin:0}.cta p{color:var(--muted);font-size:13px}
footer{background:#245c3a;color:#e9f4e9;padding:54px 0 20px}.foot-grid{display:grid;grid-template-columns:1.3fr .8fr 1fr;gap:40px}.foot-brand{display:flex;align-items:center;gap:12px}.foot-brand img{width:93px;height:93px;object-fit:contain;background:transparent;border-radius:0;mix-blend-mode:normal;filter:none;box-shadow:none}.foot-grid h3{font-size:14px;color:#fff}.foot-grid p,.foot-grid a{font-size:12px;color:#d0e3d2}.foot-grid a{display:block;text-decoration:none;margin:8px 0}.social{display:flex;gap:12px}.social a{display:inline-block}.copyright{border-top:1px solid #ffffff30;margin-top:35px;padding-top:18px;font-size:11px;color:#c0d8c3}
.reveal{opacity:0;transform:translateY(18px);transition:opacity .6s,transform .6s}.reveal.visible{opacity:1;transform:none}
@media(max-width:1000px){.links{gap:10px}.links a{font-size:11px}.brand{min-width:300px}.nav-cta{padding:10px 12px;font-size:11px}}
@media(max-width:780px){.nav{min-height:72px;justify-content:space-between}.hamb{display:block}.nav-cta{margin-left:auto}.links{display:none;position:absolute;left:0;right:0;top:72px;padding:14px 22px 20px;background:#fff;border-bottom:1px solid var(--line);box-shadow:var(--shadow);flex-direction:column;align-items:stretch}.links.open{display:flex}.links a{padding:9px;font-size:13px}.hero-grid,.about-grid{grid-template-columns:1fr;gap:28px}.hero{padding:48px 0 60px}.hero-photo{height:300px}.program-grid,.feature-grid,.news-grid,.quote-grid{grid-template-columns:repeat(2,1fr)}.pmb,.cta{padding:30px;display:block}.pmb .btn,.cta .btn{margin-top:15px}.foot-grid{grid-template-columns:1fr 1fr}}
@media(max-width:520px){.hero h1 .hero-level{font-size:11px}.hero h1 .hero-institution{font-size:clamp(25px,7vw,34px)}.hero h1 .hero-license{font-size:10px}.container{width:calc(100% - 32px)}.topline .container{justify-content:center}.topline span:nth-child(2){display:none}.brand{min-width:0;gap:8px;max-width:calc(100% - 100px)}.brand img{width:42px;height:42px}.brand .brand-kicker{font-size:7px;letter-spacing:.06em}.brand strong{font-size:10px;white-space:normal}.brand small{font-size:7px;letter-spacing:0}.nav-cta{font-size:10px;padding:9px 10px}.hero h1{font-size:12px;line-height:1.4}.hero-photo{height:240px;border-radius:22px 70px 22px 22px}.stat-grid{grid-template-columns:repeat(2,1fr);padding:10px}.stat:nth-child(3){border-left:0}.stat:nth-child(n+3){border-top:1px solid var(--line)}.stat strong{font-size:22px}.stat span{font-size:11px}.program-grid,.feature-grid,.news-grid,.quote-grid,.gallery-grid{grid-template-columns:1fr}.gallery-grid img{height:230px}.vm{grid-template-columns:1fr}.about-photo{height:250px}section.content{padding:58px 0}.pmb h2,.cta h2{font-size:24px}.foot-grid{grid-template-columns:1fr}}
</style>
<link rel="icon" href="<?=h($logo)?>">
<link rel="alternate icon" href="<?=h($logo)?>">
<style>
/* Desktop navigation and overflow hardening */
html,body{max-width:100%;overflow-x:clip}
img,svg,video,iframe{max-width:100%}
.container,.nav,.hero-grid,.hero-grid>*{min-width:0}
@media (min-width:781px) and (max-width:1400px){
  .nav{flex-wrap:wrap;gap:10px 18px;padding-top:12px;padding-bottom:12px}
  .brand{min-width:270px;flex:1 1 270px}
  .nav-cta{flex:0 1 auto;max-width:230px;text-align:center;padding:10px 13px}
  .links{order:3;flex:1 0 100%;justify-content:space-between;flex-wrap:wrap;gap:8px 14px;padding:2px 0 5px}
  .links a{font-size:12px}
}
@media (max-width:780px){
  .nav{flex-wrap:nowrap}
  .links{max-width:100%;max-height:calc(100vh - 90px);overflow-y:auto;overflow-x:hidden}
  .nav-cta{max-width:46%;white-space:normal;text-align:center}
}
</style>

<style id="campus-green-white-theme">
:root{color-scheme:light;--primary:#16834f;--light:#b9e6c8;--mint:#e6f5eb;--pale:#f4faf5;--forest:#075b3d;--white:#fff;--ink:#173b2b;--muted:#60796b;--line:#d7e9dc;--shadow:0 14px 40px rgba(7,91,61,.09)}
html,body{background:#f4faf5!important;color:#173b2b!important}
.topline{background:#075b3d!important;color:#e8f5ec!important;border-color:#cfe5d6!important}
.topline a{color:#d8f5a8!important}
header{background:rgba(255,255,255,.97)!important;border-color:#d7e9dc!important}
.brand .brand-kicker{color:#087a4b!important}.brand strong{color:#075b3d!important}.brand small{color:#60796b!important}
.links a{color:#315b43!important}.links a:hover{color:#087a4b!important}
.nav-cta,.btn-green{background:linear-gradient(135deg,#15945a,#075b3d)!important;color:#fff!important;border-color:#0c7549!important}
.hamb{background:#e6f5eb!important;color:#075b3d!important;border-color:#cde5d5!important}
.hero{background:radial-gradient(ellipse at 82% 20%,rgba(30,153,91,.16),transparent 42%),linear-gradient(135deg,#e9f6ed,#f8fcf9)!important;color:#173b2b!important}
.hero h1,.hero h1 span,.hero p{color:#173b2b!important}.hero .eyebrow,.kicker{color:#087a4b!important}
.btn-outline{color:#075b3d!important;background:#fff!important;border-color:#b9d9c4!important}
section.content,section.soft{background:#f4faf5!important;color:#173b2b!important}
section.content:nth-of-type(even){background:#eaf5ed!important}
section h1,section h2,section h3,section h4,.about-copy h2,.program h3,.feature h3{color:#075b3d!important}
.program,.feature,.quote,.news,.vm article,.stat-grid,.cta{background:#fff!important;color:#173b2b!important;border:1px solid #d7e9dc!important;box-shadow:0 14px 38px rgba(7,91,61,.08)!important}
.program p,.feature p,.quote p,.news p,.about-copy p,.section-head p,.stat span{color:#60796b!important}
.news h3,.vm h3,.stat strong{color:#075b3d!important}
footer{background:#075b3d!important;color:#e8f5ec!important;border-color:#0b754a!important}
footer h3{color:#fff!important}footer a{color:#d8f5a8!important}
@media(max-width:780px){.links{background:#fff!important;border-color:#d7e9dc!important;box-shadow:0 14px 40px rgba(7,91,61,.12)!important}.links a{color:#315b43!important}}

/* MOBILE NAVIGATION DRAWER — full-width, scrollable and safe-area aware */
@media(max-width:780px){
  header{position:sticky!important;top:0!important;z-index:1000!important;overflow:visible!important}
  header .container.nav{position:relative!important;min-height:82px!important;gap:8px!important}
  header .links,
  header .links.open{
    position:absolute!important;
    top:100%!important;left:0!important;right:0!important;
    width:100%!important;max-width:100%!important;
    max-height:calc(100dvh - 150px)!important;
    overflow-x:hidden!important;overflow-y:auto!important;
    overscroll-behavior:contain!important;
    display:none;flex-direction:column!important;align-items:stretch!important;
    justify-content:flex-start!important;gap:0!important;
    padding:10px 16px calc(16px + env(safe-area-inset-bottom))!important;
    margin:0!important;box-sizing:border-box!important;
    background:#fff!important;color:#174b35!important;
    border:1px solid #d7e9dc!important;border-top:0!important;
    border-radius:0 0 16px 16px!important;
    box-shadow:0 18px 34px rgba(7,91,61,.18)!important;
    z-index:1001!important;
  }
  header .links.open{display:flex!important}
  header .links a{
    display:flex!important;align-items:center!important;
    min-height:46px!important;width:100%!important;
    padding:11px 10px!important;margin:0!important;
    color:#245c3a!important;background:transparent!important;
    border-bottom:1px solid #e5f0e8!important;
    border-radius:8px!important;font-size:14px!important;
    line-height:1.35!important;white-space:normal!important;
  }
  header .links a:last-child{border-bottom:0!important}
  header .links a.admin-login{background:#e8f5e9!important;color:#075b3d!important;margin-top:8px!important}
  header .hamb{position:relative!important;z-index:1002!important;flex:0 0 44px!important;width:44px!important;height:44px!important;display:grid!important;place-items:center!important}
  header .nav-cta{flex:0 1 46%!important;max-width:46%!important;min-height:44px!important;line-height:1.25!important}
}
@media(max-width:520px){
  .topline .container{height:34px!important;justify-content:center!important;gap:0!important}
  .topline .container span:nth-child(2){display:none!important}
  .topline .container span:first-child{font-size:10px!important}
  .topline .container a{font-size:10px!important;margin-left:auto!important}
  header .container.nav{min-height:82px!important}
  header .brand{max-width:calc(100% - 154px)!important;flex:1 1 auto!important}
  header .nav-cta{font-size:11px!important;padding:8px 9px!important}
}

</style>


<style id="mobile-navigation-full-list">
/* Keep the mobile navigation in normal document flow so every item is visible. */
@media (max-width:780px){
  header{position:sticky!important;top:0!important;z-index:1000!important;overflow:visible!important}
  header .container.nav{
    display:flex!important;flex-wrap:wrap!important;align-items:center!important;
    min-height:0!important;height:auto!important;overflow:visible!important;
    gap:8px!important;padding-top:12px!important;padding-bottom:12px!important;
  }
  header .brand{order:1!important;flex:1 1 0!important;min-width:0!important;max-width:none!important}
  header .hamb{order:2!important;position:static!important;z-index:2!important;flex:0 0 44px!important;width:44px!important;height:44px!important}
  header .nav-cta{order:3!important;flex:0 1 44%!important;max-width:44%!important;min-width:0!important}
  header .links,
  header .links.open{
    position:static!important;inset:auto!important;top:auto!important;left:auto!important;right:auto!important;
    order:4!important;flex:1 0 100%!important;width:100%!important;max-width:100%!important;
    max-height:none!important;height:auto!important;min-height:0!important;
    overflow:visible!important;overflow-y:visible!important;overflow-x:visible!important;
    overscroll-behavior:auto!important;
    flex-direction:column!important;align-items:stretch!important;justify-content:flex-start!important;
    gap:0!important;margin:8px 0 0!important;
    padding:10px 12px calc(12px + env(safe-area-inset-bottom))!important;
    box-sizing:border-box!important;background:#fff!important;color:#174b35!important;
    border:1px solid #d7e9dc!important;border-radius:16px!important;
    box-shadow:0 12px 28px rgba(7,91,61,.12)!important;
    z-index:auto!important;
  }
  header .links{display:none!important}
  header .links.open{display:flex!important}
  header .links a{
    display:flex!important;align-items:center!important;width:100%!important;
    min-height:48px!important;height:auto!important;padding:12px 12px!important;margin:0!important;
    white-space:normal!important;overflow:visible!important;text-overflow:clip!important;
    line-height:1.4!important;font-size:14px!important;color:#245c3a!important;
    background:transparent!important;border:0!important;border-bottom:1px solid #e5f0e8!important;
    border-radius:8px!important;
  }
  header .links a:last-child{border-bottom:0!important}
  header .links a.admin-login{margin-top:8px!important;background:#e8f5e9!important;color:#075b3d!important}
}
@media (max-width:520px){
  header .container.nav{gap:7px!important;padding-top:10px!important;padding-bottom:10px!important}
  header .brand{max-width:none!important;flex:1 1 0!important}
  header .brand img{width:40px!important;height:40px!important}
  header .brand .brand-kicker{font-size:7px!important}
  header .brand strong{font-size:10px!important;white-space:normal!important}
  header .brand small{font-size:7px!important}
  header .nav-cta{flex:0 1 42%!important;max-width:42%!important;font-size:10px!important;padding:8px 7px!important}
}
</style>

<meta name="theme-color" content="#075b3d">

<style id="android-frontpage-safe-area">
/* Keep the public campus header below Android's overlaid status/notification bar. */
@media (max-width:780px){
  header{
    padding-top:max(env(safe-area-inset-top),30px)!important;
    scroll-margin-top:calc(max(env(safe-area-inset-top),30px) + 12px);
  }
  header .container.nav{
    padding-top:10px!important;
    padding-bottom:10px!important;
  }
  header .links.open{
    max-height:calc(100dvh - max(env(safe-area-inset-top),30px) - 100px)!important;
  }
}
</style>
</head>
<body>
<div class="topline"><div class="container"><span>Website Resmi Perguruan Tinggi</span><span>Nilai Keislaman · Ilmu Pengetahuan · Pengabdian</span><a href="#kontak">Hubungi Kami ↗</a></div></div>
<header><div class="container nav">
<a class="brand" href="#beranda"><img src="<?=h($logo)?>" alt="Logo resmi STIT Darul Ilmi"><span class="brand-copy"><span class="brand-kicker">SEKOLAH TINGGI ILMU TARBIYAH</span><strong>STIT DARUL ILMI TASIKMALAYA</strong><small>SK MENTERI AGAMA RI NO. 536 TAHUN 2026</small></span></a>
<button class="hamb" id="menuToggle" aria-label="Buka navigasi" aria-expanded="false">☰</button>
<nav class="links" id="mainNav" aria-label="Navigasi utama"><a href="#beranda">Beranda</a><a href="#profil">Profil Kampus</a><a href="#akademik">Akademik</a><a href="#program">Program Studi</a><a href="#pmb">Penerimaan Mahasiswa Baru</a><a href="#berita">Berita</a><a href="#galeri">Galeri</a><a href="#kontak">Kontak</a><a href="../portal/public/?page=login">Login Akademik</a><a class="admin-login" href="../admin/public/?page=login" aria-label="Masuk ke Dashboard Admin">Login Dashboard</a></nav>
<a class="nav-cta" href="<?=h($pmbUrl?:'pmb.php')?>">Pendaftaran Mahasiswa Baru ↗</a>
</div></header>
<main>
<section class="hero" id="beranda"><div class="container hero-grid">
<div class="reveal"><span class="eyebrow">SELAMAT DATANG</span><h1><span class="hero-level">SEKOLAH TINGGI ILMU TARBIYAH</span><span class="hero-institution">STIT DARUL ILMI TASIKMALAYA</span><span class="hero-license">SK MENTERI AGAMA RI NO. 536 TAHUN 2026</span></h1><p><?=h($tagline)?> <?=h($about)?></p><div class="actions"><a class="btn btn-green" href="<?=h($pmbUrl?:'#pmb')?>">Daftar Sekarang ↗</a><a class="btn btn-outline" href="#profil">Jelajahi Kampus ↓</a></div></div>
<div class="hero-photo reveal"><?php if($hero):?><img src="<?=h($hero)?>" alt="Gedung kampus STIT Darul Ilmi Tasikmalaya" fetchpriority="high"><?php else:?><div class="photo-fallback" role="img" aria-label="Foto gedung kampus belum diunggah">STIT</div><?php endif;?><div class="photo-label">STIT Darul Ilmi · Tasikmalaya</div></div>
</div></section>
<?php if($stats):?><section class="stats container" aria-label="Statistik kampus"><div class="stat-grid"><?php foreach($stats as $st):if(!is_array($st))continue;?><div class="stat"><strong data-count="<?=h($st['value']??'')?>"><?=h($st['value']??'—')?></strong><span><?=h($st['label']??'Statistik')?></span></div><?php endforeach;?></div></section><?php endif;?>
<section class="content" id="profil"><div class="container about-grid"><div class="about-photo reveal"><?php if($aboutImage):?><img src="<?=h($aboutImage)?>" alt="Lingkungan kampus STIT Darul Ilmi" loading="lazy"><?php else:?><span>✦</span><?php endif;?></div><div class="about-copy reveal"><span class="kicker">MENGENAL KAMPUS</span><h2>Mengenal STIT Darul Ilmi Tasikmalaya</h2><p><?=nl2br(h($about))?></p><?php if($history):?><p><?=nl2br(h($history))?></p><?php endif;?><div class="vm"><?php if(!empty($campus['vision'])):?><article><h3>Visi</h3><p><?=nl2br(h($campus['vision']))?></p></article><?php endif;?><?php if(!empty($campus['mission'])):?><article><h3>Misi</h3><p><?=nl2br(h($campus['mission']))?></p></article><?php endif;?></div></div></div></section>
<section class="content soft" id="akademik"><div class="container"><div class="section-head"><span class="kicker">LINGKUNGAN PENDIDIKAN</span><h2>Mengapa Memilih STIT Darul Ilmi?</h2><p>Nilai dan lingkungan pembelajaran yang mendukung tumbuhnya insan berilmu dan berakhlak.</p></div><div class="feature-grid"><?php foreach($why as $item):if(!is_array($item))continue;?><article class="feature reveal"><div class="feature-icon"><?=h($item['icon']??'✦')?></div><h3><?=h($item['title']??'Keunggulan kampus')?></h3><p><?=h($item['text']??'Informasi keunggulan kampus.')?></p></article><?php endforeach;?></div></div></section>
<section class="content" id="program"><div class="container"><div class="section-head"><span class="kicker">PENDIDIKAN AKADEMIK</span><h2>Program Studi</h2><p>Informasi program pendidikan yang tersedia di STIT Darul Ilmi Tasikmalaya.</p></div><div class="program-grid"><?php if(!$programs):?><div class="program">Informasi program studi akan segera tersedia.</div><?php endif;?><?php foreach($programs as $pr):?><article class="program reveal"><div class="program-icon">▤</div><h3><?=h($pr['name'])?></h3><p><?=h($pr['degree_level'])?> · Program studi aktif</p><a href="#kontak">Informasi program ↗</a></article><?php endforeach;?></div></div></section>
<section class="content soft" id="pmb"><div class="container"><div class="pmb reveal"><div><span class="kicker" style="color:#e7f5d8">PENERIMAAN MAHASISWA BARU</span><h2><?=h($pmbTitle)?></h2><p><?=nl2br(h($pmbInfo))?></p></div><a class="btn" href="<?=h($pmbUrl?:'pmb.php')?>">Daftar Mahasiswa Baru ↗</a></div></div></section>
<section class="content" id="berita"><div class="container"><div class="section-head"><span class="kicker">INFORMASI TERKINI</span><h2>Berita dan Kegiatan Kampus</h2><p>Berita, pengumuman, kegiatan akademik, dan aktivitas kampus yang dipublikasikan secara resmi.</p></div><div class="news-grid"><?php if(!$news):?><div class="program">Belum ada berita yang diterbitkan. Informasi resmi akan tampil di sini.</div><?php endif;?><?php foreach($news as $n):?><article class="news reveal"><?php if(!empty($n['image_url'])):?><img src="<?=h($n['image_url'])?>" alt="<?=h($n['title'])?>" loading="lazy"><?php else:?><div class="photo-fallback" style="height:190px;font-size:36px">STIT</div><?php endif;?><div class="news-body"><span class="meta"><?=h($n['category'])?> · <?=h(date('d M Y',strtotime($n['published_at'])))?></span><h3><?=h($n['title'])?></h3><p><?=h($n['excerpt']?:mb_substr(strip_tags((string)$n['body']),0,150).'…')?></p></div></article><?php endforeach;?></div></div></section>
<?php if($gallery):?><section class="content soft" id="galeri"><div class="container"><div class="section-head"><span class="kicker">GALERI KAMPUS</span><h2>Potret Kegiatan dan Lingkungan Kampus</h2></div><div class="gallery-grid"><?php foreach($gallery as $g):if(!is_array($g)||empty($g['url']))continue;?><div class="gallery-item reveal"><img src="<?=h($g['url'])?>" alt="<?=h($g['alt']??'Galeri STIT Darul Ilmi')?>" loading="lazy" onclick="openLightbox(this.src,this.alt)"><span><?=h($g['caption']??'STIT Darul Ilmi')?></span></div><?php endforeach;?></div></div></section><?php endif;?>
<?php if($testimonials):?><section class="content" id="testimoni"><div class="container"><div class="section-head"><span class="kicker">CERITA CIVITAS AKADEMIKA</span><h2>Testimoni</h2><p>Testimoni yang telah diverifikasi dan disetujui untuk dipublikasikan.</p></div><div class="quote-grid"><?php foreach($testimonials as $t):if(!is_array($t))continue;?><article class="quote reveal"><p>“<?=h($t['quote']??'')?>”</p><strong><?=h($t['name']??'')?></strong><small><?=h($t['role']??'')?></small></article><?php endforeach;?></div></div></section><?php endif;?>
<section class="content soft"><div class="container"><div class="cta"><div><h2>Raih Masa Depan Gemilang Bersama STIT Darul Ilmi</h2><p>Kenali program pendidikan dan dapatkan informasi resmi penerimaan mahasiswa baru.</p></div><a class="btn btn-green" href="<?=h($pmbUrl?:'#kontak')?>">Daftar Sekarang ↗</a></div></div></section>
</main>
<footer id="kontak"><div class="container"><div class="foot-grid"><div><div class="foot-brand"><img src="<?=h($logo)?>" alt="Logo STIT Darul Ilmi"><h3>STIT DARUL ILMI TASIKMALAYA</h3></div><p><?=h($tagline)?></p><div class="social"><?php foreach(['Facebook'=>$facebook,'Instagram'=>$instagram,'YouTube'=>$youtube] as $label=>$url):if($url):?><a href="<?=h($url)?>" target="_blank" rel="noopener"><?=h($label)?></a><?php endif;endforeach;?></div></div><div><h3>NAVIGASI</h3><a href="#profil">Profil Kampus</a><a href="#akademik">Akademik</a><a href="#program">Program Studi</a><a href="#pmb">Penerimaan Mahasiswa Baru</a><a href="#berita">Berita</a></div><div><h3>HUBUNGI KAMI</h3><p><?=h($campus['address']??'Alamat kampus belum diatur')?></p><?php if(!empty($campus['phone'])):?><a href="tel:<?=h($campus['phone'])?>"><?=h($campus['phone'])?></a><?php endif;?><?php if($campus['email']??''):?><a href="mailto:<?=h($campus['email'])?>"><?=h($campus['email'])?></a><?php endif;?><?php if($whatsapp):?><a href="https://wa.me/<?=h(preg_replace('/\D/','',$whatsapp))?>" target="_blank" rel="noopener">WhatsApp resmi</a><?php endif;?></div></div><div class="copyright">© <?=date('Y')?> <?=h($name)?>. Seluruh hak cipta dilindungi.</div></div></footer>
<div id="lightbox" style="display:none;position:fixed;inset:0;background:#102b22ed;z-index:100;align-items:center;justify-content:center;padding:20px" onclick="this.style.display='none'"><button aria-label="Tutup gambar" style="position:absolute;top:20px;right:24px;background:white;border:0;border-radius:50%;padding:10px">✕</button><img id="lightboxImage" alt="" style="max-width:95vw;max-height:90vh;object-fit:contain;border-radius:12px"></div>
<script>
const toggle=document.getElementById('menuToggle'),nav=document.getElementById('mainNav');toggle?.addEventListener('click',()=>{const open=nav.classList.toggle('open');toggle.setAttribute('aria-expanded',String(open));toggle.textContent=open?'✕':'☰'});nav?.querySelectorAll('a').forEach(a=>a.addEventListener('click',()=>{nav.classList.remove('open');toggle?.setAttribute('aria-expanded','false');if(toggle)toggle.textContent='☰'}));
const observer=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');observer.unobserve(e.target)}}),{threshold:.12});document.querySelectorAll('.reveal').forEach(el=>observer.observe(el));
function openLightbox(src,alt){document.getElementById('lightboxImage').src=src;document.getElementById('lightboxImage').alt=alt;document.getElementById('lightbox').style.display='flex'}
</script>
</body></html>