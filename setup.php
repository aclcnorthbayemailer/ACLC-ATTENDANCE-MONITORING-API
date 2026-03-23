<?php
require_once 'config/db.php';
$db = getDB();
$results = [];

$sqls = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_token VARCHAR(64) DEFAULT NULL",
    "ALTER TABLE attendance ADD COLUMN IF NOT EXISTS scanned_at VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE attendance ADD COLUMN IF NOT EXISTS attendance_date DATE DEFAULT NULL",
    "UPDATE attendance SET attendance_date = date WHERE attendance_date IS NULL AND date IS NOT NULL",
];

foreach ($sqls as $sql) {
    if ($db->query($sql)) $results[] = "✅ Done: " . substr($sql, 0, 60) . "…";
    else $results[] = "⚠️ Skipped (may already exist): " . $db->error;
}
$db->close();
?>
<!DOCTYPE html><html><body style="font-family:Arial;max-width:600px;margin:60px auto;padding:20px;background:#f5f6fa">
<div style="background:white;border-radius:12px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.08)">
<h2 style="color:#003087">AttendEase — DB Update</h2>
<?php foreach ($results as $r): ?>
  <p style="margin:8px 0"><?= htmlspecialchars($r) ?></p>
<?php endforeach; ?>
<hr>
<p style="color:#16a34a;font-weight:bold">✅ Done! Delete setup.php now for security.</p>
</div></body></html>
