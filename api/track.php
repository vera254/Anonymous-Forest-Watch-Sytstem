<?php
header('Content-Type: application/json');
$conn = new mysqli('localhost', 'root', '', 'forest_watch');
if ($conn->connect_error) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}
$tracking_id = $_GET['id'] ?? '';
if (!$tracking_id) {
    echo json_encode(['error' => 'No tracking ID provided']);
    exit;
}
$stmt = $conn->prepare("SELECT tracking_id, status, created_at, updated_at FROM reports WHERE tracking_id = ?");
$stmt->bind_param("s", $tracking_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'id' => $row['tracking_id'],
        'status' => $row['status'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'] ?? $row['created_at']
    ]);
} else {
    echo json_encode(['error' => 'Report not found']);
}
$stmt->close();
$conn->close();
?>