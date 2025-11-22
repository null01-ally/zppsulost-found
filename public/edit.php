<?php
require_once '../db.php';

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
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'lost';
    $location = trim($_POST['location'] ?? '');
    $date_reported = $_POST['date_reported'] ?? date('Y-m-d');
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $image_path = $item['image_path'];

    // Update reporter if name provided
    if ($reporter_name) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $reporter_name);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $reporter_id = $user['id'];
        } else {
            $stmt2 = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $email = strtolower(str_replace(' ', '.', $reporter_name)) . '@zppsu.edu.ph';
            $stmt2->bind_param("ss", $reporter_name, $email);
            $stmt2->execute();
            $reporter_id = $stmt2->insert_id;
            $stmt2->close();
        }
        $stmt->close();
    } else {
        $reporter_id = $item['reporter_id'];
    }

    // Handle new image upload
    if (!empty($_FILES['image']['name'])) {
        if ($item['image_path'] && file_exists("../uploads/" . $item['image_path'])) {
            @unlink("../uploads/" . $item['image_path']);
        }
        $image_path = time() . "_" . basename($_FILES['image']['name']);
        @mkdir("../uploads", 0775, true);
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image_path);
    }

    if ($title && $description && in_array($status, ['lost', 'found', 'claimed']) && $location && $date_reported && $reporter_id > 0) {
        $stmt = $conn->prepare("UPDATE items SET title=?, description=?, status=?, location=?, date_reported=?, image_path=?, reporter_id=? WHERE id=?");
        $stmt->bind_param("ssssssii", $title, $description, $status, $location, $date_reported, $image_path, $reporter_id, $item_id);
        $stmt->execute();
        $stmt->close();
        header("Location: ../index.php");
        exit;
    } else {
        $notice = "Please fill in all required fields.";
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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Edit Item</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <header>
        <img src="/FDSystem/public/image/zppsu-logo.png" alt="ZPPSU Logo" class="header-logo">
        <div style="width:100%">
            <h1>ZPPSU Lost & Found Corner</h1>
            <p>HELPING STUDENTS RECOVER THEIR BELONGING</p>
        </div>
    </header>
    <nav>
        <a href="../index.php">Home</a>
        <a href="contact.php">Contact</a>
        <a href="about.php">About Us</a>
    </nav>

    <div class="container">
        <h2>Edit Item</h2>

        <?php if ($notice && !$item): ?>
            <div class="notice" style="background:#fdecea;border-color:#f5c2c2;color:#721c24;">
                <?php echo htmlspecialchars($notice); ?>
            </div>
        <?php endif; ?>

        <?php if ($item): ?>
            <form method="post" enctype="multipart/form-data">
                <label>Status</label>
                <select name="status" required>
                    <option value="lost" <?php if ($item['status'] === 'lost') echo 'selected'; ?>>Lost</option>
                    <option value="found" <?php if ($item['status'] === 'found') echo 'selected'; ?>>Found</option>
                    <option value="claimed" <?php if ($item['status'] === 'claimed') echo 'selected'; ?>>Claimed</option>
                </select>

                <label>Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>

                <label>Description</label>
                <textarea name="description" required><?php echo htmlspecialchars($item['description']); ?></textarea>

                <label>Location</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($item['location']); ?>" required>

                <label>Date Reported</label>
                <input type="date" name="date_reported" value="<?php echo $item['date_reported']; ?>" required>

                <label>Student Name (Reporter)</label>
                <input type="text" name="reporter_name" value="<?php echo htmlspecialchars($reporter_name); ?>" required>

                <label>Image (optional — leave blank to keep current)</label>
                <?php if ($item['image_path']): ?>
                    <p style="font-size:0.9rem;color:var(--muted);">Current image: <?php echo htmlspecialchars($item['image_path']); ?></p>
                <?php endif; ?>
                <input type="file" name="image">

                <div style="margin-top:0.8rem; display:flex; gap:0.6rem;">
                    <button class="btn" type="submit">Update</button>
                    <a class="btn secondary" href="../index.php">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <p>Unable to load item. <a href="../index.php">Go back to home.</a></p>
        <?php endif; ?>
    </div>

    <footer>
        <p>© The Lost Group</p>
    </footer>
</body>

</html>