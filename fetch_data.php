<?php
require 'config.php';

header('Content-Type: application/json');

$db = getDB();

$latest = $db->query("SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$readings = $db->query("SELECT * FROM sensor_readings ORDER BY recorded_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
$readings = array_reverse($readings);
$total = $db->query("SELECT COUNT(*) FROM sensor_readings")->fetchColumn();

if ($latest) {
    $latest['time'] = date('h:i:s A', strtotime($latest['recorded_at']));
    $latest['date'] = date('M d, Y', strtotime($latest['recorded_at']));
}

foreach ($readings as &$r) {
    $r['time_short'] = date('H:i:s', strtotime($r['recorded_at']));
    $r['recorded_at_formatted'] = date('M d, h:i:s A', strtotime($r['recorded_at']));
}

echo json_encode([
    'latest'   => $latest,
    'readings' => $readings,
    'total'    => $total
]);
