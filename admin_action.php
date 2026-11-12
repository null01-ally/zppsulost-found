<?php
require __DIR__.'/includes/app.php';$admin=require_admin();verify_post();$action=field($_POST,'action');$id=(int)field($_POST,'id');$success=false;
try{
 db()->beginTransaction();
 if($action==='update_user'){
  if(!password_verify(password_input('current_password'),$admin['password_hash']))throw new RuntimeException('Your current password is incorrect.');
  // Serialize account administration, including the last-active-administrator invariant.
  $accounts=db()->query('SELECT id,role,active FROM accounts ORDER BY id FOR UPDATE')->fetchAll();$target=null;$adminCount=0;
  foreach($accounts as $account){if($account['role']==='admin' && $account['active'])$adminCount++;if((int)$account['id']===$id)$target=$account;}
  if(!$target)throw new RuntimeException('Account not found.');
  $role=field($_POST,'role');$active=field($_POST,'active')==='1' ? 1 : 0;$name=field($_POST,'name');$email=strtolower(field($_POST,'email'));$student=field($_POST,'student_id');
  if(!in_array($role,['student','admin'],true) || !$name || mb_strlen($name)>100 || strlen($email)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($student)>40)throw new RuntimeException('Enter valid account information.');
  if($role==='student' && !$student)throw new RuntimeException('Student accounts require a Student ID.');
  if($id===(int)$admin['id'] && ($role!=='admin' || !$active))throw new RuntimeException('You cannot deactivate or demote your own administrator account.');
  if($target['role']==='admin' && $target['active'] && ($role!=='admin' || !$active) && $adminCount<=1)throw new RuntimeException('At least one active administrator must remain.');
  $s=db()->prepare('SELECT user_id FROM accounts WHERE id=?');$s->execute([$id]);$userId=$s->fetchColumn();
  db()->prepare('UPDATE users SET name=?,email=? WHERE id=?')->execute([$name,$email,$userId]);
  db()->prepare('UPDATE accounts SET email=?,student_id=?,role=?,active=?,session_version=session_version+1 WHERE id=?')->execute([$email,$student ?: null,$role,$active,$id]);
  if($id===(int)$admin['id'])$_SESSION['session_version']=(int)$admin['session_version']+1;
 }elseif(in_array($action,['approve_report','reject_report','archive'],true)){
  $s=db()->prepare('SELECT * FROM items WHERE id=? FOR UPDATE');$s->execute([$id]);$item=$s->fetch();if(!$item)throw new RuntimeException('Item not found.');
  if($action!=='archive' && $item['moderation_status']!=='pending')throw new RuntimeException('Only pending reports can be approved or rejected.');
  if($action==='archive' && $item['moderation_status']==='archived')throw new RuntimeException('This item is already archived.');
  $status=['approve_report'=>'approved','reject_report'=>'rejected','archive'=>'archived'][$action];
  db()->prepare('UPDATE items SET moderation_status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$status,$admin['id'],$id]);
  if($status!=='approved')db()->prepare("UPDATE recovery_requests SET status='rejected',reviewed_by=?,reviewed_at=NOW() WHERE item_id=? AND status='pending'")->execute([$admin['id'],$id]);
 }elseif(in_array($action,['approve_claim','reject_claim'],true)){
  $s=db()->prepare('SELECT item_id FROM recovery_requests WHERE id=?');$s->execute([$id]);$itemId=$s->fetchColumn();if(!$itemId)throw new RuntimeException('Claim not found.');
  $s=db()->prepare('SELECT * FROM items WHERE id=? FOR UPDATE');$s->execute([$itemId]);$item=$s->fetch();
  $s=db()->prepare('SELECT * FROM recovery_requests WHERE id=? FOR UPDATE');$s->execute([$id]);$claim=$s->fetch();if(!$claim || $claim['status']!=='pending')throw new RuntimeException('This claim was already reviewed.');
  if($action==='approve_claim'){
   if(!$item || $item['moderation_status']!=='approved' || $item['status']==='claimed')throw new RuntimeException('This item is no longer active.');
   if((int)$claim['account_id']===(int)$admin['id'])throw new RuntimeException('Another administrator must review your own claim.');
   db()->prepare("UPDATE items SET status='claimed' WHERE id=?")->execute([$itemId]);
   db()->prepare("UPDATE recovery_requests SET status='rejected',reviewed_by=?,reviewed_at=NOW() WHERE item_id=? AND id<>? AND status='pending'")->execute([$admin['id'],$itemId,$id]);
  }
  db()->prepare('UPDATE recovery_requests SET status=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?')->execute([$action==='approve_claim' ? 'resolved' : 'rejected',$admin['id'],$id]);
 }else throw new RuntimeException('Unknown management action.');
 db()->commit();$success=true;$_SESSION['notice']='Changes saved successfully.';
}catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();$_SESSION['notice']=$ex instanceof PDOException ? 'Changes could not be saved. Check for a duplicate email or Student ID and retry.' : $ex->getMessage();if($ex instanceof PDOException)error_log((string)$ex);}
if(str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')){
 header('Content-Type: application/json');header('Cache-Control: no-store');http_response_code($success ? 200 : 422);
 echo json_encode(['ok'=>$success,'message'=>$_SESSION['notice']]);unset($_SESSION['notice']);exit;
}
go($action==='update_user' ? 'admin_users.php' : 'admin.php');
