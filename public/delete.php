<?php
require_once '../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch image path for file deletion
$image = null;
$stmt = $conn->prepare("SELECT image_path FROM items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $image = $row['image_path'];
}
$stmt->close();

// Delete related claims (to satisfy FK)
$stmt = $conn->prepare("DELETE FROM claims WHERE item_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Delete item
$stmt = $conn->prepare("DELETE FROM items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Remove image file
if ($image && file_exists("../uploads/" . $image)) {
    @unlink("../uploads/" . $image);
}

header("Location: ../index.php");
exit;
