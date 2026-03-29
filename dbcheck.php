<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: null;
if ($url) {
    $p    = parse_url($url);
    $host = $p['host'];
    $port = (int)($p['port'] ?? 3306);
    $user = $p['user'];
    $pass = urldecode($p['pass'] ?? '');
    $name = ltrim($p['path'], '/');
} else {
    $host = getenv('MYSQLHOST')     ?: 'localhost';
    $port = (int)(getenv('MYSQLPORT') ?: 3306);
    $user = getenv('MYSQLUSER')     ?: 'root';
    $pass = getenv('MYSQLPASSWORD') ?: '';
    $name = getenv('MYSQLDATABASE') ?: 'railway';
}

$db = @new mysqli($host, $user, $pass, $name, $port);
if ($db->connect_error) {
    echo json_encode(['error' => $db->connect_error]); exit;
}

// Add auth_token column
$db->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_token VARCHAR(64) DEFAULT NULL");

// Check columns in users table
$result = $db->query("DESCRIBE users");
$cols = [];
while ($row = $result->fetch_assoc()) $cols[] = $row['Field'];

echo json_encode([
    'status'       => 'ok',
    'users_columns'=> $cols,
    'auth_token_exists' => in_array('auth_token', $cols)
]);
