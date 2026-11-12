<?php
require __DIR__.'/includes/app.php';if(current_user())go('profile.php');$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_post();
 try{
  throttle('register',8);$name=field($_POST,'name');$email=strtolower(field($_POST,'email'));$student=field($_POST,'student_id');$password=password_input();
  if(!$name || mb_strlen($name)>100 || strlen($email)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Enter your full name and a valid email address.');
  if(!$student || !preg_match('/^[a-zA-Z0-9 -]{1,40}$/D',$student))throw new RuntimeException('Enter a Student ID of up to 40 letters, numbers, spaces or hyphens.');
  check_password($password);if($password!==password_input('password_confirm'))throw new RuntimeException('Passwords do not match.');
  db()->beginTransaction();db()->prepare('INSERT INTO users(name,email) VALUES (?,?)')->execute([$name,$email]);$uid=db()->lastInsertId();
  // Public registration always creates a student; no submitted role is trusted.
  db()->prepare("INSERT INTO accounts(user_id,email,student_id,password_hash,role) VALUES (?,?,?,?,'student')")->execute([$uid,$email,$student,password_hash($password,PASSWORD_DEFAULT)]);$id=db()->lastInsertId();
  db()->commit();sign_in(['id'=>$id,'session_version'=>1]);go('index.php');
 }catch(Throwable $ex){if(db()->inTransaction())db()->rollBack();$error=$ex instanceof PDOException ? ((string)$ex->getCode()==='23000' ? 'This email or Student ID is already registered.' : 'Registration is temporarily unavailable.') : $ex->getMessage();if($ex instanceof PDOException)error_log((string)$ex);}
}
$title='Student registration — ZPPSU';require __DIR__.'/includes/header.php';
?>
<main class="container form-page auth-page"><div class="form-page-heading"><span class="form-eyebrow">STUDENT PORTAL</span><h2 id="form-title">Create a student account</h2><p>Report items and track your recovery requests.</p></div><?php if($error): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif ?>
<form method="post" class="form-card"><?= csrf_field() ?><label for="name">Full name</label><input id="name" name="name" maxlength="100" value="<?= e(field($_POST,'name')) ?>" autocomplete="name" required><label for="student">Student ID</label><input id="student" name="student_id" maxlength="40" value="<?= e(field($_POST,'student_id')) ?>" required><label for="email">Email</label><input id="email" type="email" name="email" maxlength="100" value="<?= e(field($_POST,'email')) ?>" autocomplete="email" required><label for="password">Password</label><input id="password" type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password" required><p class="field-help">Use at least 10 characters.</p><label for="confirm">Confirm password</label><input id="confirm" type="password" name="password_confirm" minlength="10" maxlength="72" autocomplete="new-password" required><div class="actions form-actions"><button class="btn">Register</button><a class="btn secondary" href="login.php">Back to sign in</a></div></form></main>
<?php require __DIR__.'/includes/footer.php'; ?>
