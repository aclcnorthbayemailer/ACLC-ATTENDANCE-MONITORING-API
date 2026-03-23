<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Connect directly without requiring db.php
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

echo "<h2>Connecting to: $host:$port / $name as $user</h2>";

$db = new mysqli($host, $user, $pass, $name, $port);
if ($db->connect_error) {
    die("<p style='color:red'>Connection failed: " . $db->connect_error . "</p>");
}
echo "<p style='color:green'>✅ Connected!</p>";

$sqls = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_token VARCHAR(64) DEFAULT NULL",
];

foreach ($sqls as $sql) {
    if ($db->query($sql)) {
        echo "<p style='color:green'>✅ " . htmlspecialchars($sql) . "</p>";
    } else {
        echo "<p style='color:red'>❌ " . htmlspecialchars($db->error) . "</p>";
    }
}

$db->close();
echo "<h3 style='color:green'>Done! Delete setup.php now.</h3>";
