<?php
declare(strict_types=1);
namespace App\Core;
final class Auth {
 public static function user(): ?array {
  $token=Request::bearerToken();if(!$token)return null;
  $q=Database::connection()->prepare('SELECT u.id,u.username,u.full_name,u.email,u.status,r.name AS role FROM personal_access_tokens t JOIN users u ON u.id=t.user_id JOIN roles r ON r.id=u.role_id WHERE t.token_hash=:hash AND t.expires_at>UTC_TIMESTAMP() AND u.status=:status LIMIT 1');
  $q->execute(['hash'=>hash('sha256',$token),'status'=>'active']);return $q->fetch()?:null;
 }
 public static function requireUser(): array {$u=self::user();if(!$u)Response::json(['success'=>false,'message'=>'Autentikasi diperlukan.'],401);return $u;}
 public static function requireRoles(array $roles): array {$u=self::requireUser();if(!in_array($u['role'],$roles,true))Response::json(['success'=>false,'message'=>'Akses tidak diizinkan.'],403);return $u;}
}
