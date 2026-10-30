<?php
header('Content-Type: application/json');
require_once '../config.php';

$total = $conn->query("SELECT COUNT(*) as count FROM reports")->fetch_assoc()['count'];
$resolved = $conn->query("SELECT COUNT(*) as count FROM reports WHERE status = 'resolved'")->fetch_assoc()['count'];

echo json_encode([
    'total' => (int)$total,
    'resolved' => (int)$resolved
]);

$conn->close();
?>