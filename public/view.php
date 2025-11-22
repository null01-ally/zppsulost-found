<?php
require_once '../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch item + reporter
$stmt = $conn->prepare("SELECT items.*, users.name AS reporter_name, users.email AS reporter_email
                        FROM items
                        JOIN users ON users.id = items.reporter_id
                        WHERE items.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res->fetch_assoc();
$stmt->close();

// Fetch claim info if already claimed
$claim = null;
if ($item) {
    $stmt = $conn->prepare("SELECT claims.*, users.name AS claimer_name, users.email AS claimer_email
                          FROM claims
                          JOIN users ON users.id = claims.claimer_id
                          WHERE claims.item_id = ?
                          ORDER BY claims.id DESC LIMIT 1");
    $stmt->bind_param("i", $item['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $claim = $res->fetch_assoc();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Item Details</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <header>
        <h1>Item Details</h1>
    </header>
    <nav>
        <a href="../index.php">Home</a>
        <a href="create.php">Report Item</a>
    </nav>

    <div class="container">
        <?php if (!$item): ?>
            <div class="notice">Item not found.</div>
        <?php else: ?>
            <div class="card">
                <img src="<?php echo $item['image_path'] ? '../uploads/' . htmlspecialchars($item['image_path']) : 'https://via.placeholder.com/600x400?text=No+Image'; ?>" alt="Item">
                <div class="content">
                    <div class="status <?php echo $item['status'] === 'claimed' ? 'claimed' : ''; ?>">
                        Status: <?php echo strtoupper(htmlspecialchars($item['status'])); ?>
                    </div>
                    <h2><?php echo htmlspecialchars($item['title']); ?></h2>
                    <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
                    <p><strong>Date reported:</strong> <?php echo htmlspecialchars($item['date_reported']); ?></p>
                    <p><strong>Reporter:</strong> <?php echo htmlspecialchars($item['reporter_name']); ?> (<?php echo htmlspecialchars($item['reporter_email']); ?>)</p>

                    <?php if ($claim): ?>
                        <p><strong>Claimed by:</strong> <?php echo htmlspecialchars($claim['claimer_name']); ?> (<?php echo htmlspecialchars($claim['claimer_email']); ?>) on <?php echo htmlspecialchars($claim['claim_date']); ?></p>
                    <?php endif; ?>

                    <div class="actions">
                        <?php if ($item['status'] !== 'claimed'): ?>
                            <a class="btn" href="claim.php?id=<?php echo $item['id']; ?>">Mark as Claimed</a>
                        <?php endif; ?>
                        <a class="btn secondary" href="../index.php">Back</a>
                        <a class="btn danger" href="delete.php?id=<?php echo $item['id']; ?>" onclick="return confirm('Delete this item?');">Delete</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>