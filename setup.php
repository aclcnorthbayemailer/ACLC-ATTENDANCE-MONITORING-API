<?php
/**
 * setup.php
 * ─────────────────────────────────────────
 * Visit this file once in your browser to create
 * all the tables and sample data automatically.
 *
 * After running, DELETE this file from your repo
 * for security!
 *
 * URL: https://your-railway-api-url.up.railway.app/setup.php
 */

require_once 'config/db.php';

$db = getDB();
$results = [];
$errors  = [];

$statements = [

  // ── TABLE: students ──────────────────────────────
  "CREATE TABLE IF NOT EXISTS students (
    last_name      VARCHAR(255) NOT NULL,
    first_name     VARCHAR(255) NOT NULL,
    middle_name    VARCHAR(255),
    age            VARCHAR(10),
    sex            VARCHAR(10),
    usn            VARCHAR(255) NOT NULL UNIQUE,
    lrn            VARCHAR(255),
    section        VARCHAR(255),
    guardian_email VARCHAR(255),
    photo          LONGBLOB,
    PRIMARY KEY (usn)
  ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

  // ── TABLE: users ─────────────────────────────────
  "CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin','teacher','student') NOT NULL,
    name       VARCHAR(255) NOT NULL,
    initials   VARCHAR(10)  NOT NULL,
    section    VARCHAR(255) DEFAULT NULL,
    usn        VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usn) REFERENCES students(usn) ON DELETE SET NULL
  ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

  // ── TABLE: attendance ────────────────────────────
  "CREATE TABLE IF NOT EXISTS attendance (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usn        VARCHAR(255) NOT NULL,
    date       DATE         NOT NULL,
    status     ENUM('present','absent','late') NOT NULL DEFAULT 'absent',
    time_in    VARCHAR(10)  DEFAULT NULL,
    remarks    VARCHAR(255) DEFAULT NULL,
    marked_by  INT          DEFAULT NULL,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_usn_date (usn, date),
    FOREIGN KEY (usn)       REFERENCES students(usn) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id)     ON DELETE SET NULL
  ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",

  // ── SAMPLE STUDENTS ──────────────────────────────
  "INSERT IGNORE INTO students (last_name, first_name, middle_name, age, sex, usn, lrn, section, guardian_email) VALUES
    ('Reyes',      'Maria Clara',   'Santos',    '16', 'Female', '2024-00101', '100200300001', 'ICT 11-A', 'reyes.guardian@email.com'),
    ('Santos',     'Juan Paulo',    'Dela Cruz', '17', 'Male',   '2024-00102', '100200300002', 'ICT 11-A', 'santos.guardian@email.com'),
    ('Cruz',       'Andrea Nicole', 'Garcia',    '16', 'Female', '2024-00103', '100200300003', 'ICT 11-A', 'cruz.guardian@email.com'),
    ('Bautista',   'Carlos',        'Manuel',    '17', 'Male',   '2024-00104', '100200300004', 'ICT 11-A', 'bautista.guardian@email.com'),
    ('Garcia',     'Sophia',        'Reyes',     '16', 'Female', '2024-00105', '100200300005', 'ICT 11-A', 'garcia.guardian@email.com'),
    ('Mendoza',    'Liam Rafael',   'Torres',    '16', 'Male',   '2024-00201', '100200300011', 'ICT 11-B', 'mendoza.guardian@email.com'),
    ('Torres',     'Isabella Mae',  'Ramos',     '17', 'Female', '2024-00202', '100200300012', 'ICT 11-B', 'torres.guardian@email.com'),
    ('Flores',     'Miguel',        'Pio',       '16', 'Male',   '2024-00203', '100200300013', 'ICT 11-B', 'flores.guardian@email.com'),
    ('Villanueva', 'Marco',         'Antonio',   '18', 'Male',   '2023-00101', '100200300021', 'ICT 12-A', 'villanueva.guardian@email.com'),
    ('Castillo',   'Mia Grace',     'Lopez',     '17', 'Female', '2023-00102', '100200300022', 'ICT 12-A', 'castillo.guardian@email.com'),
    ('Dizon',      'Patricia Mae',  'Gutierrez', '16', 'Female', '2024-00301', '100200300041', 'GAS 11-A', 'dizon.guardian@email.com'),
    ('Hernandez',  'Leila Ann',     'Ignacio',   '17', 'Female', '2024-00302', '100200300042', 'GAS 11-A', 'hernandez.guardian@email.com'),
    ('Domingo',    'Kristine Mae',  'Esguerra',  '18', 'Female', '2023-00301', '100200300061', 'GAS 12-A', 'domingo.guardian@email.com'),
    ('Ferrer',     'Alyssa Jade',   'Gonzales',  '17', 'Female', '2023-00302', '100200300062', 'GAS 12-A', 'ferrer.guardian@email.com')",

  // ── SAMPLE USERS ─────────────────────────────────
  "INSERT IGNORE INTO users (username, password, role, name, initials, section, usn) VALUES
    ('admin',    'admin123',   'admin',   'Administrator',      'AD', NULL,        NULL),
    ('teacher1', 'teacher123', 'teacher', 'Prof. De Leon',      'PD', 'ICT 11-A',  NULL),
    ('teacher2', 'teacher123', 'teacher', 'Prof. Santos',       'PS', 'ICT 11-B',  NULL),
    ('teacher3', 'teacher123', 'teacher', 'Prof. Reyes',        'PR', 'ICT 12-A',  NULL),
    ('teacher4', 'teacher123', 'teacher', 'Prof. Mendoza',      'PM', 'ICT 12-B',  NULL),
    ('teacher5', 'teacher123', 'teacher', 'Prof. Garcia',       'PG', 'GAS 11-A',  NULL),
    ('teacher6', 'teacher123', 'teacher', 'Prof. Cruz',         'PC', 'GAS 11-B',  NULL),
    ('teacher7', 'teacher123', 'teacher', 'Prof. Torres',       'PT', 'GAS 12-A',  NULL),
    ('teacher8', 'teacher123', 'teacher', 'Prof. Flores',       'PF', 'GAS 12-B',  NULL),
    ('student1', 'student123', 'student', 'Reyes, Maria Clara', 'MR', 'ICT 11-A',  '2024-00101')",

];

foreach ($statements as $i => $sql) {
  $label = $i < 3 ? ['students table', 'users table', 'attendance table'][$i]
                  : ($i === 3 ? 'sample students' : 'sample users');
  if ($db->query($sql)) {
    $results[] = "✅ Created: $label";
  } else {
    $errors[]  = "❌ Error on $label: " . $db->error;
  }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>AttendEase Setup</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 600px; margin: 60px auto; padding: 20px; background: #f5f6fa; }
    h1   { color: #003087; }
    .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
    .ok  { color: #16a34a; margin: 8px 0; font-size: 15px; }
    .err { color: #c8102e; margin: 8px 0; font-size: 15px; }
    .warn { background: #fff7ed; border: 1px solid #f97316; border-radius: 8px; padding: 14px; margin-top: 24px; font-size: 13px; color: #9a3412; }
    .success { background: #f0fdf4; border: 1px solid #16a34a; border-radius: 8px; padding: 14px; margin-top: 24px; font-size: 14px; color: #15803d; }
  </style>
</head>
<body>
  <div class="card">
    <h1>AttendEase Setup</h1>
    <p>Running database setup…</p>
    <hr>
    <?php foreach ($results as $r): ?>
      <p class="ok"><?= htmlspecialchars($r) ?></p>
    <?php endforeach; ?>
    <?php foreach ($errors as $e): ?>
      <p class="err"><?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
    <hr>
    <?php if (empty($errors)): ?>
      <div class="success">
        🎉 <strong>Setup complete!</strong> All tables and sample data are ready.<br><br>
        Login credentials:<br>
        Admin: <code>admin / admin123</code><br>
        Teacher: <code>teacher1 / teacher123</code><br>
        Student: <code>student1 / student123</code>
      </div>
    <?php else: ?>
      <div class="err">⚠️ Some steps had errors. Check above for details.</div>
    <?php endif; ?>
    <div class="warn">
      ⚠️ <strong>Important:</strong> Delete <code>setup.php</code> from your repo after running this!
    </div>
  </div>
</body>
</html>
