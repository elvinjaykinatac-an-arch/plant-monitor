<?php
require 'config.php';

$db = getDB();

// Create tables
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE IF NOT EXISTS sensor_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soil_moisture INT NOT NULL,
    moisture_percent FLOAT NOT NULL,
    pump_status VARCHAR(10) NOT NULL,
    temperature FLOAT DEFAULT NULL,
    humidity FLOAT DEFAULT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert admin user
$username = 'admin';
$password = password_hash('admin2026', PASSWORD_BCRYPT);

$stmt = $db->prepare("INSERT IGNORE INTO users (username, password) VALUES (?, ?)");
$stmt->execute([$username, $password]);

echo "Setup complete! Admin user created.";