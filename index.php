<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = [
    'https://aclc-attendance-monitoring-web.vercel.app',
    'http://localhost',
    'http://localhost:3000',
    'http://127.0.0.1',
];
$allowOrigin = in_array($origin, $allowed)
    ? $origin
    : 'https://aclc-attendance-monitoring-web.vercel.app';

// Send CORS headers immediately
header("Access-Control-Allow-Origin: $allowOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Requested-With");
header("Access-Control-Max-Age: 86400");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    http_response_code(200);
    echo '{}';
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

if ($uri === '' || $uri === 'index.php') {
    ob_end_clean();
    echo json_encode(['status' => 'ACLC Monitor API is running!', 'version' => '2.0']);
    exit;
}

if ($uri === 'setup.php') {
    ob_end_clean();
    require __DIR__ . '/setup.php';
    exit;
}

if (strpos($uri, 'api/') === 0) {
    $file = __DIR__ . '/' . $uri;
    if (file_exists($file)) {
        ob_end_clean();
        require $file;
        exit;
    }
    ob_end_clean();
    http_response_code(404);
    echo json_encode(['error' => "Not found: $uri"]);
    exit;
}

ob_end_clean();
http_response_code(404);
echo json_encode(['error' => 'Unknown route']);
