<?php
ini_set('display_errors', 0);
error_reporting(0);
// NOTE: CORS headers are set in root index.php — not here
// This file only contains helper functions

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function respondError($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

function getBody() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function requireAuth() {
    $token = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? $_GET['token'] ?? '';
    if (!$token) respondError('Unauthorized — please log in.', 401);

    $db   = getDB();
    $stmt = $db->prepare("SELECT id, username, role, name, initials, section, usn FROM users WHERE auth_token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user) respondError('Session expired — please log in again.', 401);
    return $user;
}

function requireRole(...$roles) {
    $user = requireAuth();
    if (!in_array($user['role'], $roles)) respondError('You do not have permission.', 403);
    return $user;
}
