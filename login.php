<?php
require __DIR__.'/includes/app.php';if(current_user())go(is_admin() ? 'admin.php' : 'index.php');$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_post();
 try{
  throttle('login',20);$s=db()->prepare('SELECT * FROM accounts WHERE email=?');$s->execute([field($_POST,'email')]);$user=$s->fetch();
  $ok=password_verify(password_input(),$user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
  if(!$user || !$user['active'] || !$ok)throw new RuntimeException('Email or password is incorrect, or the account is inactive.');
  if(password_needs_rehash($user['password_hash'],PASSWORD_DEFAULT))db()->prepare('UPDATE accounts SET password_hash=? WHERE id=?')->execute([password_hash(password_input(),PASSWORD_DEFAULT),$user['id']]);
  sign_in($user);go($user['role']==='admin' ? 'admin.php' : 'index.php');
 }catch(Throwable $ex){$error=$ex instanceof PDOException ? 'Sign-in is temporarily unavailable.' : $ex->getMessage();if($ex instanceof PDOException)error_log((string)$ex);}
}
$title='Sign in — ZPPSU';require __DIR__.'/includes/header.php';
?>
<main class="container form-page auth-page"><div class="form-page-heading"><span class="form-eyebrow">WELCOME TO ZPPSU LOST &amp; FOUND</span><h2 id="form-title">Sign in to your campus portal</h2><p>A shared space for belongings to find their way home.</p></div><?php if($error): ?><p class="notice" role="alert"><?= e($error) ?></p><?php endif ?>
<form method="post" class="form-card" aria-labelledby="form-title"><?= csrf_field() ?><label for="email">Email</label><input id="email" type="email" name="email" maxlength="100" value="<?= e(field($_POST,'email')) ?>" autocomplete="username" required><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="current-password" required><div class="actions form-actions"><button class="btn">Sign in</button><a class="btn secondary" href="register.php">Create student account</a></div></form>
<section class="demo-accounts" aria-labelledby="demo-title"><h3 id="demo-title">Demo accounts</h3><p>Use these accounts to explore the local system.</p><dl><div><dt>Administrator</dt><dd>admin@zppsu.edu.ph</dd></div><div><dt>Student</dt><dd>student@zppsu.edu.ph</dd></div><div><dt>Password for both</dt><dd><code>password123</code></dd></div></dl></section></main>
<?php require __DIR__.'/includes/footer.php'; ?>
