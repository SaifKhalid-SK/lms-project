<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$subject_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($subject_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Verify this subject belongs to this teacher
$verify = mysqli_prepare($conn,
    "SELECT * FROM subjects WHERE id = ? AND teacher_id = ?");
mysqli_stmt_bind_param($verify, "ii", $subject_id, $teacher_id);
mysqli_stmt_execute($verify);
$subject = mysqli_fetch_assoc(mysqli_stmt_get_result($verify));

if (!$subject) {
    header("Location: dashboard.php");
    exit();
}

$success = "";
$error   = "";

// ============================================
// HANDLE ANNOUNCEMENT POST
// ============================================
if (isset($_POST['post_announcement'])) {
    $message = trim($_POST['message']);
    if (empty($message)) {
        $error = "Announcement message cannot be empty.";
    } else {
        $ins = mysqli_prepare($conn,
            "INSERT INTO announcements (subject_id, teacher_id, message)
             VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($ins, "iis", $subject_id, $teacher_id, $message);
        mysqli_stmt_execute($ins);
        $success = "Announcement posted successfully.";
    }
}

// ============================================
// HANDLE ATTENDANCE SUBMIT
// ============================================
if (isset($_POST['submit_attendance'])) {
    $att_date = $_POST['att_date'];

    if (empty($att_date)) {
        $error = "Please select a date for attendance.";
    } elseif (!isset($_POST['attendance']) || empty($_POST['attendance'])) {
        $error = "No students found to mark attendance.";
    } else {
        foreach ($_POST['attendance'] as $stu_id => $status) {
            // Use INSERT ... ON DUPLICATE KEY UPDATE
            // so teacher can correct attendance for same date
            $ins = mysqli_prepare($conn,
                "INSERT INTO attendance (student_id, subject_id, date, status)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE status = VALUES(status)");
            mysqli_stmt_bind_param($ins, "iiss",
                $stu_id, $subject_id, $att_date, $status);
            mysqli_stmt_execute($ins);
        }
        $success = "Attendance saved for " . date('d M Y', strtotime($att_date));
    }
}

// ============================================
// HANDLE GRADES SUBMIT
// ============================================
if (isset($_POST['submit_grades'])) {
    $grade_type   = $_POST['grade_type'];    // Quiz or Assignment
    $grade_number = (int)$_POST['grade_number'];
    $total_marks  = (float)$_POST['total_marks'];

    if (empty($grade_type) || $grade_number <= 0 || $total_marks <= 0) {
        $error = "Please fill all grade fields correctly.";
    } elseif (!isset($_POST['marks']) || empty($_POST['marks'])) {
        $error = "No student marks found to save.";
    } else {
        foreach ($_POST['marks'] as $stu_id => $marks_obtained) {
            $marks = (float)$marks_obtained;
            // Clamp marks so they don't exceed total
            if ($marks > $total_marks) $marks = $total_marks;
            if ($marks < 0)           $marks = 0;

            $ins = mysqli_prepare($conn,
                "INSERT INTO grades
                     (student_id, subject_id, type, number,
                      marks_obtained, total_marks)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     marks_obtained = VALUES(marks_obtained),
                     total_marks    = VALUES(total_marks)");
            mysqli_stmt_bind_param($ins, "iisidd",
                $stu_id, $subject_id,
                $grade_type, $grade_number,
                $marks, $total_marks);
            mysqli_stmt_execute($ins);
        }
        $success = "$grade_type $grade_number marks saved successfully.";
    }
}

// ============================================
// FETCH DATA FOR DISPLAY
// ============================================

// Enrolled students
$stu_sql = "SELECT u.id, u.full_name, u.reg_no
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            WHERE e.subject_id = ?
            ORDER BY u.full_name";
$stu_stmt = mysqli_prepare($conn, $stu_sql);
mysqli_stmt_bind_param($stu_stmt, "i", $subject_id);
mysqli_stmt_execute($stu_stmt);
$students_result = mysqli_stmt_get_result($stu_stmt);
$students = [];
while ($s = mysqli_fetch_assoc($students_result)) {
    $students[] = $s;
}

// Previous announcements
$ann_sql = "SELECT message, created_at FROM announcements
            WHERE subject_id = ? AND teacher_id = ?
            ORDER BY created_at DESC";
$ann_stmt = mysqli_prepare($conn, $ann_sql);
mysqli_stmt_bind_param($ann_stmt, "ii", $subject_id, $teacher_id);
mysqli_stmt_execute($ann_stmt);
$ann_result = mysqli_stmt_get_result($ann_stmt);
$announcements = [];
while ($a = mysqli_fetch_assoc($ann_result)) {
    $announcements[] = $a;
}

// Today's attendance (to pre-fill if already marked)
$today         = date('Y-m-d');
$today_att_sql = "SELECT student_id, status FROM attendance
                  WHERE subject_id = ? AND date = ?";
$ta_stmt       = mysqli_prepare($conn, $today_att_sql);
mysqli_stmt_bind_param($ta_stmt, "is", $subject_id, $today);
mysqli_stmt_execute($ta_stmt);
$ta_result     = mysqli_stmt_get_result($ta_stmt);
$today_att     = [];
while ($ta = mysqli_fetch_assoc($ta_result)) {
    $today_att[$ta['student_id']] = $ta['status'];
}

// Existing quiz and assignment numbers for this subject
$quiz_nums_sql = "SELECT DISTINCT number FROM grades
                  WHERE subject_id = ? AND type = 'Quiz'
                  ORDER BY number";
$qn_stmt = mysqli_prepare($conn, $quiz_nums_sql);
mysqli_stmt_bind_param($qn_stmt, "i", $subject_id);
mysqli_stmt_execute($qn_stmt);
$qn_result    = mysqli_stmt_get_result($qn_stmt);
$quiz_numbers = [];
while ($q = mysqli_fetch_assoc($qn_result)) {
    $quiz_numbers[] = $q['number'];
}
$next_quiz = count($quiz_numbers) > 0 ? max($quiz_numbers) + 1 : 1;

$asgn_nums_sql = "SELECT DISTINCT number FROM grades
                  WHERE subject_id = ? AND type = 'Assignment'
                  ORDER BY number";
$an_stmt = mysqli_prepare($conn, $asgn_nums_sql);
mysqli_stmt_bind_param($an_stmt, "i", $subject_id);
mysqli_stmt_execute($an_stmt);
$an_result    = mysqli_stmt_get_result($an_stmt);
$asgn_numbers = [];
while ($a = mysqli_fetch_assoc($an_result)) {
    $asgn_numbers[] = $a['number'];
}
$next_asgn = count($asgn_numbers) > 0 ? max($asgn_numbers) + 1 : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subject — EduLMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }
        .top-navbar {
            background: linear-gradient(135deg, #1a252f, #2980b9);
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
            background: #1a252f;
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
        .sidebar a i { font-size: 18px; min-width: 24px; margin-right: 14px; }
        .sidebar-label { opacity: 0; transition: opacity 0.2s; }
        .sidebar:hover .sidebar-label { opacity: 1; }
        .main-content {
            margin-left: 60px;
            margin-top: 60px;
            padding: 30px;
        }
        .subject-header {
            background: linear-gradient(135deg, #1a252f, #2980b9);
            border-radius: 12px;
            padding: 25px 30px;
            color: white;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
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
        .student-count-badge {
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            padding: 10px 18px;
            text-align: center;
        }
        .student-count-badge .count {
            font-size: 24px;
            font-weight: 700;
        }
        .student-count-badge .label {
            font-size: 11px;
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
            padding: 12px 22px;
            border-radius: 8px;
            border: 2px solid #2980b9;
            background: white;
            color: #2980b9;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-btn:hover, .tab-btn.active {
            background: linear-gradient(135deg, #1a252f, #2980b9);
            color: white;
            border-color: transparent;
        }

        /* ── SECTION BOX ── */
        .section-box {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: none;
        }
        .section-box.active { display: block; }
        .section-title {
            font-size: 15px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eaf4fb;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i { color: #2980b9; }

        /* ── FORMS ── */
        label {
            font-size: 13px;
            font-weight: 600;
            color: #2C3E50;
            margin-bottom: 5px;
        }
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #dde1e7;
            font-size: 14px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #2980b9;
            box-shadow: 0 0 0 3px rgba(41,128,185,0.15);
        }
        .btn-submit {
            background: linear-gradient(135deg, #1a252f, #2980b9);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 14px;
            font-weight: 600;
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.9; color: white; }

        /* ── ATTENDANCE TABLE ── */
        .att-table th {
            background: #1a252f;
            color: white;
            font-size: 13px;
            padding: 11px 15px;
        }
        .att-table td {
            padding: 11px 15px;
            font-size: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
        }
        .att-table tr:last-child td { border-bottom: none; }
        .radio-group {
            display: flex;
            gap: 15px;
        }
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            font-weight: 500;
            margin: 0;
        }
        input[type="radio"] { cursor: pointer; }

        /* ── GRADES TABLE ── */
        .grades-table th {
            background: #1a252f;
            color: white;
            font-size: 13px;
            padding: 11px 15px;
        }
        .grades-table td {
            padding: 10px 15px;
            font-size: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
        }
        .grades-table tr:last-child td { border-bottom: none; }
        .marks-input {
            width: 90px;
            text-align: center;
            border-radius: 6px;
            border: 1px solid #dde1e7;
            padding: 7px 10px;
            font-size: 14px;
        }
        .marks-input:focus {
            border-color: #2980b9;
            outline: none;
            box-shadow: 0 0 0 3px rgba(41,128,185,0.15);
        }

        /* ── ANNOUNCEMENT ── */
        .prev-ann {
            background: #f8fafc;
            border-left: 4px solid #2980b9;
            border-radius: 8px;
            padding: 13px 16px;
            margin-bottom: 12px;
        }
        .prev-ann .ann-msg {
            font-size: 14px;
            color: #2C3E50;
            margin-bottom: 6px;
        }
        .prev-ann .ann-time {
            font-size: 12px;
            color: #95a5a6;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #2980b9;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .back-btn:hover { color: #1a252f; }

        .grade-type-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .grade-type-btn {
            padding: 8px 20px;
            border-radius: 8px;
            border: 2px solid #2980b9;
            background: white;
            color: #2980b9;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .grade-type-btn.active,
        .grade-type-btn:hover {
            background: #2980b9;
            color: white;
        }
        .no-students {
            text-align: center;
            padding: 30px;
            color: #95a5a6;
            font-size: 14px;
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
    <a href="profile.php">
        <i class="fas fa-user-tie"></i>
        <span class="sidebar-label">My Profile</span>
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <a href="dashboard.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>

    <!-- SUCCESS / ERROR -->
    <?php if ($success): ?>
        <div class="alert alert-success py-2 mb-3" style="font-size:13px;">
            <i class="fas fa-check-circle me-1"></i><?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:13px;">
            <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- SUBJECT HEADER -->
    <div class="subject-header">
        <div>
            <h4><?= htmlspecialchars($subject['name']) ?></h4>
            <p>
                <i class="fas fa-building me-1"></i>
                <?= htmlspecialchars($subject['department']) ?>
                &nbsp;|&nbsp;
                <i class="fas fa-layer-group me-1"></i>
                Semester <?= $subject['semester'] ?>
            </p>
        </div>
        <div class="student-count-badge">
            <div class="count"><?= count($students) ?></div>
            <div class="label">Students Enrolled</div>
        </div>
    </div>

    <!-- TAB BUTTONS -->
    <div class="tab-buttons">
        <button class="tab-btn active" onclick="showTab('announcement', this)">
            <i class="fas fa-bullhorn"></i> Announcement
        </button>
        <button class="tab-btn" onclick="showTab('attendance', this)">
            <i class="fas fa-clipboard-list"></i> Mark Attendance
        </button>
        <button class="tab-btn" onclick="showTab('grades', this)">
            <i class="fas fa-chart-bar"></i> Enter Grades
        </button>
    </div>

    <!-- ══════════════════════════════════════ -->
    <!-- TAB 1: ANNOUNCEMENT                   -->
    <!-- ══════════════════════════════════════ -->
    <div id="tab-announcement" class="section-box active">
        <div class="section-title">
            <i class="fas fa-bullhorn"></i> Post Announcement
        </div>

        <form method="POST">
            <div class="mb-3">
                <label>Announcement Message</label>
                <textarea name="message" class="form-control"
                          rows="4"
                          placeholder="Type your announcement here..."
                          required></textarea>
            </div>
            <button type="submit" name="post_announcement" class="btn-submit">
                <i class="fas fa-paper-plane me-2"></i>Post Announcement
            </button>
        </form>

        <!-- Previous Announcements -->
        <?php if (!empty($announcements)): ?>
            <hr style="margin:25px 0; border-color:#eaf4fb;">
            <div class="section-title" style="font-size:13px;">
                <i class="fas fa-history"></i> Previous Announcements
            </div>
            <?php foreach ($announcements as $ann): ?>
                <div class="prev-ann">
                    <div class="ann-msg">
                        <?= htmlspecialchars($ann['message']) ?>
                    </div>
                    <div class="ann-time">
                        <i class="fas fa-clock me-1"></i>
                        <?= date('d M Y, h:i A', strtotime($ann['created_at'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════ -->
    <!-- TAB 2: ATTENDANCE                     -->
    <!-- ══════════════════════════════════════ -->
    <div id="tab-attendance" class="section-box">
        <div class="section-title">
            <i class="fas fa-clipboard-list"></i> Mark Attendance
        </div>

        <?php if (empty($students)): ?>
            <div class="no-students">
                <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                No students enrolled in this subject.
            </div>
        <?php else: ?>
        <form method="POST">
            <!-- Date Picker -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>
                        <i class="fas fa-calendar me-1"></i>Date
                    </label>
                    <input type="date" name="att_date"
                           class="form-control"
                           value="<?= $today ?>" required>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <div style="font-size:13px; color:#7f8c8d; padding-bottom:10px;">
                        <i class="fas fa-info-circle me-1" style="color:#2980b9;"></i>
                        If attendance was already marked for this date, 
                        submitting again will update it.
                    </div>
                </div>
            </div>

            <!-- Quick Mark All Buttons -->
            <div style="margin-bottom:15px; display:flex; gap:10px; flex-wrap:wrap;">
                <button type="button" class="btn btn-sm btn-success"
                        onclick="markAll('Present')">
                    <i class="fas fa-check me-1"></i>Mark All Present
                </button>
                <button type="button" class="btn btn-sm btn-danger"
                        onclick="markAll('Absent')">
                    <i class="fas fa-times me-1"></i>Mark All Absent
                </button>
            </div>

            <!-- Students List -->
            <div class="table-responsive">
                <table class="table att-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Reg No</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $stu): ?>
                            <?php
                            // Pre-fill if already marked today
                            $already = isset($today_att[$stu['id']])
                                     ? $today_att[$stu['id']]
                                     : 'Present';
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <i class="fas fa-user-graduate me-2"
                                       style="color:#2980b9;"></i>
                                    <?= htmlspecialchars($stu['full_name']) ?>
                                </td>
                                <td style="color:#7f8c8d; font-size:13px;">
                                    <?= htmlspecialchars($stu['reg_no']) ?>
                                </td>
                                <td>
                                    <div class="radio-group">
                                        <label>
                                            <input type="radio"
                                                   name="attendance[<?= $stu['id'] ?>]"
                                                   value="Present"
                                                   class="att-radio"
                                                   <?= $already == 'Present' ? 'checked' : '' ?>>
                                            <span style="color:#27AE60; font-weight:600;">
                                                Present
                                            </span>
                                        </label>
                                        <label>
                                            <input type="radio"
                                                   name="attendance[<?= $stu['id'] ?>]"
                                                   value="Absent"
                                                   class="att-radio"
                                                   <?= $already == 'Absent' ? 'checked' : '' ?>>
                                            <span style="color:#E74C3C; font-weight:600;">
                                                Absent
                                            </span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" name="submit_attendance" class="btn-submit mt-2">
                <i class="fas fa-save me-2"></i>Save Attendance
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- ══════════════════════════════════════ -->
    <!-- TAB 3: GRADES                         -->
    <!-- ══════════════════════════════════════ -->
    <div id="tab-grades" class="section-box">
        <div class="section-title">
            <i class="fas fa-chart-bar"></i> Enter Grades
        </div>

        <?php if (empty($students)): ?>
            <div class="no-students">
                <i class="fas fa-user-slash fa-2x mb-2 d-block"></i>
                No students enrolled in this subject.
            </div>
        <?php else: ?>

        <!-- Quiz / Assignment toggle -->
        <div class="grade-type-tabs">
            <button class="grade-type-btn active"
                    onclick="switchGradeType('Quiz', this)">
                <i class="fas fa-pen me-1"></i>Quiz
            </button>
            <button class="grade-type-btn"
                    onclick="switchGradeType('Assignment', this)">
                <i class="fas fa-file-alt me-1"></i>Assignment
            </button>
        </div>

        <form method="POST" id="grades-form">
            <input type="hidden" name="grade_type" id="grade_type" value="Quiz">

            <div class="row mb-3">
                <div class="col-md-3">
                    <label id="grade-number-label">
                        <i class="fas fa-hashtag me-1"></i>Quiz Number
                    </label>
                    <select name="grade_number" class="form-select" id="grade_number">
                        <!-- Quiz options -->
                        <option value="<?= $next_quiz ?>">
                            Quiz <?= $next_quiz ?> (New)
                        </option>
                        <?php foreach ($quiz_numbers as $qn): ?>
                            <option value="<?= $qn ?>">
                                Quiz <?= $qn ?> (Edit)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label>
                        <i class="fas fa-star me-1"></i>Total Marks
                    </label>
                    <input type="number" name="total_marks"
                           class="form-control"
                           placeholder="e.g. 20"
                           min="1" max="100"
                           value="20" required>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div style="font-size:13px; color:#7f8c8d; padding-bottom:10px;">
                        <i class="fas fa-info-circle me-1" style="color:#2980b9;"></i>
                        Select "New" to add a new quiz/assignment,
                        or "Edit" to update existing marks.
                    </div>
                </div>
            </div>

            <!-- Students marks input -->
            <div class="table-responsive">
                <table class="table grades-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student Name</th>
                            <th>Reg No</th>
                            <th>Marks Obtained</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $stu): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <i class="fas fa-user-graduate me-2"
                                       style="color:#2980b9;"></i>
                                    <?= htmlspecialchars($stu['full_name']) ?>
                                </td>
                                <td style="color:#7f8c8d; font-size:13px;">
                                    <?= htmlspecialchars($stu['reg_no']) ?>
                                </td>
                                <td>
                                    <input type="number"
                                           name="marks[<?= $stu['id'] ?>]"
                                           class="marks-input"
                                           placeholder="0"
                                           min="0" max="100"
                                           value="0">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <button type="submit" name="submit_grades" class="btn-submit mt-2">
                <i class="fas fa-save me-2"></i>Save Grades
            </button>
        </form>
        <?php endif; ?>
    </div>

</div>

<script>
// ── TAB SWITCHING ──
function showTab(tab, btn) {
    document.querySelectorAll('.section-box')
            .forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn')
            .forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}

// ── MARK ALL ATTENDANCE ──
function markAll(status) {
    document.querySelectorAll('.att-radio').forEach(radio => {
        if (radio.value === status) radio.checked = true;
    });
}

// Quiz numbers from PHP
const quizNumbers = <?= json_encode($quiz_numbers) ?>;
const nextQuiz    = <?= $next_quiz ?>;

// Assignment numbers from PHP
const asgnNumbers = <?= json_encode($asgn_numbers) ?>;
const nextAsgn    = <?= $next_asgn ?>;

// ── SWITCH BETWEEN QUIZ AND ASSIGNMENT ──
function switchGradeType(type, btn) {
    document.querySelectorAll('.grade-type-btn')
            .forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    document.getElementById('grade_type').value = type;

    const select = document.getElementById('grade_number');
    const label  = document.getElementById('grade-number-label');

    select.innerHTML = '';
    label.innerHTML  = `<i class="fas fa-hashtag me-1"></i>${type} Number`;

    if (type === 'Quiz') {
        select.innerHTML += 
            `<option value="${nextQuiz}">Quiz ${nextQuiz} (New)</option>`;
        quizNumbers.forEach(n => {
            select.innerHTML += 
                `<option value="${n}">Quiz ${n} (Edit)</option>`;
        });
    } else {
        select.innerHTML += 
            `<option value="${nextAsgn}">Assignment ${nextAsgn} (New)</option>`;
        asgnNumbers.forEach(n => {
            select.innerHTML += 
                `<option value="${n}">Assignment ${n} (Edit)</option>`;
        });
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>