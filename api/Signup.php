<?php
ini_set('display_errors', 0);
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Method not allowed']); exit;
}

require_once __DIR__ . '/../config/db.php';

$body     = json_decode(file_get_contents('php://input'), true) ?? [];
$role     = trim($body['role']     ?? '');
$name     = trim($body['name']     ?? '');
$username = trim($body['username'] ?? '');
$password = trim($body['password'] ?? '');
$usn      = trim($body['usn']      ?? '');
$section  = trim($body['section']  ?? '');

if (!$role || !$name || !$username || !$password) {
    echo json_encode(['error' => 'All fields are required.']); exit;
}
if (!in_array($role, ['admin','teacher','student'])) {
    echo json_encode(['error' => 'Invalid role.']); exit;
}
if (strlen($password) < 6) {
    echo json_encode(['error' => 'Password must be at least 6 characters.']); exit;
}

$parts    = explode(' ', $name);
$initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr(end($parts), 0, 1));

$db = getDB();

$check = $db->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
$check->bind_param('s', $username);
$check->execute();
if ($check->get_result()->fetch_assoc()) {
    $check->close();
    echo json_encode(['error' => 'Username already taken.']); exit;
}
$check->close();

$hashed = password_hash($password, PASSWORD_DEFAULT);
$secVal = in_array($role, ['student','teacher']) ? $section : null;
$usnVal = $role === 'student' ? $usn : null;

$stmt = $db->prepare("INSERT INTO users (username, password, role, name, initials, section, usn) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param('sssssss', $username, $hashed, $role, $name, $initials, $secVal, $usnVal);
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Failed to create account.']); exit;
}
$stmt->close();
$db->close();

echo json_encode(['success' => true, 'message' => 'Account created!']);
