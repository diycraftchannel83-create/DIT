<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Database;use App\Core\Response;
final class ProfileController {
 public static function me(): never {$u=Auth::requireUser();$q=Database::connection()->prepare('SELECT s.nim,s.phone,s.address,s.status AS student_status,p.name AS study_program FROM students s LEFT JOIN study_programs p ON p.id=s.study_program_id WHERE s.user_id=:uid LIMIT 1');$q->execute(['uid'=>$u['id']]);Response::json(['success'=>true,'user'=>['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role']],'student'=>$q->fetch()?:null]);}
}
