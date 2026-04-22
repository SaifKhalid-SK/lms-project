<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get student's department and semester
$stu = mysqli_prepare($conn, "SELECT department, semester FROM users WHERE id = ?");
mysqli_stmt_bind_param($stu, "i", $student_id);
mysqli_stmt_execute($stu);
$stu_data   = mysqli_fetch_assoc(mysqli_stmt_get_result($stu));
$department = $stu_data['department'];
$semester   = $stu_data['semester'];

// Get timetable for enrolled subjects
$sql = "SELECT s.name AS subject_name, 
               t.day, t.time_start, t.time_end,
               te.full_name AS teacher_name
        FROM timetable t
        JOIN subjects s  ON t.subject_id = s.id
        JOIN teachers te ON s.teacher_id = te.id
        JOIN enrollments e ON e.subject_id = s.id
        WHERE e.student_id = ?
        ORDER BY FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday'),
                 t.time_start";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Organise into array [day][time] = subject
$days     = ['Monday','Tuesday','Wednesday','Thursday','Friday'];
$schedule = [];
$times    = [];

while ($row = mysqli_fetch_assoc($result)) {
    $time_slot = date('h:i A', strtotime($row['time_start'])) 
               . ' - ' 
               . date('h:i A', strtotime($row['time_end']));
    $schedule[$row['day']][$time_slot] = $row;
    if (!in_array($time_slot, $times)) {
        $times[] = $time_slot;
    }
}
sort($times);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable — EduLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }
        .top-navbar {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            padding: 0 25px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .top-navbar .brand { font-size: 20px; font-weight: 700; }
        .top-navbar .brand i { margin-right: 8px; }
        .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }
        .sidebar {
            position: fixed;
            top: 60px; left: 0;
            height: calc(100vh - 60px);
            width: 60px;
            background: #2C3E50;
            overflow: hidden;
            transition: width 0.3s ease;
            z-index: 999;
            padding-top: 20px;
        }
        .sidebar:hover { width: 220px; }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 14px 18px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            white-space: nowrap;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar a i { font-size: 18px; min-width: 24px; margin-right: 14px; }
        .sidebar-label { opacity: 0; transition: opacity 0.2s; }
        .sidebar:hover .sidebar-label { opacity: 1; }
        .main-content {
            margin-left: 60px;
            margin-top: 60px;
            padding: 30px;
        }
        .page-heading {
            margin-bottom: 25px;
        }
        .page-heading h4 {
            color: #2C3E50;
            font-weight: 700;
            font-size: 22px;
            margin: 0;
        }
        .page-heading p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 4px 0 0;
        }
        .timetable-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            overflow-x: auto;
        }
        .timetable-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 700px;
        }
        .timetable-table th {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            padding: 14px 16px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .timetable-table th:first-child {
            border-radius: 8px 0 0 0;
        }
        .timetable-table th:last-child {
            border-radius: 0 8px 0 0;
        }
        .timetable-table td {
            padding: 12px 10px;
            text-align: center;
            border: 1px solid #edf2f7;
            font-size: 13px;
            vertical-align: middle;
        }
        .timetable-table td.time-col {
            background: #f8fafc;
            font-weight: 600;
            color: #2C3E50;
            font-size: 12px;
            white-space: nowrap;
            width: 130px;
        }
        .subject-cell {
            background: linear-gradient(135deg, #eaf4fb, #d6eaf8);
            border-radius: 8px;
            padding: 10px 8px;
            border-left: 3px solid #3498DB;
        }
        .subject-cell .cell-name {
            font-weight: 600;
            color: #2C3E50;
            font-size: 12px;
            margin-bottom: 4px;
        }
        .subject-cell .cell-teacher {
            font-size: 11px;
            color: #7f8c8d;
        }
        .empty-cell {
            color: #bdc3c7;
            font-size: 18px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #3498DB;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .back-btn:hover { color: #2C3E50; }
        .info-badge {
            background: #eaf4fb;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 13px;
            color: #2C3E50;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="top-navbar">
    <div class="brand">
        <i class="fas fa-graduation-cap"></i>EduLMS
    </div>
    <div style="font-size:14px; color:rgba(255,255,255,0.85);">
        <i class="fas fa-calendar-alt me-1"></i>Weekly Timetable
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <a href="dashboard.php">
        <i class="fas fa-th-large"></i>
        <span class="sidebar-label">Dashboard</span>
    </a>
    <a href="timetable.php" class="active">
        <i class="fas fa-calendar-alt"></i>
        <span class="sidebar-label">Timetable</span>
    </a>
    <a href="profile.php">
        <i class="fas fa-user"></i>
        <span class="sidebar-label">My Profile</span>
    </a>
    <a href="fees.php">
        <i class="fas fa-file-invoice-dollar"></i>
        <span class="sidebar-label">Fee Invoice</span>
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <div class="page-heading">
        <h4><i class="fas fa-calendar-alt me-2" style="color:#3498DB;"></i>Weekly Timetable</h4>
        <p>Your class schedule for the current semester.</p>
    </div>

    <div class="info-badge">
        <i class="fas fa-info-circle" style="color:#3498DB;"></i>
        <strong><?= htmlspecialchars($department) ?></strong>
        &nbsp;|&nbsp; Semester <?= $semester ?>
    </div>

    <div class="timetable-card">
        <?php if (empty($times)): ?>
            <div style="text-align:center; padding:40px; color:#95a5a6;">
                <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                No timetable found for your subjects.
            </div>
        <?php else: ?>
        <table class="timetable-table">
            <thead>
                <tr>
                    <th><i class="fas fa-clock me-1"></i>Time</th>
                    <?php foreach ($days as $day): ?>
                        <th><?= $day ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($times as $time): ?>
                    <tr>
                        <td class="time-col">
                            <i class="fas fa-clock me-1" style="color:#3498DB;"></i>
                            <?= $time ?>
                        </td>
                        <?php foreach ($days as $day): ?>
                            <td>
                                <?php if (isset($schedule[$day][$time])): ?>
                                    <div class="subject-cell">
                                        <div class="cell-name">
                                            <?= htmlspecialchars($schedule[$day][$time]['subject_name']) ?>
                                        </div>
                                        <div class="cell-teacher">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($schedule[$day][$time]['teacher_name']) ?>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="empty-cell">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>