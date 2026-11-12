<?php
// Local demo provisioning only; repeat runs do not reset existing passwords.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/auth.php';
foreach([['Campus Administrator','admin@zppsu.edu.ph','admin',null],['Campus Student','student@zppsu.edu.ph','student','2026-0001']] as [$name,$email,$role,$student]){
 $s=db()->prepare('SELECT id FROM accounts WHERE email=?');$s->execute([$email]);if($s->fetch())continue;
 db()->beginTransaction();
 try{
  db()->prepare('INSERT INTO users(name,email) VALUES (?,?)')->execute([$name,$email]);$user=db()->lastInsertId();
  db()->prepare('INSERT INTO accounts(user_id,email,student_id,password_hash,role) VALUES (?,?,?,?,?)')->execute([$user,$email,$student,password_hash('password123',PASSWORD_DEFAULT),$role]);
  db()->commit();
 }catch(Throwable $ex){db()->rollBack();throw $ex;}
}
echo "Local demo accounts are ready.\n";
