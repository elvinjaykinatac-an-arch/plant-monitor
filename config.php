<?php
define('DB_HOST', getenv('MYSQLHOST') ?: 'mysql.railway.internal');
define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');
define('DB_USER', getenv('MYSQLUSER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: 'cczjDiTfrcCCKmRDQUhhMhpmmUyZPfzv');

function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die(json_encode(['error' => 'DB failed: ' . $e->getMessage()]));
    }
}

session_start();
