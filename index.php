<?php
// index.php — Entry point for Railway PHP server
// Routes requests to the correct files

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = ltrim($uri, '/');

// Route to setup.php
if ($uri === 'setup.php') {
    require __DIR__ . '/setup.php';
    exit;
}

// Route api/* requests
if (strpos($uri, 'api/') === 0) {
    $file = __DIR__ . '/' . $uri;
    if (file_exists($file)) {
        require $file;
        exit;
    }
}

// Default response
header('Content-Type: application/json');
echo json_encode(['status' => 'AttendEase API is running!']);
