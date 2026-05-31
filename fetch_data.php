<?php
require 'config.php';

header('Content-Type: application/json');

$db = getDB();

$latest = $db->query("SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$total = $db->query("SELECT COUNT(*) FROM sensor_readings")->fetchColumn();

// ===== DATE FILTER =====
$startDate = isset($_GET['start']) ? $_GET['start'] : null;
$endDate   = isset($_GET['end']) ? $_GET['end'] : null;

if ($startDate && $endDate) {
    // Filtered query
    $stmt = $db->prepare("SELECT * FROM sensor_readings 
        WHERE recorded_at >= ? AND recorded_at <= ?
        ORDER BY recorded_at DESC");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $isFiltered = true;
} else {
    // Default: last 20 readings
    $readings = $db->query("SELECT * FROM sensor_readings 
        ORDER BY recorded_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    $readings = array_reverse($readings);
    $isFiltered = false;
}

if ($latest) {
    $latest['time'] = date('h:i:s A', strtotime($latest['recorded_at']));
    $latest['date'] = date('M d, Y', strtotime($latest['recorded_at']));
}

foreach ($readings as &$r) {
    $r['time_short'] = date('H:i:s', strtotime($r['recorded_at']));
    $r['recorded_at_formatted'] = date('M d, h:i:s A', strtotime($r['recorded_at']));
}

echo json_encode([
    'latest'     => $latest,
    'readings'   => $readings,
    'total'      => $total,
    'isFiltered' => $isFiltered,
    'count'      => count($readings)
]);
