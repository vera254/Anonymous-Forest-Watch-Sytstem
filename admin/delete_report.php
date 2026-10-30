<?php
header('Content-Type: application/json');
require_once '../config.php';

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if admin has permission
if ($_SESSION['admin_role'] !== 'admin') {
    echo json_encode(['error' => 'Permission denied']);
    exit;
}

$report_id = intval($_POST['report_id'] ?? 0);
if (!$report_id) {
    echo json_encode(['error' => 'Invalid report ID']);
    exit;
}

// Get photo path first
$photo_stmt = $conn->prepare("SELECT photo_path FROM reports WHERE id = ?");
$photo_stmt->bind_param("i", $report_id);
$photo_stmt->execute();
$photo_result = $photo_stmt->get_result();
$photo = $photo_result->fetch_assoc();

if ($photo && $photo['photo_path']) {
    $photo_path = UPLOAD_DIR . $photo['photo_path'];
    if (file_exists($photo_path)) {
        unlink($photo_path);
    }
}

$stmt = $conn->prepare("DELETE FROM reports WHERE id = ?");
$stmt->bind_param("i", $report_id);

if ($stmt->execute()) {
    logActivity($_SESSION['admin_id'], "Deleted report", $report_id);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Database error']);
}
?>
