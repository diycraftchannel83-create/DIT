<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Database;use App\Core\Response;
final class ProfileController {
 public static function me(): never {
  $u=Auth::requireUser();
  $q=Database::connection()->prepare('SELECT s.nim,s.study_program_id,s.entry_year,s.phone,s.address,s.national_id,s.birth_place,s.birth_date,s.gender,s.religion,s.marital_status,s.province,s.city,s.district,s.village,s.postal_code,s.father_name,s.mother_name,s.guardian_name,s.guardian_phone,s.emergency_contact_name,s.emergency_contact_phone,s.admission_pathway,s.registration_date,s.photo_url,s.status AS student_status,p.name AS study_program FROM students s LEFT JOIN study_programs p ON p.id=s.study_program_id WHERE s.user_id=:uid LIMIT 1');
  $q->execute(['uid'=>$u['id']]);
  Response::json(['success'=>true,'user'=>['id'=>(int)$u['id'],'username'=>$u['username'],'name'=>$u['full_name'],'email'=>$u['email'],'role'=>$u['role']],'student'=>$q->fetch()?:null]);
 }
}
