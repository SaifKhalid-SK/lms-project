<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($student_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch student info
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$student) {
    header("Location: dashboard.php");
    exit();
}

// Fetch enrolled subjects with attendance
$subj_sql = "SELECT s.id, s.name, s.semester,
                    t.full_name AS teacher_name,
                    COUNT(DISTINCT a.id) AS total_classes,
                    SUM(CASE WHEN a.status='Present' THEN 1 ELSE 0 END) AS present
             FROM enrollments e
             JOIN subjects s ON e.subject_id = s.id
             JOIN teachers t ON s.teacher_id = t.id
             LEFT JOIN attendance a ON a.subject_id = s.id
                                   AND a.student_id = ?
             WHERE e.student_id = ?
             GROUP BY s.id";
$subj_stmt = mysqli_prepare($conn, $subj_sql);
mysqli_stmt_bind_param($subj_stmt, "ii", $student_id, $student_id);
mysqli_stmt_execute($subj_stmt);
$subjects = mysqli_stmt_get_result($subj_stmt);
$subj_rows = [];
while ($s = mysqli_fetch_assoc($subjects)) {
    $subj_rows[] = $s;
}

// Fetch all grades
$grade_sql = "SELECT g.*, s.name AS subject_name
              FROM grades g
              JOIN subjects s ON g.subject_id = s.id
              WHERE g.student_id = ?
              ORDER BY s.name, g.type, g.number";
$g_stmt = mysqli_prepare($conn, $grade_sql);
mysqli_stmt_bind_param($g_stmt, "i", $student_id);
mysqli_stmt_execute($g_stmt);
$grades = mysqli_stmt_get_result($g_stmt);
$grade_rows = [];
while ($g = mysqli_fetch_assoc($grades)) {
    $grade_rows[] = $g;
}

// Fetch fee records
$fee_sql = "SELECT * FROM fees WHERE student_id = ? ORDER BY due_date";
$f_stmt  = mysqli_prepare($conn, $fee_sql);
mysqli_stmt_bind_param($f_stmt, "i", $student_id);
mysqli_stmt_execute($f_stmt);
$fees     = mysqli_stmt_get_result($f_stmt);
$fee_rows = [];
while ($f = mysqli_fetch_assoc($fees)) {
    $fee_rows[] = $f;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Detail — EduLMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background:#f0f4f8; font-family:'Segoe UI',sans-serif; margin:0; }
        .top-navbar {
            background:linear-gradient(135deg,#1a0533,#6c3483);
            color:white; padding:0 25px; height:60px;
            display:flex; align-items:center; justify-content:space-between;
            position:fixed; top:0; left:0; right:0; z-index:1000;
            box-shadow:0 2px 10px rgba(0,0,0,0.2);
        }
        .top-navbar .brand { font-size:20px; font-weight:700; }
        .top-navbar .brand i { margin-right:8px; }
        .logout-btn {
            background:rgba(255,255,255,0.15);
            border:1px solid rgba(255,255,255,0.3);
            color:white; padding:6px 16px; border-radius:20px;
            font-size:13px; text-decoration:none;
        }
        .logout-btn:hover { background:rgba(255,255,255,0.25); color:white; }
        .sidebar {
            position:fixed; top:60px; left:0;
            height:calc(100vh - 60px); width:220px;
            background:#1a0533; z-index:999; padding-top:20px;
        }
        .sidebar a {
            display:flex; align-items:center; padding:14px 20px;
            color:rgba(255,255,255,0.75); text-decoration:none;
            font-size:14px; font-weight:500; gap:12px;
        }
        .sidebar a:hover { background:rgba(255,255,255,0.1); color:white; }
        .sidebar a i { font-size:16px; min-width:20px; }
        .sidebar-section {
            padding:10px 20px 5px; font-size:10px; font-weight:700;
            color:rgba(255,255,255,0.35); text-transform:uppercase;
            letter-spacing:1.5px; margin-top:10px;
        }
        .main-content { margin-left:220px; margin-top:60px; padding:30px; }
        .back-btn {
            display:inline-flex; align-items:center; gap:6px;
            color:#6c3483; text-decoration:none;
            font-size:14px; font-weight:600; margin-bottom:20px;
        }
        .back-btn:hover { color:#1a0533; }
        .profile-header {
            background:linear-gradient(135deg,#1a0533,#6c3483);
            border-radius:12px; padding:25px 30px; color:white;
            margin-bottom:25px; display:flex;
            align-items:center; gap:20px; flex-wrap:wrap;
        }
        .avatar {
            width:70px; height:70px; border-radius:50%;
            background:rgba(255,255,255,0.2);
            display:flex; align-items:center;
            justify-content:center; font-size:28px;
            flex-shrink:0;
        }
        .profile-header h4 { font-size:22px; font-weight:700; margin:0; }
        .profile-header p { margin:5px 0 0; opacity:0.85; font-size:14px; }
        .status-pill {
            padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700;
        }
        .pill-online  { background:#27AE60; color:white; }
        .pill-offline { background:rgba(255,255,255,0.2); color:white; }
        .info-card {
            background:white; border-radius:12px; padding:25px;
            box-shadow:0 2px 12px rgba(0,0,0,0.07); margin-bottom:20px;
        }
        .section-title {
            font-size:13px; font-weight:700; color:#6c3483;
            text-transform:uppercase; letter-spacing:1px;
            margin:0 0 18px; padding-bottom:8px;
            border-bottom:2px solid #f0eafb;
        }
        .info-row {
            display:flex; align-items:center; padding:10px 0;
            border-bottom:1px solid #f5f7fa; font-size:14px;
        }
        .info-row:last-child { border-bottom:none; }
        .info-label {
            font-weight:600; color:#7f8c8d;
            min-width:180px; font-size:13px;
        }
        .info-label i { color:#6c3483; margin-right:8px; }
        .info-value { color:#2C3E50; }
        .admin-table th {
            background:#1a0533; color:white;
            font-size:13px; font-weight:600; padding:12px 15px;
        }
        .admin-table td {
            padding:12px 15px; font-size:14px;
            vertical-align:middle; border-bottom:1px solid #f0f4f8;
        }
        .admin-table tr:last-child td { border-bottom:none; }
        .progress { height:8px; border-radius:10px; background:#ecf0f1; }
        .badge-present {
            background:#27AE60; color:white;
            padding:3px 10px; border-radius:20px; font-size:11px;
        }
        .badge-absent {
            background:#E74C3C; color:white;
            padding:3px 10px; border-radius:20px; font-size:11px;
        }
        .badge-paid    { background:#eafaf1; color:#27AE60; border:1px solid #27AE60; padding:3px 10px; border-radius:20px; font-size:11px; }
        .badge-unpaid  { background:#fdedec; color:#E74C3C; border:1px solid #E74C3C; padding:3px 10px; border-radius:20px; font-size:11px; }
        .badge-pending { background:#fef9e7; color:#f39c12; border:1px solid #f39c12; padding:3px 10px; border-radius:20px; font-size:11px; }
    </style>
</head>
<body>

<div class="top-navbar">
    <div class="brand"><i class="fas fa-graduation-cap"></i>EduLMS Admin</div>
    <div style="font-size:14px; color:rgba(255,255,255,0.85);">
        Student Detail
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<div class="sidebar">
    <div class="sidebar-section">Main</div>
    <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <div class="sidebar-section">Manage</div>
    <a href="dashboard.php#students">
        <i class="fas fa-user-graduate"></i> Students
    </a>
    <a href="dashboard.php#teachers">
        <i class="fas fa-chalkboard-teacher"></i> Teachers
    </a>
    <a href="add_teacher.php">
        <i class="fas fa-user-plus"></i> Add Teacher
    </a>
    <a href="add_fees.php">
        <i class="fas fa-file-invoice-dollar"></i> Add Fees
    </a>
</div>

<div class="main-content">

    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <!-- PROFILE HEADER -->
    <div class="profile-header">
        <div class="avatar"><i class="fas fa-user-graduate"></i></div>
        <div>
            <h4><?= htmlspecialchars($student['full_name']) ?></h4>
            <p>
                <?= htmlspecialchars($student['department']) ?>
                &nbsp;|&nbsp; Semester <?= $student['semester'] ?>
                &nbsp;|&nbsp; <?= htmlspecialchars($student['reg_no']) ?>
            </p>
            <span class="status-pill mt-2 d-inline-block
                <?= $student['is_online'] ? 'pill-online' : 'pill-offline' ?>">
                <i class="fas fa-circle me-1" style="font-size:8px;"></i>
                <?= $student['is_online'] ? 'Currently Online' : 'Offline' ?>
            </span>
        </div>
    </div>

    <!-- PERSONAL INFO -->
    <div class="info-card">
        <div class="section-title">
            <i class="fas fa-id-card me-2"></i>Personal Information
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-user"></i>Full Name</div>
                    <div class="info-value"><?= htmlspecialchars($student['full_name']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-envelope"></i>Email</div>
                    <div class="info-value"><?= htmlspecialchars($student['email']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-phone"></i>Phone</div>
                    <div class="info-value"><?= htmlspecialchars($student['phone']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-venus-mars"></i>Gender</div>
                    <div class="info-value"><?= htmlspecialchars($student['gender']) ?></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-id-badge"></i>Reg No</div>
                    <div class="info-value"><?= htmlspecialchars($student['reg_no']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-building"></i>Department</div>
                    <div class="info-value"><?= htmlspecialchars($student['department']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-layer-group"></i>Semester</div>
                    <div class="info-value">Semester <?= $student['semester'] ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-birthday-cake"></i>Date of Birth</div>
                    <div class="info-value">
                        <?= date('d M Y', strtotime($student['dob'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SUBJECTS + ATTENDANCE -->
    <div class="info-card">
        <div class="section-title">
            <i class="fas fa-book me-2"></i>Enrolled Subjects & Attendance
        </div>
        <?php if (empty($subj_rows)): ?>
            <div style="text-align:center; padding:20px; color:#95a5a6;">
                Not enrolled in any subjects.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Teacher</th>
                        <th>Semester</th>
                        <th>Present</th>
                        <th>Total</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subj_rows as $s):
                        $pct   = $s['total_classes'] > 0
                               ? round(($s['present'] / $s['total_classes']) * 100)
                               : 0;
                        $color = $pct >= 75 ? 'success'
                               : ($pct >= 50 ? 'warning' : 'danger');
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['name']) ?></strong></td>
                            <td style="color:#7f8c8d; font-size:13px;">
                                <?= htmlspecialchars($s['teacher_name']) ?>
                            </td>
                            <td>Sem <?= $s['semester'] ?></td>
                            <td>
                                <span class="badge-present">
                                    <?= $s['present'] ?>
                                </span>
                            </td>
                            <td><?= $s['total_classes'] ?></td>
                            <td style="min-width:130px;">
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar bg-<?= $color ?>"
                                             style="width:<?= $pct ?>%">
                                        </div>
                                    </div>
                                    <span style="font-weight:600; font-size:13px;
                                                 min-width:35px;">
                                        <?= $pct ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- GRADES -->
    <div class="info-card">
        <div class="section-title">
            <i class="fas fa-chart-bar me-2"></i>Grades Summary
        </div>
        <?php if (empty($grade_rows)): ?>
            <div style="text-align:center; padding:20px; color:#95a5a6;">
                No grades recorded yet.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Number</th>
                        <th>Marks Obtained</th>
                        <th>Total Marks</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grade_rows as $g):
                        $pct   = round(($g['marks_obtained'] / $g['total_marks']) * 100);
                        $color = $pct >= 75 ? '#27AE60'
                               : ($pct >= 50 ? '#f39c12' : '#E74C3C');
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($g['subject_name']) ?></td>
                            <td>
                                <span style="background:#f0eafb; color:#6c3483;
                                             border-radius:20px; padding:3px 10px;
                                             font-size:12px; font-weight:600;">
                                    <?= $g['type'] ?>
                                </span>
                            </td>
                            <td><?= $g['type'] ?> <?= $g['number'] ?></td>
                            <td><strong><?= $g['marks_obtained'] ?></strong></td>
                            <td><?= $g['total_marks'] ?></td>
                            <td>
                                <span style="font-weight:700; color:<?= $color ?>;">
                                    <?= $pct ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- FEES -->
    <div class="info-card">
        <div class="section-title">
            <i class="fas fa-file-invoice-dollar me-2"></i>Fee Records
        </div>
        <?php if (empty($fee_rows)): ?>
            <div style="text-align:center; padding:20px; color:#95a5a6;">
                No fee records found.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fee_rows as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['description']) ?></td>
                            <td><strong>Rs. <?= number_format($f['amount']) ?></strong></td>
                            <td><?= date('d M Y', strtotime($f['due_date'])) ?></td>
                            <td>
                                <?php if ($f['status'] == 'Paid'): ?>
                                    <span class="badge-paid">Paid</span>
                                <?php elseif ($f['status'] == 'Unpaid'): ?>
                                    <span class="badge-unpaid">Unpaid</span>
                                <?php else: ?>
                                    <span class="badge-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>