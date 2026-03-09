<?php
require_once '../config/cors.php';
require_once '../config/db.php';

$user   = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── POST: Save attendance ─────────────────────────────────────────────
if ($method === 'POST') {
    if (!in_array($user['role'], ['admin','teacher'])) respondError('Forbidden', 403);
    $body    = getBody();
    $records = $body['records'] ?? [];
    $date    = $body['date']    ?? date('Y-m-d');
    if (empty($records)) respondError('No records provided.');

    $stmt = $db->prepare("INSERT INTO attendance (usn, scanned_at, attendance_date, remarks) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE scanned_at=VALUES(scanned_at), remarks=VALUES(remarks)");
    $saved = 0;
    foreach ($records as $rec) {
        $usn        = $rec['usn']             ?? '';
        $scanned_at = $rec['scanned_at']      ?? null;
        $att_date   = $rec['attendance_date'] ?? $date;
        $remarks    = $rec['remarks']         ?? null;
        if (!$usn) continue;
        $stmt->bind_param('ssss', $usn, $scanned_at, $att_date, $remarks);
        if ($stmt->execute()) $saved++;
    }
    $stmt->close();
    respond(['success' => true, 'saved' => $saved]);
}

// ── GET: Student attendance history ──────────────────────────────────
if ($method === 'GET' && isset($_GET['usn'])) {
    $usn  = $_GET['usn'];
    $days = min(intval($_GET['days'] ?? 30), 365);
    if ($user['role'] === 'student' && $user['usn'] !== $usn) respondError('Access denied.', 403);

    $stmt = $db->prepare("SELECT attendance_date AS date, scanned_at, remarks FROM attendance WHERE usn = ? ORDER BY attendance_date DESC LIMIT ?");
    $stmt->bind_param('si', $usn, $days);
    $stmt->execute();
    $result  = $stmt->get_result();
    $history = [];
    while ($row = $result->fetch_assoc()) {
        // Determine status from remarks/scanned_at
        $r = strtolower($row['remarks'] ?? '');
        if (!$row['remarks'] && !$row['scanned_at']) $row['status'] = 'absent';
        elseif (str_contains($r, 'tardy') || str_contains($r, 'late')) $row['status'] = 'late';
        else $row['status'] = 'present';
        $history[] = $row;
    }
    $stmt->close();

    $present = count(array_filter($history, fn($r) => $r['status']==='present'));
    $absent  = count(array_filter($history, fn($r) => $r['status']==='absent'));
    $late    = count(array_filter($history, fn($r) => $r['status']==='late'));
    $total   = count($history);
    $rate    = $total > 0 ? round(($present/$total)*100) : 0;
    respond(['success'=>true,'history'=>$history,'summary'=>compact('present','absent','late','total','rate')]);
}

// ── GET: Section stats ────────────────────────────────────────────────
if ($method === 'GET' && isset($_GET['section'])) {
    $section = $_GET['section'];
    $date    = $_GET['date'] ?? date('Y-m-d');

    $stmt = $db->prepare("
        SELECT
          COUNT(st.usn) AS total,
          SUM(CASE WHEN a.remarks IS NOT NULL AND a.remarks != '' AND a.remarks NOT LIKE '%tardy%' AND a.remarks NOT LIKE '%late%' THEN 1 ELSE 0 END) AS present,
          SUM(CASE WHEN a.usn IS NULL OR (a.remarks IS NULL AND a.scanned_at IS NULL) THEN 1 ELSE 0 END) AS absent,
          SUM(CASE WHEN a.remarks LIKE '%tardy%' OR a.remarks LIKE '%late%' THEN 1 ELSE 0 END) AS late
        FROM students st
        LEFT JOIN attendance a ON a.usn=st.usn AND a.attendance_date=?
        WHERE st.section=?
    ");
    $stmt->bind_param('ss', $date, $section);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    respond(['success'=>true,'stats'=>$stats,'date'=>$date]);
}

respondError('Invalid request.');
