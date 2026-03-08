<?php
/**
 * config/db.php
 * Reads Railway MySQL environment variables automatically.
 */

$host = getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost';
$port = getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306';
$user = getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root';
$pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway';

define('DB_HOST', $host);
define('DB_PORT', $port);
define('DB_USER', $user);
define('DB_PASS', $pass);
define('DB_NAME', $name);

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
