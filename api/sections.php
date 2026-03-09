<?php
ini_set('display_errors', 0);
error_reporting(0);
if (!defined('ROOT')) define('ROOT', dirname(__DIR__));
require_once ROOT . '/config/db.php';
requireAuth();
$db  = getDB();
$res = $db->query("SELECT section AS name, COUNT(*) AS student_count FROM students WHERE section IS NOT NULL AND section!='' GROUP BY section ORDER BY section ASC");
$sections = [];
while ($row = $res->fetch_assoc()) {
    preg_match('/^(\w+)\s*(\d*)/', $row['name'], $m);
    $row['strand']     = $m[1] ?? 'Other';
    $row['year_level'] = $m[2] ?? '';
    $sections[] = $row;
}
$db->close();
respond(['success' => true, 'sections' => $sections]);
