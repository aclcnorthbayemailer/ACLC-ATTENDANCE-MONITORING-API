<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../config/cors.php';

$user   = requireAuth();
$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// ── POST: Add new student ─────────────────────────────────────────────
if ($method === 'POST') {
    if ($user['role'] !== 'admin') respondError('Only admins can add students.', 403);
    $body       = getBody();
    $last_name  = trim($body['last_name']      ?? '');
    $first_name = trim($body['first_name']     ?? '');
    $middle     = trim($body['middle_name']    ?? '');
    $age        = trim($body['age']            ?? '');
    $sex        = trim($body['sex']            ?? '');
    $usn        = trim($body['usn']            ?? '');
    $lrn        = trim($body['lrn']            ?? '');
    $section    = trim($body['section']        ?? '');
    $email      = trim($body['guardian_email'] ?? '');

    if (!$last_name || !$first_name || !$usn || !$section) respondError('Last name, first name, USN, and section are required.');

    $stmt = $db->prepare("INSERT INTO students (last_name, first_name, middle_name, age, sex, usn, lrn, section, guardian_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('sssssssss', $last_name, $first_name, $middle, $age, $sex, $usn, $lrn, $section, $email);
    if (!$stmt->execute()) respondError('Failed to add student. USN may already exist: ' . $db->error);
    $stmt->close();
    respond(['success' => true, 'message' => 'Student added successfully.']);
}

// ── GET all students ──────────────────────────────────────────────────
if (isset($_GET['all'])) {
    if ($user['role'] === 'teacher') {
        $stmt = $db->prepare("SELECT usn, first_name, last_name, middle_name, age, sex, lrn, section, guardian_email, CONCAT(last_name, ', ', first_name, IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', LEFT(middle_name,1), '.'), '')) AS full_name FROM students WHERE section = ? ORDER BY last_name ASC, first_name ASC");
        $stmt->bind_param('s', $user['section']);
    } else {
        $stmt = $db->prepare("SELECT usn, first_name, last_name, middle_name, age, sex, lrn, section, guardian_email, CONCAT(last_name, ', ', first_name, IF(middle_name IS NOT NULL AND middle_name != '', CONCAT(' ', LEFT(middle_name,1), '.'), '')) AS full_name FROM students ORDER BY section ASC, last_name ASC, first_name ASC");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    while ($row = $result->fetch_assoc()) $students[] = $row;
    $stmt->close();
    respond(['success' => true, 'students' => $students, 'count' => count($students)]);
}

// ── GET single student ────────────────────────────────────────────────
if (isset($_GET['usn'])) {
    $usn = $_GET['usn'];
    if ($user['role'] === 'student' && $user['usn'] !== $usn) respondError('Access denied.', 403);
    $stmt = $db->prepare("SELECT usn, first_name, last_name, middle_name, age, sex, lrn, section, guardian_email FROM students WHERE usn = ? LIMIT 1");
    $stmt->bind_param('s', $usn);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$student) respondError('Student not found.', 404);
    respond(['success' => true, 'student' => $student]);
}

// ── GET students for attendance table ─────────────────────────────────
$section = $_GET['section'] ?? '';
$date    = $_GET['date']    ?? date('Y-m-d');
if (!$section) respondError('section is required.');
if ($user['role'] === 'teacher' && $user['section'] !== $section) respondError('Access denied.', 403);

$stmt = $db->prepare("
    SELECT st.usn, st.last_name, st.first_name, st.middle_name, st.sex, st.age, st.lrn, st.section,
        CONCAT(st.last_name, ', ', st.first_name, IF(st.middle_name IS NOT NULL AND st.middle_name != '', CONCAT(' ', LEFT(st.middle_name,1), '.'), '')) AS full_name,
        a.scanned_at, COALESCE(a.remarks, '') AS remarks
    FROM students st
    LEFT JOIN attendance a ON a.usn = st.usn AND a.attendance_date = ?
    WHERE st.section = ?
    ORDER BY st.last_name ASC, st.first_name ASC
");
$stmt->bind_param('ss', $date, $section);
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) $students[] = $row;
$stmt->close();
respond(['success' => true, 'students' => $students, 'section' => $section, 'date' => $date, 'count' => count($students)]);
