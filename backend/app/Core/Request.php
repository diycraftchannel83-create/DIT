<?php
declare(strict_types=1);
namespace App\Core;
final class Request {
 public static function json(): array {
  $raw=file_get_contents('php://input')?:'';
  if(strlen($raw)>1048576)Response::json(['success'=>false,'message'=>'Request terlalu besar.'],413);
  $data=json_decode($raw,true);if(!is_array($data))Response::json(['success'=>false,'message'=>'JSON tidak valid.'],400);return $data;
 }
 public static function bearerToken(): ?string {
  $h=$_SERVER['HTTP_AUTHORIZATION']??'';return preg_match('/^Bearer\s+([A-Za-z0-9._~-]+)$/i',$h,$m)?$m[1]:null;
 }
}
