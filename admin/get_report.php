<?php
session_start();
header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['admin_logged_in'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'forest_watch');

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get reports
$result = $conn->query("SELECT tracking_id, violation_type, latitude, longitude, status, created_at FROM reports ORDER BY created_at DESC LIMIT 20");

$reports = [];
while ($row = $result->fetch_assoc()) {
    $reports[] = $row;
}

echo json_encode($reports);
$conn->close();
?>