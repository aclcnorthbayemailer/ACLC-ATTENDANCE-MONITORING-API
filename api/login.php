<?php
ini_set('display_errors', 0);
error_reporting(0);

// CORS
$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['https://aclc-attendance-monitoring-web.vercel.app','http://localhost','http://127.0.0.1'];
header("Access-Control-Allow-Origin: " . (in_array($origin, $allowed) ? $origin : $allowed[0]));
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Requested-With");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST')    { echo json_encode(['error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/../config/db.php';

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');
$role     = trim($body['role']     ?? '');

if (!$username || !$password || !$role) {
    echo json_encode(['error' => 'All fields are required.']); exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, username, password, role, name, initials, section, usn FROM users WHERE username = ? AND role = ? LIMIT 1");
$stmt->bind_param('ss', $username, $role);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Incorrect username or password.']); exit;
}

// Support both hashed and plain-text passwords
$ok = password_verify($password, $user['password']) || ($password === $user['password']);
if (!$ok) {
    http_response_code(401);
    echo json_encode(['error' => 'Incorrect username or password.']); exit;
}

// Generate token safely — avoid random_bytes on older PHP
$token = md5(uniqid($username . time(), true)) . md5(uniqid($role . rand(), true));

$upd = $db->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
$upd->bind_param('si', $token, $user['id']);
$upd->execute();
$upd->close();
$db->close();

echo json_encode([
    'success' => true,
    'token'   => $token,
    'user'    => [
        'id'           => (int)$user['id'],
        'name'         => $user['name'],
        'role'         => $user['role'],
        'initials'     => $user['initials'],
        'section_name' => $user['section'],
        'section'      => $user['section'],
        'usn'          => $user['usn'],
    ]
]);
