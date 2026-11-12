<?php
require dirname(__DIR__).'/includes/app.php';
$account=require_login();
$id=(int)field($_GET,'id'); $s=$conn->prepare('SELECT * FROM items WHERE id=?'); $s->bind_param('i',$id); $s->execute(); $item=$s->get_result()->fetch_assoc();
if(!$item) { http_response_code(404); }
$attachments=[]; $error=''; $values=['name'=>$account['name'],'email'=>$account['email'],'proof'=>''];
if($_SERVER['REQUEST_METHOD']==='POST') {
 verify_post(); $values['proof']=field($_POST,'proof');
 try {
    throttle('recovery',5);
    if(!$item || $item['status']==='claimed' || $item['moderation_status']!=='approved') throw new RuntimeException('This item is no longer available for recovery requests.');
    if(field($_POST,'website')!=='') throw new RuntimeException('Unable to accept this request.');
    if($values['name']==='' || mb_strlen($values['name'])>100 || mb_strlen($values['email'])>100 || !filter_var($values['email'],FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter your name and a valid email address.');
    if(mb_strlen($values['proof'])<20 || mb_strlen($values['proof'])>3000) throw new RuntimeException('Provide 20–3000 characters describing ownership evidence or where you found the item.');
    db()->beginTransaction();
    $s=db()->prepare('SELECT status,moderation_status,reporter_id FROM items WHERE id=? FOR UPDATE');$s->execute([$id]);$current=$s->fetch();
    if(!$current || $current['status']==='claimed' || $current['moderation_status']!=='approved')throw new RuntimeException('This item is no longer available.');
    if((int)$current['reporter_id']===(int)$account['user_id'])throw new RuntimeException('You cannot claim your own report.');
    $s=db()->prepare("SELECT id FROM recovery_requests WHERE item_id=? AND account_id=? AND status='pending'");$s->execute([$id,$account['id']]);
    if($s->fetch())throw new RuntimeException('You already have a pending request for this item.');
    $attachments=upload_verification_files();
    db()->prepare('INSERT INTO recovery_requests(item_id,name,email,proof,account_id) VALUES (?,?,?,?,?)')->execute([$id,$account['name'],$account['email'],$values['proof'],$account['id']]);$requestId=db()->lastInsertId();
    foreach($attachments as $file)db()->prepare('INSERT INTO recovery_attachments(request_id,stored_name,original_name,mime_type) VALUES (?,?,?,?)')->execute([$requestId,$file['file'],$file['name'],$file['mime']]);
    db()->commit();$_SESSION['notice']='Claim submitted for review. Track its status in My Claims.';
    header('Location: view.php?id='.$id); exit;
 } catch(Throwable $ex) {
    if(db()->inTransaction())db()->rollBack();
    foreach($attachments as $file) @unlink(dirname(__DIR__).'/database/verification/'.$file['file']);
    $error=$ex instanceof PDOException ? 'Your request could not be saved. Please try again.' : $ex->getMessage();
    if($ex instanceof PDOException) error_log((string)$ex);
 }
}
$prefix='../'; $title='Recovery request — ZPPSU'; require dirname(__DIR__).'/includes/header.php';
?>
<main class="container form-page"><div class="form-page-heading"><span class="form-eyebrow">ZPPSU LOST &amp; FOUND</span><h2 id="form-title"><?= $item && $item['status']==='lost' ? 'I found this item' : 'Claim an item' ?></h2><p>Help us connect the right belonging with the right person.</p></div>
<?php if(!$item || $item['status']==='claimed' || $item['moderation_status']!=='approved'): ?><div class="notice">This item is unavailable for recovery requests.</div><a class="btn secondary" href="../index.php">Browse items</a>
<?php else: ?>
<?php if($error): ?><div class="notice" role="alert"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="form-card" aria-labelledby="form-title"><?= csrf_field() ?><div class="honeypot" aria-hidden="true"><label>Leave blank<input name="website" tabindex="-1" autocomplete="off"></label></div>
<label for="name">Your name <span class="required-mark" aria-hidden="true">*</span></label><input id="name" name="name" maxlength="100" required readonly autocomplete="name" value="<?= e($values['name']) ?>">
<label for="email">Your email <span class="required-mark" aria-hidden="true">*</span></label><input id="email" type="email" name="email" maxlength="100" required readonly autocomplete="email" value="<?= e($values['email']) ?>">
<label for="proof"><?= $item['status']==='found' ? 'Proof of ownership' : 'Recovery details' ?></label><textarea aria-describedby="proof-help" id="proof" name="proof" minlength="20" maxlength="3000" required><?= e($values['proof']) ?></textarea>
<p id="proof-help" class="field-help">Submitting a request does not mark the item claimed. Follow up with the reporter and verify the item before arranging a return.</p>
<label for="verification-files">Supporting verification files <span class="optional-label">(optional)</span></label>
<div class="upload-field"><input id="verification-files" type="file" name="verification_files[]" accept="image/jpeg,image/png,image/webp,application/pdf" multiple data-verification aria-describedby="verification-help"><span class="upload-caption">A receipt, item photo or other ownership evidence can help.</span></div>
<p id="verification-help" class="field-help">Up to 3 files, 5 MB each. JPG, PNG, WebP or PDF. Visible only to administrators. Hide unrelated personal details; never upload passwords or security PINs.</p>
<p class="form-required-note"><span aria-hidden="true">*</span> Name, email and ownership or recovery details are required.</p>
<div class="actions form-actions"><button class="btn" type="submit">Submit request</button><a class="btn secondary" href="view.php?id=<?= $id ?>">Cancel</a></div></form>
<?php endif ?></main>
<script src="../assets/forms.js" defer></script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
