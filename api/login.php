<?php
require_once '../config/cors.php';
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respondError('Method not allowed.', 405);

$body     = getBody();
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');
$role     = trim($body['role']     ?? '');

if (!$username || !$password || !$role) respondError('All fields are required.');
if (!in_array($role, ['admin','teacher','student'])) respondError('Invalid role.');

$db = getDB();

$stmt = $db->prepare("SELECT id, username, password, role, name, initials, section, usn FROM users WHERE username = ? AND role = ? LIMIT 1");
$stmt->bind_param('ss', $username, $role);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) respondError('Incorrect username or password.', 401);

$ok = password_verify($password, $user['password']) || $password === $user['password'];
if (!$ok) respondError('Incorrect username or password.', 401);

// Generate token and save to DB
$token = bin2hex(random_bytes(32));
$upd   = $db->prepare("UPDATE users SET auth_token = ? WHERE id = ?");
$upd->bind_param('si', $token, $user['id']);
$upd->execute();
$upd->close();
$db->close();

respond([
    'success' => true,
    'token'   => $token,
    'user'    => [
        'id'           => $user['id'],
        'name'         => $user['name'],
        'role'         => $user['role'],
        'initials'     => $user['initials'],
        'section_name' => $user['section'],
        'section'      => $user['section'],
        'usn'          => $user['usn'],
    ]
]);
