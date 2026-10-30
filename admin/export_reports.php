<?php
require_once '../config.php';

// Check if logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Get filters
$status = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];
$types = "";

if ($status !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status;
    $types .= "s";
}

if (!empty($search)) {
    $where_conditions[] = "(tracking_id LIKE ? OR violation_type LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($date_from)) {
    $where_conditions[] = "DATE(created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_clause = empty($where_conditions) ? "" : "WHERE " . implode(" AND ", $where_conditions);

$sql = "SELECT tracking_id, violation_type, description, latitude, longitude, status, created_at, updated_at 
        FROM reports $where_clause ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="forest_reports_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Tracking ID',
    'Violation Type',
    'Description',
    'Latitude',
    'Longitude',
    'Status',
    'Reported Date',
    'Last Updated'
]);

// Add data
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['tracking_id'],
        $row['violation_type'],
        $row['description'],
        $row['latitude'],
        $row['longitude'],
        $row['status'],
        $row['created_at'],
        $row['updated_at']
    ]);
}

fclose($output);
exit;
?>