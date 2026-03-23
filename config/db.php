<?php
ini_set('display_errors', 0);
error_reporting(0);

function getDB() {
    $url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: null;

    if ($url) {
        $parts = parse_url($url);
        $host  = $parts['host']              ?? '';
        $port  = (int)($parts['port']        ?? 3306);
        $user  = $parts['user']              ?? '';
        $pass  = isset($parts['pass']) ? urldecode($parts['pass']) : '';
        $name  = ltrim($parts['path'] ?? '', '/');
    } else {
        $host  = getenv('MYSQLHOST')     ?: 'localhost';
        $port  = (int)(getenv('MYSQLPORT')     ?: 3306);
        $user  = getenv('MYSQLUSER')     ?: 'root';
        $pass  = getenv('MYSQLPASSWORD') ?: '';
        $name  = getenv('MYSQLDATABASE') ?: 'railway';
    }

    if (!$host || !$user || !$name) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Missing DB config — check Railway environment variables.', 'host_set' => !empty($host), 'user_set' => !empty($user), 'name_set' => !empty($name)]);
        exit;
    }

    $conn = @new mysqli($host, $user, $pass, $name, $port);

    if ($conn->connect_error) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }

    $conn->set_charset('utf8mb4');
    return $conn;
}

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
