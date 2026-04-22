<?php
session_start();
require '../db.php';

// Block if not logged in or not a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Get subject ID from URL
$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Verify this student is actually enrolled in this subject
$verify = mysqli_prepare($conn,
    "SELECT s.id, s.name, t.full_name AS teacher_name
     FROM enrollments e
     JOIN subjects s ON e.subject_id = s.id
     JOIN teachers t ON s.teacher_id = t.id
     WHERE e.student_id = ? AND s.id = ?");
mysqli_stmt_bind_param($verify, "ii", $student_id, $subject_id);
mysqli_stmt_execute($verify);
$verify_result = mysqli_stmt_get_result($verify);
$subject = mysqli_fetch_assoc($verify_result);

// If student not enrolled in this subject, send back
if (!$subject) {
    header("Location: dashboard.php");
    exit();
}

// ── FETCH ATTENDANCE RECORDS ──
$att_sql = "SELECT date, status FROM attendance
            WHERE student_id = ? AND subject_id = ?
            ORDER BY date DESC";
$att_stmt = mysqli_prepare($conn, $att_sql);
mysqli_stmt_bind_param($att_stmt, "ii", $student_id, $subject_id);
mysqli_stmt_execute($att_stmt);
$attendance_records = mysqli_stmt_get_result($att_stmt);

// ── FETCH QUIZ LIST ──
$quiz_sql = "SELECT DISTINCT number FROM grades
             WHERE student_id = ? AND subject_id = ? AND type = 'Quiz'
             ORDER BY number ASC";
$quiz_stmt = mysqli_prepare($conn, $quiz_sql);
mysqli_stmt_bind_param($quiz_stmt, "ii", $student_id, $subject_id);
mysqli_stmt_execute($quiz_stmt);
$quiz_list = mysqli_stmt_get_result($quiz_stmt);
$quizzes = [];
while ($q = mysqli_fetch_assoc($quiz_list)) {
    $quizzes[] = $q['number'];
}

// ── FETCH ASSIGNMENT LIST ──
$asgn_sql = "SELECT DISTINCT number FROM grades
             WHERE student_id = ? AND subject_id = ? AND type = 'Assignment'
             ORDER BY number ASC";
$asgn_stmt = mysqli_prepare($conn, $asgn_sql);
mysqli_stmt_bind_param($asgn_stmt, "ii", $student_id, $subject_id);
mysqli_stmt_execute($asgn_stmt);
$asgn_list = mysqli_stmt_get_result($asgn_stmt);
$assignments = [];
while ($a = mysqli_fetch_assoc($asgn_list)) {
    $assignments[] = $a['number'];
}

// ── FETCH ANNOUNCEMENTS ──
$ann_sql = "SELECT a.message, a.created_at, t.full_name AS teacher_name
            FROM announcements a
            JOIN teachers t ON a.teacher_id = t.id
            WHERE a.subject_id = ?
            ORDER BY a.created_at DESC";
$ann_stmt = mysqli_prepare($conn, $ann_sql);
mysqli_stmt_bind_param($ann_stmt, "i", $subject_id);
mysqli_stmt_execute($ann_stmt);
$announcements = mysqli_stmt_get_result($ann_stmt);
$ann_rows = [];
while ($ann = mysqli_fetch_assoc($announcements)) {
    $ann_rows[] = $ann;
}

// ── ATTENDANCE PERCENTAGE ──
$total_sql = "SELECT COUNT(*) AS total FROM attendance
              WHERE student_id = ? AND subject_id = ?";
$t_stmt = mysqli_prepare($conn, $total_sql);
mysqli_stmt_bind_param($t_stmt, "ii", $student_id, $subject_id);
mysqli_stmt_execute($t_stmt);
$total = mysqli_fetch_assoc(mysqli_stmt_get_result($t_stmt))['total'];

$present_sql = "SELECT COUNT(*) AS present FROM attendance
                WHERE student_id = ? AND subject_id = ? AND status = 'Present'";
$p_stmt = mysqli_prepare($conn, $present_sql);
mysqli_stmt_bind_param($p_stmt, "ii", $student_id, $subject_id);
mysqli_stmt_execute($p_stmt);
$present    = mysqli_fetch_assoc(mysqli_stmt_get_result($p_stmt))['present'];
$percentage = ($total > 0) ? round(($present / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($subject['name']) ?> — EduLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }

        /* ── NAVBAR ── */
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
        .top-navbar .brand {
            font-size: 20px;
            font-weight: 700;
        }
        .top-navbar .brand i { margin-right: 8px; }
        .top-navbar .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .top-navbar .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }

        /* ── SIDEBAR ── */
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
        .sidebar a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar a i {
            font-size: 18px;
            min-width: 24px;
            margin-right: 14px;
        }
        .sidebar-label {
            opacity: 0;
            transition: opacity 0.2s;
        }
        .sidebar:hover .sidebar-label { opacity: 1; }

        /* ── MAIN ── */
        .main-content {
            margin-left: 60px;
            margin-top: 60px;
            padding: 30px;
        }

        /* ── SUBJECT HEADER CARD ── */
        .subject-header {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            border-radius: 12px;
            padding: 25px 30px;
            color: white;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .subject-header h4 {
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }
        .subject-header p {
            margin: 5px 0 0;
            opacity: 0.85;
            font-size: 14px;
        }
        .attendance-badge {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.4);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            line-height: 1.2;
        }
        .attendance-badge span {
            font-size: 10px;
            font-weight: 400;
            opacity: 0.85;
        }

        /* ── TAB BUTTONS ── */
        .tab-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 11px 24px;
            border-radius: 8px;
            border: 2px solid #2C3E50;
            background: white;
            color: #2C3E50;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-btn:hover,
        .tab-btn.active {
            background: linear-gradient(135deg, #2C3E50, #3498DB);
            color: white;
            border-color: transparent;
        }

        /* ── CONTENT SECTIONS ── */
        .section-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: none;   /* hidden by default */
        }
        .section-box.active { display: block; }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eaf4fb;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ── GRADE SELECTS ── */
        .grade-select-row {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .grade-select-row label {
            font-size: 13px;
            font-weight: 600;
            color: #2C3E50;
            min-width: 100px;
        }
        .grade-select-row select {
            border-radius: 8px;
            border: 1px solid #dde1e7;
            padding: 9px 14px;
            font-size: 14px;
            min-width: 160px;
        }
        .grade-result-card {
            background: #f8fafc;
            border-radius: 10px;
            padding: 18px 22px;
            display: inline-flex;
            align-items: center;
            gap: 20px;
            border: 1px solid #e0e7ef;
            margin-top: 5px;
        }
        .grade-result-card .score {
            font-size: 28px;
            font-weight: 700;
            color: #2C3E50;
        }
        .grade-result-card .out-of {
            font-size: 13px;
            color: #7f8c8d;
        }
        .grade-result-card .grade-pct {
            font-size: 15px;
            font-weight: 600;
        }

        /* ── ATTENDANCE TABLE ── */
        .attendance-table th {
            background: #2C3E50;
            color: white;
            font-size: 13px;
            font-weight: 600;
        }
        .attendance-table td {
            font-size: 14px;
            vertical-align: middle;
        }
        .badge-present {
            background: #27AE60;
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-absent {
            background: #E74C3C;
            color: white;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── ANNOUNCEMENT CARDS ── */
        .ann-card {
            background: #f8fafc;
            border-left: 4px solid #3498DB;
            border-radius: 8px;
            padding: 15px 18px;
            margin-bottom: 14px;
        }
        .ann-card .ann-message {
            font-size: 14px;
            color: #2C3E50;
            margin-bottom: 8px;
            line-height: 1.6;
        }
        .ann-card .ann-meta {
            font-size: 12px;
            color: #95a5a6;
        }
        .ann-card .ann-meta i { margin-right: 4px; }

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

        .empty-msg {
            text-align: center;
            color: #95a5a6;
            padding: 30px;
            font-size: 14px;
        }
        .empty-msg i {
            display: block;
            font-size: 36px;
            margin-bottom: 10px;
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
        <?= htmlspecialchars($subject['name']) ?>
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
    <a href="timetable.php">
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

    <!-- SUBJECT HEADER -->
    <div class="subject-header">
        <div>
            <h4><?= htmlspecialchars($subject['name']) ?></h4>
            <p>
                <i class="fas fa-chalkboard-teacher me-1"></i>
                <?= htmlspecialchars($subject['teacher_name']) ?>
            </p>
        </div>
        <div class="attendance-badge">
            <?= $percentage ?>%
            <span>Attendance</span>
        </div>
    </div>

    <!-- TAB BUTTONS -->
    <div class="tab-buttons">
        <button class="tab-btn active" onclick="showTab('grades', this)">
            <i class="fas fa-chart-bar"></i> Grade Book
        </button>
        <button class="tab-btn" onclick="showTab('attendance', this)">
            <i class="fas fa-clipboard-list"></i> Attendance
        </button>
        <button class="tab-btn" onclick="showTab('announcements', this)">
            <i class="fas fa-bullhorn"></i> Announcements
            <?php if (count($ann_rows) > 0): ?>
                <span style="background:#E74C3C; color:white; border-radius:50%;
                             width:20px; height:20px; font-size:11px;
                             display:inline-flex; align-items:center;
                             justify-content:center;">
                    <?= count($ann_rows) ?>
                </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ── GRADE BOOK SECTION ── -->
    <div id="tab-grades" class="section-box active">
        <div class="section-title">
            <i class="fas fa-chart-bar" style="color:#3498DB;"></i>
            Grade Book
        </div>

        <!-- QUIZ -->
        <div class="grade-select-row">
            <label><i class="fas fa-pen me-1"></i>Quiz</label>
            <select id="quiz-select" onchange="loadGrade('Quiz', this.value)">
                <option value="">-- Select Quiz --</option>
                <?php foreach ($quizzes as $num): ?>
                    <option value="<?= $num ?>">Quiz <?= $num ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="quiz-result" style="margin-bottom:25px;"></div>

        <hr style="border-color:#eaf4fb;">

        <!-- ASSIGNMENT -->
        <div class="grade-select-row" style="margin-top:20px;">
            <label><i class="fas fa-file-alt me-1"></i>Assignment</label>
            <select id="asgn-select" onchange="loadGrade('Assignment', this.value)">
                <option value="">-- Select Assignment --</option>
                <?php foreach ($assignments as $num): ?>
                    <option value="<?= $num ?>">Assignment <?= $num ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="asgn-result"></div>

        <?php if (empty($quizzes) && empty($assignments)): ?>
            <div class="empty-msg">
                <i class="fas fa-inbox"></i>
                No grades have been entered yet.
            </div>
        <?php endif; ?>
    </div>

    <!-- ── ATTENDANCE SECTION ── -->
    <div id="tab-attendance" class="section-box">
        <div class="section-title">
            <i class="fas fa-clipboard-list" style="color:#3498DB;"></i>
            Attendance Record
        </div>

        <?php
        // Reset result pointer
        mysqli_data_seek($attendance_records, 0);
        $att_arr = [];
        while ($a = mysqli_fetch_assoc($attendance_records)) {
            $att_arr[] = $a;
        }
        ?>

        <?php if (empty($att_arr)): ?>
            <div class="empty-msg">
                <i class="fas fa-calendar-times"></i>
                No attendance records found yet.
            </div>
        <?php else: ?>
            <!-- Summary -->
            <div style="display:flex; gap:15px; margin-bottom:20px; flex-wrap:wrap;">
                <div style="background:#eafaf1; border-radius:8px; padding:12px 20px;
                            border-left:4px solid #27AE60;">
                    <div style="font-size:22px; font-weight:700; color:#27AE60;">
                        <?= $present ?>
                    </div>
                    <div style="font-size:12px; color:#555;">Classes Present</div>
                </div>
                <div style="background:#fdedec; border-radius:8px; padding:12px 20px;
                            border-left:4px solid #E74C3C;">
                    <div style="font-size:22px; font-weight:700; color:#E74C3C;">
                        <?= $total - $present ?>
                    </div>
                    <div style="font-size:12px; color:#555;">Classes Absent</div>
                </div>
                <div style="background:#eaf4fb; border-radius:8px; padding:12px 20px;
                            border-left:4px solid #3498DB;">
                    <div style="font-size:22px; font-weight:700; color:#3498DB;">
                        <?= $total ?>
                    </div>
                    <div style="font-size:12px; color:#555;">Total Classes</div>
                </div>
            </div>

            <!-- Attendance Table -->
            <div class="table-responsive">
                <table class="table attendance-table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($att_arr as $index => $att): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= date('d M Y', strtotime($att['date'])) ?></td>
                                <td><?= date('l', strtotime($att['date'])) ?></td>
                                <td>
                                    <?php if ($att['status'] == 'Present'): ?>
                                        <span class="badge-present">
                                            <i class="fas fa-check me-1"></i>Present
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-absent">
                                            <i class="fas fa-times me-1"></i>Absent
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── ANNOUNCEMENTS SECTION ── -->
    <div id="tab-announcements" class="section-box">
        <div class="section-title">
            <i class="fas fa-bullhorn" style="color:#3498DB;"></i>
            Announcements
        </div>

        <?php if (empty($ann_rows)): ?>
            <div class="empty-msg">
                <i class="fas fa-bell-slash"></i>
                No announcements yet.
            </div>
        <?php else: ?>
            <?php foreach ($ann_rows as $ann): ?>
                <div class="ann-card">
                    <div class="ann-message">
                        <?= htmlspecialchars($ann['message']) ?>
                    </div>
                    <div class="ann-meta">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <?= htmlspecialchars($ann['teacher_name']) ?>
                        &nbsp;&nbsp;
                        <i class="fas fa-clock"></i>
                        <?= date('d M Y, h:i A', strtotime($ann['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Hidden grade data from PHP for JavaScript to use -->
<script>
// All grade data passed from PHP into JS
const gradeData = <?php
    // Fetch all grades for this student in this subject
    $all_grades_sql = "SELECT type, number, marks_obtained, total_marks
                       FROM grades
                       WHERE student_id = ? AND subject_id = ?";
    $g_stmt = mysqli_prepare($conn, $all_grades_sql);
    mysqli_stmt_bind_param($g_stmt, "ii", $student_id, $subject_id);
    mysqli_stmt_execute($g_stmt);
    $g_result = mysqli_stmt_get_result($g_stmt);
    $all_grades = [];
    while ($g = mysqli_fetch_assoc($g_result)) {
        $key = $g['type'] . '_' . $g['number'];
        $all_grades[$key] = $g;
    }
    echo json_encode($all_grades);
?>;

// Show tab when button clicked
function showTab(tab, btn) {
    // Hide all sections
    document.querySelectorAll('.section-box').forEach(s => s.classList.remove('active'));
    // Remove active from all buttons
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    // Show selected section
    document.getElementById('tab-' + tab).classList.add('active');
    // Set button active
    btn.classList.add('active');
}

// Load grade when dropdown changes
function loadGrade(type, number) {
    if (!number) return;

    const key     = type + '_' + number;
    const grade   = gradeData[key];
    const divId   = (type === 'Quiz') ? 'quiz-result' : 'asgn-result';
    const div     = document.getElementById(divId);

    if (!grade) {
        div.innerHTML = `<div class="empty-msg" style="padding:15px; text-align:left;">
            <i class="fas fa-exclamation-circle" style="color:#E74C3C;"></i>
            No marks entered for this ${type} yet.
        </div>`;
        return;
    }

    const obtained  = parseFloat(grade.marks_obtained);
    const total     = parseFloat(grade.total_marks);
    const pct       = Math.round((obtained / total) * 100);
    const color     = pct >= 75 ? '#27AE60' : pct >= 50 ? '#f39c12' : '#E74C3C';
    const label     = type === 'Quiz' ? 'Quiz ' + number : 'Assignment ' + number;

    div.innerHTML = `
        <div class="grade-result-card">
            <div>
                <div style="font-size:12px; font-weight:600; color:#7f8c8d;
                            margin-bottom:4px;">${label}</div>
                <div class="score">${obtained}</div>
                <div class="out-of">out of ${total}</div>
            </div>
            <div>
                <div class="grade-pct" style="color:${color}; font-size:24px;">
                    ${pct}%
                </div>
                <div style="font-size:12px; color:#7f8c8d;">Score</div>
            </div>
        </div>`;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>