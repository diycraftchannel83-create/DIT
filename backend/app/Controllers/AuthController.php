<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Database;use App\Core\Request;use App\Core\Response;
final class AuthController {
 public static function login(): never {
  $in=Request::json();$username=trim((string)($in['username']??''));$password=(string)($in['password']??'');
  if($username===''||$password===''||strlen($username)>100||strlen($password)>200)Response::json(['success'=>false,'message'=>'Username dan password wajib diisi.'],422);
  $pdo=Database::connection();$q=$pdo->prepare('SELECT u.id,u.username,u.full_name,u.email,u.password_hash,u.status,r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=:username LIMIT 1');$q->execute(['username'=>$username]);$u=$q->fetch();
  if(!$u||$u['status']!=='active'||!password_verify($password,$u['password_hash']))Response::json(['success'=>false,'message'=>'Kredensial tidak valid.'],401);
  $cfg=require dirname(__DIR__,2).'/config/database.local.php';$token=rtrim(strtr(base64_encode(random_bytes(48)),'+/','-_'),'=');$expires=gmdate('Y-m-d H:i:s',time()+((int)$cfg['token_ttl_hours']*3600));
  $ins=$pdo->prepare('INSERT INTO personal_access_tokens(user_id,token_hash,expires_at,created_at) VALUES(:uid,:hash,:expires,UTC_TIMESTAMP())');$ins->execute(['uid'=>$u['id'],'hash'=>hash('sha256',$token),'expires'=>$expires]);
  Response::json(['success'=>true,'token_type'=>'Bearer','token'=>$token,'expires_at'=>gmdate(DATE_ATOM,strtotime($expires.' UTC')),'user'=>['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role']]]);
 }
 public static function logout(): never {$token=Request::bearerToken();if(!$token)Response::json(['success'=>false,'message'=>'Token tidak ditemukan.'],401);$q=Database::connection()->prepare('DELETE FROM personal_access_tokens WHERE token_hash=:hash');$q->execute(['hash'=>hash('sha256',$token)]);Response::json(['success'=>true,'message'=>'Sesi berhasil diakhiri.']);}
}
