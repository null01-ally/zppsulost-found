<?php
require_once 'db.php';

// Capture filters
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$sql = "SELECT items.*, users.name AS reporter_name
        FROM items
        JOIN users ON users.id = items.reporter_id";
$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = "(items.title LIKE ? OR items.description LIKE ? OR items.location LIKE ?)";
    $like = "%$q%";
    $params = array_merge($params, [$like, $like, $like]);
    $types .= 'sss';
}
if (in_array($status, ['lost', 'found', 'claimed'])) {
    $where[] = "items.status = ?";
    $params[] = $status;
    $types .= 's';
}
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY items.created_at DESC LIMIT 24";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost & Found System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <img src="/FDSystem/public/image/zppsu-logo.png" alt="ZPPSU Logo" class="header-logo">
        <div style="width:100%">
            <h1>ZPPSU Lost &amp; Found Corner</h1>
            <p>HELPING STUDENTS RECOVER THEIR BELONGING</p>
        </div>
    </header>
    <nav>
        <a href="index.php">Home</a>
        <a href="public/contact.php">Contact</a>
        <a href="public/about.php">About Us</a>
    </nav>

    <div class="container">
        <div class="search-block">
            <form method="get" action="index.php">
                <div class="search-row">
                    <input type="text" name="q" class="search-input" placeholder="Search title, description, location..." value="<?php echo htmlspecialchars($q); ?>">
                    <button type="submit" class="search-btn">Search</button>
                </div>
            </form>

            <a href="public/create.php" class="report-btn">+ Report Item</a>
        </div>

        <div class="grid">
            <?php if ($res->num_rows === 0): ?>
                <div class="empty">No items found.</div>
                <?php else: while ($row = $res->fetch_assoc()):
                    $img = $row['image_path'] ? 'uploads/' . htmlspecialchars($row['image_path'])
                        : 'https://via.placeholder.com/600x400?text=No+Image';
                    $statusClass = $row['status'] === 'claimed' ? 'status claimed' : 'status';
                    $descPreview = mb_substr($row['description'], 0, 160) . (mb_strlen($row['description']) > 160 ? '...' : '');
                ?>
                    <div class="card">
                        <img src="<?php echo $img; ?>" alt="Item">
                        <div class="content">
                            <div class="<?php echo $statusClass; ?>">Status: <?php echo strtoupper(htmlspecialchars($row['status'])); ?></div>
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p><?php echo htmlspecialchars($descPreview); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                            <p><strong>Reporter:</strong> <?php echo htmlspecialchars($row['reporter_name']); ?></p>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($row['date_reported']); ?></p>
                            <div class="actions">
                                <a class="btn" href="public/view.php?id=<?php echo $row['id']; ?>">View</a>
                                <a class="btn secondary" href="public/edit.php?id=<?php echo $row['id']; ?>" style="font-size:0.9rem;padding:0.4rem 0.7rem;">Edit</a>
                                <a class="btn danger" href="public/delete.php?id=<?php echo $row['id']; ?>" style="font-size:0.9rem;padding:0.4rem 0.7rem;" onclick="return confirm('Delete this item?');">Delete</a>
                            </div>
                        </div>
                    </div>
            <?php endwhile;
            endif; ?>
        </div>
    </div>

    <footer>
        <p>© The Lost Group</p>
    </footer>
</body>

</html>