<?php
// CLI-only, repeatable sample activity. Existing records and review decisions are preserved.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/auth.php';
date_default_timezone_set('Asia/Manila');
db()->beginTransaction();
try{
 $accounts=[];
 foreach([
  ['Mika Santos','mika.santos.zppsu4827@gmail.com','2026-1048'],
  ['Alex Reyes','alex.reyes.zppsu6139@gmail.com','2026-1061'],
  ['Jamie Cruz','jamie.cruz.zppsu7524@gmail.com','2026-1075'],
  ['Sam Ramos','sam.ramos.zppsu9368@gmail.com','2026-1093']
 ] as [$name,$email,$student]){
  $s=db()->prepare('SELECT a.*,u.name FROM accounts a JOIN users u ON u.id=a.user_id WHERE a.email=?');$s->execute([$email]);$account=$s->fetch();
  if(!$account){
   db()->prepare('INSERT INTO users(name,email) VALUES (?,?)')->execute([$name,$email]);$uid=db()->lastInsertId();
   db()->prepare("INSERT INTO accounts(user_id,email,student_id,password_hash,role) VALUES (?,?,?,?,'student')")->execute([$uid,$email,$student,password_hash('password123',PASSWORD_DEFAULT)]);
   $account=['id'=>db()->lastInsertId(),'user_id'=>$uid,'name'=>$name,'email'=>$email];
  }
  $accounts[]=$account;
 }
 $s=db()->prepare('SELECT a.*,u.name FROM accounts a JOIN users u ON u.id=a.user_id WHERE a.email=?');$s->execute(['student@zppsu.edu.ph']);$demo=$s->fetch();if(!$demo)throw new RuntimeException('Install the demo accounts first.');
 $admin=db()->query("SELECT id FROM accounts WHERE role='admin' AND active=1 ORDER BY id LIMIT 1")->fetchColumn();
 $reports=[
  ['Black Calculator with Sliding Cover','Electronics','lost','pending','A black scientific calculator with a sliding cover. Last seen after the afternoon engineering lecture.','Engineering Building, Room 203','Black Scientific Calculator',$demo,0],
  ['Green Keyring near Student Center','Keys','found','pending','A green keyring with three metal keys found beside a bench. The owner should describe the keyring design.','Student Center benches','Three Keys on a Green Keyring',$accounts[0],1],
  ['Blue Notebook with Lecture Notes','Books','lost','pending','A blue spiral notebook containing mathematics lecture notes and several page tabs.','Main Library, second floor','Blue Calculus Notebook',$accounts[1],1],
  ['Maroon Bottle at Covered Walkway','Personal Items','found','pending','A maroon reusable bottle found at the covered walkway after lunch. Describe its identifying marks to claim it.','Covered Walkway','Maroon Water Bottle',$demo,0],
  ['Recovered Library Card - Archived Record','IDs','claimed','archived','A library card was returned after verification. This sample report is archived for record keeping.','Main Library circulation desk','Library Card in Clear Holder',$accounts[2],5],
  ['Incomplete Umbrella Report','Personal Items','lost','rejected','A compact umbrella reported without enough identifying details. Sample rejection for a report requiring clarification.','Campus canteen','Navy Blue Umbrella',$accounts[3],3]
 ];
 foreach($reports as [$title,$category,$type,$state,$description,$location,$photoTitle,$reporter,$days]){
  $s=db()->prepare('SELECT id FROM items WHERE title=? AND reporter_id=?');$s->execute([$title,$reporter['user_id']]);if($s->fetchColumn())continue;
  $s=db()->prepare('SELECT image_path FROM items WHERE title=? ORDER BY id LIMIT 1');$s->execute([$photoTitle]);$image=$s->fetchColumn() ?: null;
  $date=(new DateTimeImmutable('now',new DateTimeZone('Asia/Manila')))->modify('-'.$days.' days')->format('Y-m-d');
  db()->prepare('INSERT INTO items(title,category,status,moderation_status,description,location,date_reported,image_path,reporter_id,contact_email,reviewed_by,reviewed_at,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,DATE_SUB(NOW(),INTERVAL ? DAY))')->execute([$title,$category,$type,$state,$description,$location,$date,$image,$reporter['user_id'],$reporter['email'],$state==='pending' ? null : $admin,$state==='pending' ? null : date('Y-m-d H:i:s'),$days]);
 }
 $claims=[
  ['Wireless Earbuds in White Case',$accounts[0],'pending','My earbuds case has a small blue star sticker underneath and a faint scratch near the charging port.'],
  ['Student ID with Maroon Lanyard',$accounts[1],'pending','The ID holder has a small ink mark on the upper right corner, and the lanyard has a silver clip. I can show matching identification in person.'],
  ['Three Keys on a Green Keyring',$accounts[2],'pending','The green keyring has a tiny scratch on one side. Two keys are silver and one has a rounded black head.'],
  ['Introduction to Programming Textbook',$demo,'pending','There is a handwritten note on the inside cover and yellow page tabs near the introductory exercises.'],
  ['Navy Blue Umbrella',$accounts[3],'resolved','The navy umbrella has a short black wrist strap and a repaired seam near the handle. Ownership was verified before collection.'],
  ['Wireless Earbuds in White Case',$accounts[1],'rejected','I initially thought this was my pair, but the case markings do not match. This sample request was rejected after verification.']
 ];
 foreach($claims as [$title,$claimant,$state,$proof]){
  $s=db()->prepare('SELECT id FROM items WHERE title=? ORDER BY id LIMIT 1');$s->execute([$title]);$itemId=$s->fetchColumn();if(!$itemId)continue;
  $s=db()->prepare('SELECT id FROM recovery_requests WHERE item_id=? AND account_id=? AND proof=?');$s->execute([$itemId,$claimant['id'],$proof]);if($s->fetchColumn())continue;
  db()->prepare('INSERT INTO recovery_requests(item_id,name,email,proof,status,account_id,reviewed_by,reviewed_at) VALUES (?,?,?,?,?,?,?,?)')->execute([$itemId,$claimant['name'],$claimant['email'],$proof,$state,$claimant['id'],$state==='pending' ? null : $admin,$state==='pending' ? null : date('Y-m-d H:i:s')]);
 }
 db()->commit();echo "Admin samples added: four students, six reports and six claims. Repeat imports preserve review decisions.\n";
}catch(Throwable $ex){db()->rollBack();throw $ex;}
