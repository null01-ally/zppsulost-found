<?php
require_once '../db.php';

$default_reporter_id = 1;

// Ensure at least one user exists
$check_user = $conn->query("SELECT id FROM users LIMIT 1");
if (!$check_user || $check_user->num_rows === 0) {
    $conn->query("INSERT INTO users (name, email) VALUES ('Student', 'student@zppsu.edu.ph')");
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'lost';
    $location = trim($_POST['location'] ?? '');
    $date_reported = $_POST['date_reported'] ?? date('Y-m-d');
    $reporter_name = trim($_POST['reporter_name'] ?? '');
    $image_path = null;

    // Create or get reporter user
    if ($reporter_name) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $reporter_name);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            $reporter_id = $user['id'];
        } else {
            // Create new user if doesn't exist
            $stmt2 = $conn->prepare("INSERT INTO users (name, email) VALUES (?, ?)");
            $email = strtolower(str_replace(' ', '.', $reporter_name)) . '@zppsu.edu.ph';
            $stmt2->bind_param("ss", $reporter_name, $email);
            $stmt2->execute();
            $reporter_id = $stmt2->insert_id;
            $stmt2->close();
        }
        $stmt->close();
    } else {
        $reporter_id = $default_reporter_id;
    }

    if (!empty($_FILES['image']['name'])) {
        $image_path = time() . "_" . basename($_FILES['image']['name']);
        @mkdir("../uploads", 0775, true);
        move_uploaded_file($_FILES['image']['tmp_name'], "../uploads/" . $image_path);
    }

    if ($title && $description && in_array($status, ['lost', 'found']) && $location && $date_reported && $reporter_id > 0) {
        $stmt = $conn->prepare("INSERT INTO items (title, description, status, location, date_reported, image_path, reporter_id) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssi", $title, $description, $status, $location, $date_reported, $image_path, $reporter_id);
        $stmt->execute();
        $stmt->close();
        header("Location: ../index.php");
        exit;
    } else {
        $notice = "Please fill in all required fields.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Report Item</title>
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
        <h2>Report Lost or Found Item</h2>
        <?php if ($notice): ?><div class="notice"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label>Status</label>
            <select name="status" required>
                <option value="lost">Lost</option>
                <option value="found">Found</option>
            </select>

            <label>Title</label>
            <input type="text" name="title" required>

            <label>Description</label>
            <textarea name="description" required></textarea>

            <label>Location</label>
            <input type="text" name="location" required>

            <label>Date Reported</label>
            <input type="date" name="date_reported" value="<?php echo date('Y-m-d'); ?>" required>

            <label>Student Name (Reporter)</label>
            <input type="text" name="reporter_name" placeholder="Enter your name" required>

            <label>Image (optional)</label>
            <input type="file" name="image">

            <div style="margin-top:0.8rem; display:flex; gap:0.6rem;">
                <button class="btn" type="submit">Submit</button>
                <a class="btn secondary" href="../index.php">Cancel</a>
            </div>
        </form>
    </div>

    <footer>
        <p>© The Lost Group</p>
    </footer>
</body>

</html>