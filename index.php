<?php
require __DIR__.'/includes/app.php';
$account=require_login();
$q=mb_substr(field($_GET,'q'),0,100);
$category=field($_GET,'category'); $status=field($_GET,'status');
if (!in_array($category,CATEGORIES,true)) $category='';
if (!in_array($status,['lost','found','claimed'],true)) $status='';
// Only fixed SQL fragments are assembled; all search/filter values are bound.
$where=["i.moderation_status='approved'"]; $params=[]; $types='';
if ($q!=='') { $where[]='(i.title LIKE ? OR i.description LIKE ? OR i.location LIKE ?)'; $like='%'.$q.'%'; $params=[$like,$like,$like]; $types='sss'; }
if ($category!=='') { $where[]='i.category=?'; $params[]=$category; $types.='s'; }
if ($status!=='') { $where[]='i.status=?'; $params[]=$status; $types.='s'; }
$condition=$where ? ' WHERE '.implode(' AND ',$where) : '';
$s=$conn->prepare('SELECT COUNT(*) AS total FROM items i'.$condition);
if ($params) $s->bind_param($types,...$params);
$s->execute(); $total=(int)$s->get_result()->fetch_assoc()['total'];
$pages=max(1,(int)ceil($total/24)); $page=min($pages,max(1,(int)field($_GET,'page')));
$s=$conn->prepare('SELECT i.*,u.name AS reporter_name FROM items i JOIN users u ON u.id=i.reporter_id'.$condition.' ORDER BY i.created_at DESC,i.id DESC LIMIT 24 OFFSET '.(($page-1)*24));
if ($params) $s->bind_param($types,...$params);
$s->execute(); $items=$s->get_result();
require __DIR__.'/includes/header.php';
?>
<main class="container">
    <div class="search-block">
        <form method="get" action="index.php" role="search">
            <div class="search-row">
                <label class="search-keyword"><span>Search campus items</span><input type="search" name="q" class="search-input" maxlength="100" placeholder="Title, description, location..." value="<?= e($q) ?>"></label>
                <label class="search-filter"><span>Category</span><select name="category"><option value="">All categories</option><?php foreach(CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $category===$c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach ?></select></label>
                <label class="search-filter"><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach(['lost','found','claimed'] as $v): ?><option value="<?= $v ?>" <?= $status===$v ? 'selected' : '' ?>><?= ucfirst($v) ?></option><?php endforeach ?></select></label>
                <button type="submit" class="search-btn">Search</button>
            </div>
        </form>
        <a href="public/create.php" class="report-btn">+ Report Item</a>
    </div>
    <div class="listing-heading"><h2>Campus lost &amp; found</h2><span><?= $total ?> <?= $total===1 ? 'item' : 'items' ?><?php if($q!=='' || $category || $status): ?> &middot; <a href="index.php">Clear filters</a><?php endif ?></span></div>
    <div class="grid">
    <?php if (!$total): ?>
        <div class="empty"><img class="empty-illustration" src="assets/item-placeholder.svg" alt=""><h3><?= $q!=='' || $category || $status ? 'No matching items' : 'No items reported yet' ?></h3><p><?= $q!=='' || $category || $status ? 'Try a different keyword or filter.' : 'Lost something or found a belonging? Your report could help bring it home.' ?></p><?php if($q!=='' || $category || $status): ?><a class="btn secondary" href="index.php">Clear filters</a><?php endif ?></div>
    <?php else: while($item=$items->fetch_assoc()): $details='public/view.php?id='.(int)$item['id']; ?>
        <article class="card item-card">
            <a class="thumbnail-link" href="<?= e($details) ?>" aria-label="<?= e('View '.$item['title']) ?>"><img src="<?= e(image_url($item['image_path'])) ?>" alt="<?= e($item['title']) ?>" loading="lazy"></a>
            <div class="content">
                <div class="card-badges"><span class="status <?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span><span class="category-tag"><?= e($item['category']) ?></span></div>
                <h3><a href="<?= e($details) ?>"><?= e($item['title']) ?></a></h3>
                <p class="item-description"><?= e(mb_strimwidth($item['description'],0,125,'…')) ?></p>
                <p><strong>Location:</strong> <?= e($item['location']) ?></p>
                <p><strong>Date:</strong> <?= e(date('M j, Y',strtotime($item['date_reported']))) ?></p>
                <div class="actions"><a class="btn secondary" href="<?= e($details) ?>">View details</a><?php if($item['status']!=='claimed'): ?><a class="btn" href="public/claim.php?id=<?= (int)$item['id'] ?>"><?= $item['status']==='found' ? 'Claim Item' : 'I Found This' ?></a><?php endif ?></div>
            </div>
        </article>
    <?php endwhile; endif ?>
    </div>
    <?php if($pages>1): ?><div class="pagination" aria-label="Item pages"><?php if($page>1): ?><a class="btn secondary" href="?<?= e(http_build_query(['q'=>$q,'category'=>$category,'status'=>$status,'page'=>$page-1])) ?>">Previous</a><?php endif ?><span>Page <?= $page ?> of <?= $pages ?></span><?php if($page<$pages): ?><a class="btn secondary" href="?<?= e(http_build_query(['q'=>$q,'category'=>$category,'status'=>$status,'page'=>$page+1])) ?>">Next</a><?php endif ?></div><?php endif ?>
</main>
<?php require __DIR__.'/includes/footer.php'; ?>
