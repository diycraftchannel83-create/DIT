<?php
declare(strict_types=1);
namespace App\\Controllers;
use App\\Core\\Database;
use App\\Core\\Response;
final class PublicContentController {
 public static function home(): never {
  $db=Database::connection();
  $profile=$db->query('SELECT tagline,about,vision,mission,address,phone,email,website,pmb_url,updated_at FROM campus_profile WHERE id=1')->fetch();
  $news=$db->query("SELECT id,title,excerpt,body,category,image_url,published_at FROM news_posts WHERE published_at IS NOT NULL AND published_at<=UTC_TIMESTAMP() ORDER BY published_at DESC LIMIT 10")->fetchAll();
  $programs=$db->query('SELECT code,name,degree_level FROM study_programs WHERE is_active=1 ORDER BY name')->fetchAll();
  Response::json(['success'=>true,'campus'=>['name'=>'STIT Darul Ilmi Tasikmalaya']+($profile?:[]),'programs'=>$programs,'news'=>$news]);
 }
}
