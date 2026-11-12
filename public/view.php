<?php
require dirname(__DIR__).'/includes/app.php';
$account=require_login();
$id=(int)field($_GET,'id');
$s=$conn->prepare('SELECT i.*,u.name AS reporter_name,u.email AS reporter_email FROM items i JOIN users u ON u.id=i.reporter_id WHERE i.id=?'); $s->bind_param('i',$id); $s->execute(); $item=$s->get_result()->fetch_assoc();
if($item && $item['moderation_status']!=='approved' && !is_admin() && (int)$item['reporter_id']!==(int)$account['user_id'])$item=null;
if(!$item) http_response_code(404);
$prefix='../'; $title=$item ? $item['title'].' — ZPPSU' : 'Item not found'; require dirname(__DIR__).'/includes/header.php';
?>
<main class="container detail-page">
<?php if(isset($_SESSION['notice'])): ?><p class="notice" role="status"><?= e($_SESSION['notice']) ?></p><?php unset($_SESSION['notice']); endif ?>
<?php if(!$item): ?><div class="empty"><h2>Item not found</h2><a class="btn secondary" href="../index.php">Browse items</a></div>
<?php else: $email=$item['contact_email'] ?: $item['reporter_email']; ?>
<article class="card detail-card" aria-labelledby="item-title"><div class="detail-image"><img src="<?= e(image_url($item['image_path'],'../')) ?>" alt="<?= e($item['title']) ?>"></div><div class="content">
    <div class="card-badges"><span class="status <?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span><span class="category-tag"><?= e($item['category']) ?></span></div>
    <h2 id="item-title"><?= e($item['title']) ?></h2><p class="full-description"><?= e($item['description']) ?></p>
    <dl class="detail-metadata">
        <div><dt>Location</dt><dd><?= e($item['location']) ?></dd></div>
        <div><dt>Date lost / found</dt><dd><time datetime="<?= e($item['date_reported']) ?>"><?= e(date('M j, Y',strtotime($item['date_reported']))) ?></time></dd></div>
        <div><dt>Report submitted</dt><dd><?= e(date('M j, Y, g:i:s A',strtotime($item['created_at']))) ?> <span class="metadata-timezone">(Asia/Manila)</span></dd></div>
        <div><dt>Reporter</dt><dd><?= e($item['reporter_name']) ?></dd></div>
        <div><dt>Email</dt><dd><?php if(filter_var($email,FILTER_VALIDATE_EMAIL)): ?><a href="<?= e('mailto:'.$email.'?subject='.rawurlencode('ZPPSU item #'.$id.': '.$item['title'])) ?>"><?= e($email) ?></a><?php else: ?>Not provided<?php endif ?></dd></div>
        <?php if($item['contact_phone']): ?><div><dt>Phone</dt><dd><?= e($item['contact_phone']) ?></dd></div><?php endif ?>
    </dl>
    <div class="actions detail-actions"><?php if($item['status']!=='claimed' && $item['moderation_status']==='approved'): ?><a class="btn" href="claim.php?id=<?= $id ?>"><?= $item['status']==='found' ? 'Claim Item' : 'I Found This' ?></a><?php elseif($item['status']==='claimed'): ?><span class="resolved-note">This item has been recovered.</span><?php else: ?><span class="resolved-note">Review status: <?= e(ucfirst($item['moderation_status'])) ?></span><?php endif ?><a class="btn secondary" href="../index.php">Back to items</a></div>
</div></article>
<?php endif ?></main>
<?php require dirname(__DIR__).'/includes/footer.php'; ?>
