<?php
require __DIR__.'/includes/app.php';$user=require_login();$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_post();
 try{
  throttle('profile:'.$user['id'],20);
  if(!password_verify(password_input('current_password'),$user['password_hash']))throw new RuntimeException('Current password is incorrect.');
  if(field($_POST,'action')==='password'){
   $password=password_input('new_password');check_password($password);if($password!==password_input('password_confirm'))throw new RuntimeException('New passwords do not match.');
   db()->prepare('UPDATE accounts SET password_hash=?,session_version=session_version+1 WHERE id=?')->execute([password_hash($password,PASSWORD_DEFAULT),$user['id']]);
   session_regenerate_id(true);$_SESSION['session_version']=(int)$user['session_version']+1;$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['notice']='Password changed. Other sessions were signed out.';
  }elseif(field($_POST,'action')==='contact'){
   $name=field($_POST,'name');$email=strtolower(field($_POST,'email'));$phone=field($_POST,'phone');
   if(!$name || mb_strlen($name)>100 || strlen($email)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter your name and a valid email.');
   if($phone && !preg_match('/^[+0-9() .-]{7,20}$/D',$phone))throw new RuntimeException('Enter a valid phone number.');
   db()->beginTransaction();db()->prepare('UPDATE users SET name=?,email=? WHERE id=?')->execute([$name,$email,$user['user_id']]);db()->prepare('UPDATE accounts SET email=?,phone=? WHERE id=?')->execute([$email,$phone ?: null,$user['id']]);db()->commit();$_SESSION['notice']='Profile updated. Existing report contact details are preserved.';
  }else throw new RuntimeException('Unknown profile action.');
  go('profile.php');
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();$error=$ex instanceof PDOException ? 'This email is already registered or your changes could not be saved.' : $ex->getMessage();}
}
$page=max(1,(int)field($_GET,'page'));$s=db()->prepare('SELECT COUNT(*) FROM items WHERE reporter_id=?');$s->execute([$user['user_id']]);$pages=max(1,(int)ceil($s->fetchColumn()/20));$page=min($pages,$page);
$s=db()->prepare('SELECT * FROM items WHERE reporter_id=? ORDER BY id DESC LIMIT 20 OFFSET '.(($page-1)*20));$s->execute([$user['user_id']]);$reports=$s->fetchAll();
$cp=max(1,(int)field($_GET,'claims'));$s=db()->prepare('SELECT COUNT(*) FROM recovery_requests WHERE account_id=?');$s->execute([$user['id']]);$cps=max(1,(int)ceil($s->fetchColumn()/20));$cp=min($cp,$cps);
$s=db()->prepare('SELECT r.*,i.title FROM recovery_requests r JOIN items i ON i.id=r.item_id WHERE r.account_id=? ORDER BY r.id DESC LIMIT 20 OFFSET '.(($cp-1)*20));$s->execute([$user['id']]);$claims=$s->fetchAll();
$title='My profile — ZPPSU';require __DIR__.'/includes/header.php';
?>
<main class="container account-page"><div class="page-heading"><span class="form-eyebrow">MY ACCOUNT</span><h2><?= e($user['name']) ?></h2><p><?= $user['role']==='admin' ? 'Administrator' : 'Student' ?> &middot; Student ID: <?= e($user['student_id'] ?: 'Not applicable') ?></p></div>
<?php if(isset($_SESSION['notice'])): ?><p class="notice" role="status"><?= e($_SESSION['notice']) ?></p><?php unset($_SESSION['notice']);endif ?><?php if($error): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif ?>
<div class="profile-grid"><section><h3>My Profile</h3><form method="post" class="form-card"><?= csrf_field() ?><input type="hidden" name="action" value="contact"><label for="name">Name</label><input id="name" name="name" maxlength="100" value="<?= e($user['name']) ?>" autocomplete="name" required><label for="email">Email</label><input id="email" type="email" name="email" maxlength="100" value="<?= e($user['email']) ?>" autocomplete="email" required><label for="phone">Phone</label><input id="phone" name="phone" maxlength="20" value="<?= e($user['phone']) ?>" autocomplete="tel"><label for="current">Current password</label><input id="current" type="password" name="current_password" autocomplete="current-password" required><div class="actions form-actions"><button class="btn">Save profile</button></div></form></section>
<section><h3>Change password</h3><form method="post" class="form-card"><?= csrf_field() ?><input type="hidden" name="action" value="password"><label for="old">Current password</label><input id="old" type="password" name="current_password" autocomplete="current-password" required><label for="new">New password</label><input id="new" type="password" name="new_password" minlength="10" maxlength="72" autocomplete="new-password" required><label for="confirm">Confirm new password</label><input id="confirm" type="password" name="password_confirm" minlength="10" maxlength="72" autocomplete="new-password" required><div class="actions form-actions"><button class="btn">Update password</button></div></form></section></div>
<section class="history-section" id="reports"><h3>My Reports</h3><div class="table-card"><table><thead><tr><th>Item</th><th>Item status</th><th>Review status</th><th>Reported</th></tr></thead><tbody><?php foreach($reports as $report): ?><tr><td><a href="public/view.php?id=<?= (int)$report['id'] ?>"><?= e($report['title']) ?></a></td><td><?= e(ucfirst($report['status'])) ?></td><td><span class="review-badge <?= e($report['moderation_status']) ?>"><?= e(ucfirst($report['moderation_status'])) ?></span></td><td><?= e($report['created_at']) ?></td></tr><?php endforeach ?><?php if(!$reports): ?><tr><td colspan="4">No reports yet. <a href="public/create.php">Report an item</a></td></tr><?php endif ?></tbody></table></div>
<?php if($pages>1): ?><div class="pagination"><?php if($page>1): ?><a href="?page=<?= $page-1 ?>&amp;claims=<?= $cp ?>#reports">Previous</a><?php endif ?><span>Page <?= $page ?> / <?= $pages ?></span><?php if($page<$pages): ?><a href="?page=<?= $page+1 ?>&amp;claims=<?= $cp ?>#reports">Next</a><?php endif ?></div><?php endif ?></section>
<section class="history-section" id="claims"><h3>My Claims</h3><div class="table-card"><table><thead><tr><th>Item</th><th>Status</th><th>Submitted</th></tr></thead><tbody><?php foreach($claims as $claim): ?><tr><td><?= e($claim['title']) ?></td><td><span class="review-badge <?= e($claim['status']) ?>"><?= e($claim['status']==='resolved' ? 'Approved / recovered' : ucfirst($claim['status'])) ?></span></td><td><?= e($claim['created_at']) ?></td></tr><?php endforeach ?><?php if(!$claims): ?><tr><td colspan="3">No claims submitted yet.</td></tr><?php endif ?></tbody></table></div>
<?php if($cps>1): ?><div class="pagination"><?php if($cp>1): ?><a href="?page=<?= $page ?>&amp;claims=<?= $cp-1 ?>#claims">Previous</a><?php endif ?><span>Page <?= $cp ?> / <?= $cps ?></span><?php if($cp<$cps): ?><a href="?page=<?= $page ?>&amp;claims=<?= $cp+1 ?>#claims">Next</a><?php endif ?></div><?php endif ?></section></main>
<?php require __DIR__.'/includes/footer.php'; ?>
