<?php
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../config/db.php';

$user = requireAuth();
$db   = getDB();

$result   = $db->query("SELECT section AS name, COUNT(*) AS student_count FROM students WHERE section IS NOT NULL AND section != '' GROUP BY section ORDER BY section ASC");
$sections = [];
while ($row = $result->fetch_assoc()) {
    // Parse strand and year from section name e.g. "ICT 11-A"
    preg_match('/^(\w+)\s+(\d+)/', $row['name'], $m);
    $row['strand']     = $m[1] ?? 'Other';
    $row['year_level'] = $m[2] ?? '';
    $sections[] = $row;
}
$db->close();

echo json_encode(['success' => true, 'sections' => $sections]);
