<?php
require __DIR__.'/includes/app.php';$user=require_admin();$adminSection='settings';$title='Settings — ZPPSU';$adminLayout=true;require __DIR__.'/includes/header.php';require __DIR__.'/includes/admin_layout.php';
?>
<div class="workspace-heading"><div><span class="workspace-eyebrow">CAMPUS ADMINISTRATION</span><h2>Settings</h2><p>Your administrator account and access preferences.</p></div></div>
<div class="settings-grid"><section class="workspace-card settings-card"><span class="workspace-eyebrow">ACCOUNT</span><h3><?= e($user['name']) ?></h3><p><?= e($user['email']) ?></p><span class="admin-pill approved">Administrator</span><a class="btn secondary" href="profile.php">Edit my profile</a></section><section class="workspace-card settings-card"><span class="workspace-eyebrow">SECURITY</span><h3>Password &amp; access</h3><p>Update your password or manage who can access the campus portal.</p><a class="btn secondary" href="profile.php">Change password</a><a class="subtle-link" href="admin_users.php">Manage user access &#8594;</a></section></div>
<?php require __DIR__.'/includes/admin_footer.php'; ?>
