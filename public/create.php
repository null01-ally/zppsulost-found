<?php
require dirname(__DIR__).'/includes/app.php';
$account=require_login();
$values=['status'=>'lost','category'=>'','title'=>'','description'=>'','location'=>'','date_reported'=>date('Y-m-d'),'reporter_name'=>$account['name'],'contact_email'=>$account['email'],'contact_phone'=>$account['phone'] ?? ''];
$error=''; $image=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verify_post(); foreach($values as $key=>$value) $values[$key]=field($_POST,$key);
    $values['reporter_name']=$account['name'];
    try {
        throttle('report',10);
        if(field($_POST,'website')!=='') throw new RuntimeException('Unable to accept this report.');
        foreach(['title'=>150,'description'=>5000,'location'=>150,'reporter_name'=>100] as $key=>$max) {
            if($values[$key]==='' || mb_strlen($values[$key])>$max) throw new RuntimeException('Complete all required fields within their displayed length limits.');
        }
        if(mb_strlen($values['description'])<10) throw new RuntimeException('Describe the item in at least 10 characters.');
        if(!in_array($values['status'],['lost','found'],true) || !in_array($values['category'],CATEGORIES,true)) throw new RuntimeException('Choose a valid report type and category.');
        if(!valid_date($values['date_reported'])) throw new RuntimeException('Choose a valid date that is not in the future.');
        if(mb_strlen($values['contact_email'])>100 || !filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid contact email.');
        if($values['contact_phone']!=='' && !preg_match('/^[+0-9() .-]{7,20}$/D',$values['contact_phone'])) throw new RuntimeException('Use a valid phone number of 7–20 characters.');
        $image=upload_image();
        db()->beginTransaction();
        db()->prepare('INSERT INTO items (title,description,status,location,date_reported,image_path,reporter_id,category,contact_email,contact_phone,moderation_status) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$values['title'],$values['description'],$values['status'],$values['location'],$values['date_reported'],$image,$account['user_id'],$values['category'],$values['contact_email'],$values['contact_phone'],'pending']);$id=db()->lastInsertId();
        db()->commit(); $_SESSION['notice']='Your report was submitted for administrator approval.'; header('Location: view.php?id='.$id); exit;
    } catch(Throwable $ex) {
        if(db()->inTransaction())db()->rollBack(); if($image) @unlink(dirname(__DIR__).'/uploads/'.$image);
        $error=$ex instanceof PDOException ? 'Your report could not be saved. Please try again.' : $ex->getMessage();
        if($ex instanceof PDOException) error_log((string)$ex);
    }
}
$prefix='../'; $title='Report an item — ZPPSU'; require dirname(__DIR__).'/includes/header.php';
?>
<main class="container form-page"><div class="form-page-heading"><span class="form-eyebrow">ZPPSU LOST &amp; FOUND</span><h2 id="form-title">Report Lost or Found Item</h2><p>A few details can help a belonging find its way home.</p></div>
<?php if($error): ?><div class="notice" role="alert"><?= e($error) ?></div><?php endif ?>
<form method="post" enctype="multipart/form-data" class="form-card" aria-labelledby="form-title"><?= csrf_field() ?>
    <div class="honeypot" aria-hidden="true"><label>Leave blank<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <label for="status">Status</label><select id="status" name="status" required><?php foreach(['lost'=>'Lost','found'=>'Found'] as $v=>$label): ?><option value="<?= $v ?>" <?= $values['status']===$v ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?></select>
    <label for="category">Category</label><select id="category" name="category" required><option value="">Select a category</option><?php foreach(CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $values['category']===$c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach ?></select>
    <label for="title">Title</label><input id="title" name="title" maxlength="150" value="<?= e($values['title']) ?>" required>
    <label for="description">Description</label><textarea aria-describedby="description-help" id="description" name="description" minlength="10" maxlength="5000" required><?= e($values['description']) ?></textarea>
    <p id="description-help" class="field-help">Keep unique identifying details private so the owner can use them as proof.</p>
    <label for="location">Location</label><input id="location" name="location" maxlength="150" placeholder="e.g. Main Library, second floor" value="<?= e($values['location']) ?>" required>
    <label for="date">Date lost / found</label><input id="date" type="date" name="date_reported" max="<?= date('Y-m-d') ?>" value="<?= e($values['date_reported']) ?>" required>
    <label for="reporter">Student Name (Reporter)</label><input id="reporter" name="reporter_name" maxlength="100" value="<?= e($values['reporter_name']) ?>" autocomplete="name" readonly required>
    <label for="email">Contact email</label><input id="email" type="email" name="contact_email" maxlength="100" value="<?= e($values['contact_email']) ?>" autocomplete="email" required>
    <label for="phone">Phone</label><input id="phone" type="tel" name="contact_phone" maxlength="20" value="<?= e($values['contact_phone']) ?>" autocomplete="tel">
    <p class="field-help">Your report appears after administrator approval. Your name and contact details will be visible on the approved report.</p>
    <label for="image">Image (optional)</label><div class="upload-field"><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="image-help"><span class="upload-caption">A clear photo makes your item easier to recognize.</span></div><p id="image-help" class="field-help">JPG, PNG or WebP, up to 5 MB. Do not upload private IDs or sensitive documents.</p>
    <div class="actions form-actions"><button class="btn" type="submit">Submit report</button><a class="btn secondary" href="../index.php">Cancel</a></div>
</form></main>
<script src="../assets/forms.js" defer></script>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
