<?php
declare(strict_types=1);
header('Content-Type: application/xml; charset=utf-8');
$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
$host=$_SERVER['HTTP_HOST']??'localhost';
$base=$scheme.'://'.$host;
$paths=['/','/#profil','/#akademik','/#program','/#pmb','/#berita','/#galeri','/#kontak'];
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
foreach($paths as $path){echo '<url><loc>'.htmlspecialchars($base.$path,ENT_XML1|ENT_QUOTES,'UTF-8').'</loc></url>';}
echo '</urlset>';
