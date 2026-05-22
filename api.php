<?php
require 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

if (!isset($input['soil_moisture']) || !isset($input['moisture_percent']) || !isset($input['pump_status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$db = getDB();

$stmt = $db->prepare("INSERT INTO sensor_readings 
    (soil_moisture, moisture_percent, pump_status, temperature, humidity) 
    VALUES (?, ?, ?, ?, ?)");

$stmt->execute([
    intval($input['soil_moisture']),
    floatval($input['moisture_percent']),
    $input['pump_status'],
    isset($input['temperature']) ? floatval($input['temperature']) : null,
    isset($input['humidity']) ? floatval($input['humidity']) : null
]);

echo json_encode(['success' => true, 'message' => 'Data saved', 'id' => $db->lastInsertId()]);