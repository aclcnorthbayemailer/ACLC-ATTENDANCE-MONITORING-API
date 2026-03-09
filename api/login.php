<?php
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

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

$ok = password_verify($password, $user['password']) || ($password === $user['password']);
if (!$ok) {
    http_response_code(401);
    echo json_encode(['error' => 'Incorrect username or password.']); exit;
}

$token = md5(uniqid('a', true)) . md5(uniqid('b', true));
$upd   = $db->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
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
