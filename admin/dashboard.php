<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$admin_username = $_SESSION['username'];

// ── STATS ──
$total_students  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM users"))['c'];
$total_teachers  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM teachers"))['c'];
$online_students = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM users WHERE is_online = 1"))['c'];
$online_teachers = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS c FROM teachers WHERE is_online = 1"))['c'];



// ── FETCH STUDENTS ──
$stu_sql = "SELECT id, full_name, reg_no, department,
                   semester, is_online
            FROM users
            ORDER BY is_online DESC, full_name ASC";
$stu_stmt = mysqli_prepare($conn, $stu_sql);
mysqli_stmt_execute($stu_stmt);
$students = mysqli_stmt_get_result($stu_stmt);
$student_rows = [];
while ($s = mysqli_fetch_assoc($students)) {
    $student_rows[] = $s;
}

// ── FETCH TEACHERS ──
$tea_sql = "SELECT id, full_name, email, department, is_online
            FROM teachers
            ORDER BY is_online DESC, full_name ASC";
$tea_stmt = mysqli_prepare($conn, $tea_sql);
mysqli_stmt_execute($tea_stmt);
$teachers = mysqli_stmt_get_result($tea_stmt);
$teacher_rows = [];
while ($t = mysqli_fetch_assoc($teachers)) {
    $teacher_rows[] = $t;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — EduLMS</title>
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
            background: linear-gradient(135deg, #1a0533, #6c3483);
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
        .role-badge {
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            text-decoration: none;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.25);
            color: white;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed;
            top: 60px; left: 0;
            height: calc(100vh - 60px);
            width: 220px;
            background: #1a0533;
            z-index: 999;
            padding-top: 20px;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
            gap: 12px;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .sidebar a i { font-size: 16px; min-width: 20px; }
        .sidebar-section {
            padding: 10px 20px 5px;
            font-size: 10px;
            font-weight: 700;
            color: rgba(255,255,255,0.35);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 10px;
        }

        /* ── MAIN ── */
        .main-content {
            margin-left: 220px;
            margin-top: 60px;
            padding: 30px;
        }

        /* ── STAT CARDS ── */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            flex-shrink: 0;
        }
        .stat-card .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: #2C3E50;
            line-height: 1;
            margin-bottom: 4px;
        }
        .stat-card .stat-label {
            font-size: 13px;
            color: #7f8c8d;
            font-weight: 500;
        }

        /* ── SECTION HEADING ── */
        .section-heading {
            font-size: 16px;
            font-weight: 700;
            color: #2C3E50;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .section-heading i { color: #6c3483; }

        /* ── TABLE CARD ── */
        .table-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .search-input {
            border-radius: 8px;
            border: 1px solid #dde1e7;
            padding: 9px 14px;
            font-size: 13px;
            width: 250px;
        }
        .search-input:focus {
            border-color: #6c3483;
            outline: none;
            box-shadow: 0 0 0 3px rgba(108,52,131,0.15);
        }
        .admin-table th {
            background: #1a0533;
            color: white;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 15px;
        }
        .admin-table td {
            padding: 12px 15px;
            font-size: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
        }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tbody tr:hover td { background: #f8f4fc; }
        .admin-table tbody tr { cursor: pointer; }

        /* ── ONLINE / OFFLINE DOTS ── */
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }
        .dot-online  { background: #27AE60; box-shadow: 0 0 6px #27AE60; }
        .dot-offline { background: #bdc3c7; }
        .status-text-online  {
            color: #27AE60;
            font-size: 12px;
            font-weight: 600;
        }
        .status-text-offline {
            color: #95a5a6;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-view {
            background: #6c3483;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 5px 14px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-view:hover { opacity: 0.85; color: white; }

        .page-heading h4 {
            color: #2C3E50;
            font-weight: 700;
            font-size: 22px;
            margin: 0 0 5px;
        }
        .page-heading p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0 0 25px;
        }
        .empty-msg {
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
        <i class="fas fa-graduation-cap"></i>EduLMS Admin
    </div>
    <div style="display:flex; align-items:center; gap:10px;
                font-size:14px; color:rgba(255,255,255,0.9);">
        <i class="fas fa-user-shield"></i>
        <?= htmlspecialchars($admin_username) ?>
        <span class="role-badge">ADMIN</span>
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-section">Main</div>
    <a href="dashboard.php" class="active">
        <i class="fas fa-th-large"></i> Dashboard
    </a>

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

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="page-heading">
        <h4><i class="fas fa-tachometer-alt me-2" style="color:#6c3483;"></i>
            Admin Dashboard
        </h4>
        <p>Overview of the entire LMS system.</p>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon"
                     style="background:linear-gradient(135deg,#1a0533,#6c3483);">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $total_students ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon"
                     style="background:linear-gradient(135deg,#1a252f,#2980b9);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $total_teachers ?></div>
                    <div class="stat-label">Total Teachers</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon"
                     style="background:linear-gradient(135deg,#1e8449,#27AE60);">
                    <i class="fas fa-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $online_students ?></div>
                    <div class="stat-label">Students Online</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon"
                     style="background:linear-gradient(135deg,#922b21,#E74C3C);">
                    <i class="fas fa-circle"></i>
                </div>
                <div>
                    <div class="stat-number"><?= $online_teachers ?></div>
                    <div class="stat-label">Teachers Online</div>
                </div>
            </div>
        </div>
    </div>

    <!-- STUDENTS TABLE -->
    <div id="students" class="table-card mb-4">
        <div class="section-heading">
            <span>
                <i class="fas fa-user-graduate me-2"></i>Students
            </span>
            <form method="GET" style="display:inline;" id="student_form">
                <input type="hidden" name="teacher_search"
                        value="<?= htmlspecialchars($teacher_search) ?>">
                <input type="text"
                       id="student_search_input"
                       class="search-input"
                       placeholder="Search by name or reg no..."
                       oninput="filterTable(this.value, 'students_table')"
                       autocomplete="off">
            </form>
        </div>

        <?php if (empty($student_rows)): ?>
            <div class="empty-msg">
                <i class="fas fa-search fa-2x mb-2 d-block"></i>
                No students found.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table" id="students_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Full Name</th>
                        <th>Reg No</th>
                        <th>Department</th>
                        <th>Semester</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($student_rows as $i => $s): ?>
                        <tr onclick="window.location='student_detail.php?id=<?= $s['id'] ?>'">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <span class="status-dot
                                    <?= $s['is_online'] ? 'dot-online' : 'dot-offline' ?>">
                                </span>
                                <?php if ($s['is_online']): ?>
                                    <span class="status-text-online">Online</span>
                                <?php else: ?>
                                    <span class="status-text-offline">Offline</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas fa-user-circle me-2"
                                   style="color:#6c3483;"></i>
                                <strong><?= htmlspecialchars($s['full_name']) ?></strong>
                            </td>
                            <td style="color:#7f8c8d; font-size:13px;">
                                <?= htmlspecialchars($s['reg_no']) ?>
                            </td>
                            <td><?= htmlspecialchars($s['department']) ?></td>
                            <td>
                                <span style="background:#f0eafb; color:#6c3483;
                                             border-radius:20px; padding:3px 10px;
                                             font-size:12px; font-weight:600;">
                                    Sem <?= $s['semester'] ?>
                                </span>
                            </td>
                            <td>
                                <a href="student_detail.php?id=<?= $s['id'] ?>"
                                   class="btn-view"
                                   onclick="event.stopPropagation()">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- TEACHERS TABLE -->
    <div id="teachers" class="table-card">
        <div class="section-heading">
            <span>
                <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
            </span>
            <form method="GET" style="display:inline;" id="teacher_form">
                <input type="hidden" name="student_search"
                       value="<?= htmlspecialchars($student_search) ?>">
                <input type="text"
                       id="teacher_search_input"
                       class="search-input"
                       placeholder="Search by name or department..."
                       oninput="filterTable(this.value, 'teachers_table')"
                       autocomplete="off">
            </form>
        </div>

        <?php if (empty($teacher_rows)): ?>
            <div class="empty-msg">
                <i class="fas fa-search fa-2x mb-2 d-block"></i>
                No teachers found.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table" id="teachers_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Status</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teacher_rows as $i => $t): ?>
                        <tr onclick="window.location='teacher_detail.php?id=<?= $t['id'] ?>'">
                            <td><?= $i + 1 ?></td>
                            <td>
                                <span class="status-dot
                                    <?= $t['is_online'] ? 'dot-online' : 'dot-offline' ?>">
                                </span>
                                <?php if ($t['is_online']): ?>
                                    <span class="status-text-online">Online</span>
                                <?php else: ?>
                                    <span class="status-text-offline">Offline</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas fa-user-tie me-2"
                                   style="color:#6c3483;"></i>
                                <strong><?= htmlspecialchars($t['full_name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($t['department']) ?></td>
                            <td style="color:#7f8c8d; font-size:13px;">
                                <?= htmlspecialchars($t['email']) ?>
                            </td>
                            <td>
                                <a href="teacher_detail.php?id=<?= $t['id'] ?>"
                                   class="btn-view"
                                   onclick="event.stopPropagation()">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
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
<script>
function filterTable(query, tableId) {
    // Convert search to lowercase for comparison
    const search = query.toLowerCase().trim();
    const table  = document.getElementById(tableId);
    const rows   = table.getElementsByTagName('tbody')[0]
                        .getElementsByTagName('tr');

    let visibleCount = 0;

    // Loop through every row
    for (let i = 0; i < rows.length; i++) {
        // Get all text in this row
        const rowText = rows[i].innerText.toLowerCase();

        if (rowText.indexOf(search) > -1) {
            // Show row if it matches
            rows[i].style.display = '';
            visibleCount++;
        } else {
            // Hide row if it does not match
            rows[i].style.display = 'none';
        }
    }

    // Show no results message if nothing found
    const noResultId = tableId + '_empty';
    let noResult     = document.getElementById(noResultId);

    if (visibleCount === 0) {
        if (!noResult) {
            const tbody = table.getElementsByTagName('tbody')[0];
            const colCount = table.getElementsByTagName('th').length;
            const emptyRow = document.createElement('tr');
            emptyRow.id = noResultId;
            emptyRow.innerHTML = `
                <td colspan="${colCount}"
                    style="text-align:center;
                           padding:30px;
                           color:#95a5a6;
                           font-size:14px;">
                    <i class="fas fa-search me-2"></i>
                    No results found for "${query}"
                </td>`;
            tbody.appendChild(emptyRow);
        }
    } else {
        // Remove no results message if results found
        if (noResult) noResult.remove();
    }
}
</script>
</body>
</html>