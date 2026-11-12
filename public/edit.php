<?php
require_once dirname(__DIR__).'/includes/app.php';
require_admin();

$item_id = (int)($_GET['id'] ?? 0);
$item = null;
$notice = '';

// Fetch the item to edit
if ($item_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM items WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res->fetch_assoc();
    $stmt->close();

    if (!$item) {
        $notice = "Item not found.";
    }
} else {
    $notice = "No item ID provided.";
}

// Handle POST (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $item) {
    verify_post();
    try {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $item['status']; // Claim status changes only through verification.
    $location = trim($_POST['location'] ?? '');
    $date_reported = $_POST['date_reported'] ?? date('Y-m-d');
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $image_path = $item['image_path'];
    $category=field($_POST,'category');$contact_email=field($_POST,'contact_email');$contact_phone=field($_POST,'contact_phone');
    if(!in_array($category,CATEGORIES,true) || ($contact_email && (strlen($contact_email)>100 || !filter_var($contact_email,FILTER_VALIDATE_EMAIL))) || strlen($contact_phone)>20) throw new RuntimeException('Check the category and contact information.');

    if (!$title || mb_strlen($title)>150 || mb_strlen($description)<10 || mb_strlen($description)>5000 || !$location || mb_strlen($location)>150 || !$reporter_name || mb_strlen($reporter_name)>100 || !valid_date($date_reported) || !in_array($status,['lost','found','claimed'],true)) throw new RuntimeException('Check the required fields, date, and length limits.');
    $replacement=upload_image();
    $reporter_id=(int)$item['reporter_id'];
    if ($replacement) $image_path=$replacement;
    if ($title && $description && in_array($status, ['lost', 'found', 'claimed']) && $location && $date_reported && $reporter_id > 0) {
        $stmt = $conn->prepare("UPDATE items SET title=?, description=?, status=?, location=?, date_reported=?, image_path=?, reporter_id=?, category=?, contact_email=?, contact_phone=? WHERE id=?");
        $stmt->bind_param("ssssssisssi", $title, $description, $status, $location, $date_reported, $image_path, $reporter_id, $category, $contact_email, $contact_phone, $item_id);
        $stmt->execute();
        $stmt->close();
        if ($replacement && $item['image_path'] && basename($item['image_path'])===$item['image_path']) @unlink(dirname(__DIR__).'/uploads/'.$item['image_path']);
        header("Location: ../admin.php");
        exit;
    } else {
        $notice = "Please fill in all required fields.";
    }
    } catch(Throwable $ex) {
        if (!empty($replacement)) @unlink(dirname(__DIR__).'/uploads/'.$replacement);
        $notice=$ex instanceof mysqli_sql_exception ? 'Unable to update the report.' : $ex->getMessage();
        if($ex instanceof mysqli_sql_exception) error_log((string)$ex);
    }
}

// Fetch reporter name
$reporter_name = '';
if ($item) {
    $stmt = $conn->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $item['reporter_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $reporter_name = $row['name'];
    }
    $stmt->close();
}
?>
<?php $prefix='../';$title='Edit campus item';require dirname(__DIR__).'/includes/header.php'; ?>
    <main class="container form-page">
        <h2>Edit Item</h2>

        <?php if ($notice): ?>
            <div class="notice" style="background:#fdecea;border-color:#f5c2c2;color:#721c24;">
                <?php echo htmlspecialchars($notice); ?>
            </div>
        <?php endif; ?>

        <?php if ($item): ?>
            <form method="post" enctype="multipart/form-data" class="form-card"><?= csrf_field() ?>
                <label>Status</label>
                <select name="status" disabled>
                    <option value="lost" <?php if ($item['status'] === 'lost') echo 'selected'; ?>>Lost</option>
                    <option value="found" <?php if ($item['status'] === 'found') echo 'selected'; ?>>Found</option>
                    <option value="claimed" <?php if ($item['status'] === 'claimed') echo 'selected'; ?>>Claimed</option>
                </select>

                <label for="edit-category">Category</label><select id="edit-category" name="category" required><?php foreach(CATEGORIES as $c): ?><option value="<?= e($c) ?>" <?= $item['category']===$c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach ?></select>
                <label>Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>

                <label>Description</label>
                <textarea name="description" required><?php echo htmlspecialchars($item['description']); ?></textarea>

                <label>Location</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" required>

                <label>Date Reported</label>
                <input type="date" name="date_reported" value="<?php echo $item['date_reported']; ?>" required>

                <label>Student Name (Reporter)</label>
                <input type="text" name="reporter_name" readonly value="<?php echo htmlspecialchars($reporter_name); ?>" required>

                <label for="edit-email">Contact email</label><input id="edit-email" type="email" name="contact_email" maxlength="100" value="<?= e($item['contact_email']) ?>">
                <label for="edit-phone">Contact phone</label><input id="edit-phone" name="contact_phone" maxlength="20" value="<?= e($item['contact_phone']) ?>">
                <label>Image (optional — leave blank to keep current)</label>
                <?php if ($item['image_path']): ?>
                    <p style="font-size:0.9rem;color:var(--muted);">Current image: <?php echo htmlspecialchars($item['image_path']); ?></p>
                <?php endif; ?>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp">

                <div style="margin-top:0.8rem; display:flex; gap:0.6rem;">
                    <button class="btn" type="submit">Update</button>
                    <a class="btn secondary" href="../index.php">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>Unable to load item. <a href="../index.php">Go back to home.</a></p>
        <?php endif; ?>
    </main>

<?php require dirname(__DIR__).'/includes/footer.php'; ?>
