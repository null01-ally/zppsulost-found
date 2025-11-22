<?php
require_once '../db.php';

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify item exists and not already claimed
$stmt = $conn->prepare("SELECT id, title, status FROM items WHERE id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: ../index.php");
    exit;
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $claimer_id = (int)($_POST['claimer_id'] ?? 0);
    $claim_date = $_POST['claim_date'] ?? date('Y-m-d');

    if ($claimer_id > 0) {
        // Insert claim row
        $stmt = $conn->prepare("INSERT INTO claims (item_id, claimer_id, claim_date) VALUES (?,?,?)");
        $stmt->bind_param("iis", $item_id, $claimer_id, $claim_date);
        $stmt->execute();
        $stmt->close();

        // Update item status
        $stmt = $conn->prepare("UPDATE items SET status = 'claimed' WHERE id = ?");
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $stmt->close();

        header("Location: view.php?id={$item_id}");
        exit;
    } else {
        $notice = "Please choose a claimer.";
    }
}

$users = $conn->query("SELECT id, name FROM users ORDER BY name ASC");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Claim Item</title>
    <link rel="stylesheet" href="../style.css">
</head>

<body>
    <header>
        <h1>Mark Item as Claimed</h1>
    </header>
    <nav>
        <a href="../index.php">Home</a>
    </nav>

    <div class="container">
        <div class="card" style="padding:1rem;">
            <h2><?php echo htmlspecialchars($item['title']); ?></h2>
            <p>Status: <?php echo strtoupper($item['status']); ?></p>
        </div>

        <?php if ($notice): ?><div class="notice"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>

        <form method="post">
            <label>Claimer (user)</label>
            <select name="claimer_id" required>
                <option value="">-- Select user --</option>
                <?php if ($users && $users->num_rows): while ($u = $users->fetch_assoc()): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?></option>
                <?php endwhile;
                endif; ?>
            </select>

            <label>Claim date</label>
            <input type="date" name="claim_date" value="<?php echo date('Y-m-d'); ?>" required>

            <div style="margin-top:0.8rem; display:flex; gap:0.6rem;">
                <button class="btn" type="submit">Confirm Claim</button>
                <a class="btn secondary" href="view.php?id=<?php echo $item_id; ?>">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>