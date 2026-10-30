<?php
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'root', '', 'forest_watch');
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed: ' . $conn->connect_error]);
    exit;
}

// Get POST data
$violation_type = $_POST['violation_type'] ?? '';
$description = $_POST['description'] ?? '';
$latitude = $_POST['latitude'] ?? '';
$longitude = $_POST['longitude'] ?? '';

// Validate
if (!$violation_type || !$latitude || !$longitude) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// ✅ Generate ONE tracking ID for everything
$tracking_id = 'FR-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

// Handle photo upload using the SAME tracking ID
$photo_filename = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (in_array($ext, $allowed)) {
        $photo_filename = $tracking_id . '_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_filename);
    }
}

// ✅ Insert using the SAME tracking ID (no separate generation inside the query)
$sql = "INSERT INTO reports (tracking_id, violation_type, description, latitude, longitude, photo_path, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssdds", $tracking_id, $violation_type, $description, $latitude, $longitude, $photo_filename);

if ($stmt->execute()) {
    // ✅ Return the SAME tracking ID (the one actually inserted)
    echo json_encode(['success' => true, 'tracking_id' => $tracking_id]);
} else {
    echo json_encode(['error' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>