<?php
declare(strict_types=1);
namespace App\Core;
use PDO;
use RuntimeException;
final class Database {
 private static ?PDO $connection=null;
 public static function connection(): PDO {
  if(self::$connection instanceof PDO)return self::$connection;
  $path=dirname(__DIR__,2).'/config/database.local.php';
  if(!is_file($path))throw new RuntimeException('Konfigurasi database belum dibuat.');
  $c=require $path;
  $dsn=sprintf('mysql:host=%s;dbname=%s;charset=%s',$c['host'],$c['database'],$c['charset']??'utf8mb4');
  self::$connection=new PDO($dsn,$c['username'],$c['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
  return self::$connection;
 }
}
