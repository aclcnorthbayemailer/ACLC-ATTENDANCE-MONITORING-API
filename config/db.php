<?php

function getDB() {
    // Try parsing from full URL first
    $url = getenv('MYSQL_URL') ?: getenv('DATABASE_URL') ?: getenv('MYSQL_PUBLIC_URL') ?: null;

    if ($url) {
        $parts = parse_url($url);
        $host  = $parts['host'];
        $port  = $parts['port'] ?? 3306;
        $user  = $parts['user'];
        $pass  = isset($parts['pass']) ? urldecode($parts['pass']) : '';
        $name  = ltrim($parts['path'], '/');
    } else {
        // Fallback to individual env vars
        $host  = getenv('MYSQLHOST')     ?: 'localhost';
        $port  = getenv('MYSQLPORT')     ?: 3306;
        $user  = getenv('MYSQLUSER')     ?: 'root';
        $pass  = getenv('MYSQLPASSWORD') ?: '';
        $name  = getenv('MYSQLDATABASE') ?: 'railway';
    }

    $conn = new mysqli($host, $user, $pass, $name, (int)$port);
    if ($conn->connect_error) {
        http_response_code(500);
        echo json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]);
        exit;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

