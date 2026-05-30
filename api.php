<?php
require 'config.php';
require 'mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) $input = $_POST;

if (!isset($input['soil_moisture']) || !isset($input['moisture_percent']) || !isset($input['pump_status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$db = getDB();

$soilMoisture    = intval($input['soil_moisture']);
$moisturePercent = floatval($input['moisture_percent']);
$pumpStatus      = strtoupper(trim($input['pump_status']));
$temperature     = isset($input['temperature']) ? floatval($input['temperature']) : null;
$humidity        = isset($input['humidity']) ? floatval($input['humidity']) : null;

// Save to database
$stmt = $db->prepare("INSERT INTO sensor_readings 
    (soil_moisture, moisture_percent, pump_status, temperature, humidity) 
    VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$soilMoisture, $moisturePercent, $pumpStatus, $temperature, $humidity]);

// ===== EMAIL ALERT LOGIC =====
// Get last 2 records to detect pump status change
$prev = $db->query("SELECT pump_status FROM sensor_readings ORDER BY recorded_at DESC LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);

$currentStatus  = isset($prev[0]) ? strtoupper(trim($prev[0]['pump_status'])) : '';
$previousStatus = isset($prev[1]) ? strtoupper(trim($prev[1]['pump_status'])) : '';

// Only send email when pump status CHANGES
$emailSent = false;
if ($currentStatus !== $previousStatus) {
    date_default_timezone_set('Asia/Manila');
    $timestamp = date('m-d-Y h:i A');
    $emailSent = sendEmailAlert($currentStatus, $moisturePercent, $timestamp);
}

echo json_encode([
    'success'    => true,
    'message'    => 'Data saved',
    'id'         => $db->lastInsertId(),
    'email_sent' => $emailSent
]);
