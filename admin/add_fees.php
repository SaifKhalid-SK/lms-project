<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$success = "";
$error   = "";

// ── HANDLE UPDATE EXISTING FEE ──
if (isset($_POST['update_fee'])) {
    $fee_id     = (int)$_POST['fee_id'];
    $new_status = $_POST['new_status'];
    $new_amount = (float)$_POST['new_amount'];
    $new_due    = $_POST['new_due_date'];
    $new_desc   = trim($_POST['new_description']);

    if (empty($new_desc) || $new_amount <= 0 || empty($new_due)) {
        $error = "All fields are required.";
    } else {
        $upd = mysqli_prepare($conn,
            "UPDATE fees
             SET status = ?, amount = ?, due_date = ?, description = ?
             WHERE id = ?");
        mysqli_stmt_bind_param($upd, "sdssi",
            $new_status, $new_amount, $new_due, $new_desc, $fee_id);
        mysqli_stmt_execute($upd);
        $success = "Fee record updated successfully.";
    }
}

// ── HANDLE DELETE FEE ──
if (isset($_POST['delete_fee'])) {
    $fee_id = (int)$_POST['fee_id'];
    $del    = mysqli_prepare($conn, "DELETE FROM fees WHERE id = ?");
    mysqli_stmt_bind_param($del, "i", $fee_id);
    mysqli_stmt_execute($del);
    $success = "Fee record deleted.";
}

// ── HANDLE ADD NEW FEE ──
if (isset($_POST['add_fee'])) {
    $student_id  = (int)$_POST['student_id'];
    $description = trim($_POST['description']);
    $amount      = (float)$_POST['amount'];
    $due_date    = $_POST['due_date'];
    $status      = $_POST['status'];

    if (!$student_id || empty($description) ||
        $amount <= 0 || empty($due_date)) {
        $error = "All fields are required.";
    } else {
        $ins = mysqli_prepare($conn,
            "INSERT INTO fees
             (student_id, description, amount, due_date, status)
             VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($ins, "isdss",
            $student_id, $description, $amount, $due_date, $status);
        mysqli_stmt_execute($ins);
        $success = "Fee record added successfully.";
        $_POST   = [];
    }
}

// ── FETCH STUDENTS FOR DROPDOWN ──
$stu_result = mysqli_query($conn,
    "SELECT id, full_name, reg_no FROM users ORDER BY full_name");
$students = [];
while ($s = mysqli_fetch_assoc($stu_result)) {
    $students[] = $s;
}

// ── FETCH ALL FEES WITH STUDENT INFO ──
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter = isset($_GET['filter']) ? $_GET['filter']       : 'all';

$fees_sql = "SELECT f.*, u.full_name, u.reg_no, u.semester
             FROM fees f
             JOIN users u ON f.student_id = u.id
             WHERE (u.full_name LIKE ? OR u.reg_no LIKE ?
                    OR f.description LIKE ?)";

if ($filter != 'all') {
    $fees_sql .= " AND f.status = '"
               . mysqli_real_escape_string($conn, $filter) . "'";
}
$fees_sql .= " ORDER BY f.due_date ASC";

$f_stmt = mysqli_prepare($conn, $fees_sql);
$like   = '%' . $search . '%';
mysqli_stmt_bind_param($f_stmt, "sss", $like, $like, $like);
mysqli_stmt_execute($f_stmt);
$fees_result = mysqli_stmt_get_result($f_stmt);
$fee_rows    = [];
while ($f = mysqli_fetch_assoc($fees_result)) {
    $fee_rows[] = $f;
}

// ── STATUS COUNTS ──
$counts      = ['all' => 0, 'Paid' => 0, 'Unpaid' => 0, 'Pending' => 0];
$cnt_result  = mysqli_query($conn,
    "SELECT status, COUNT(*) AS c FROM fees GROUP BY status");
while ($c = mysqli_fetch_assoc($cnt_result)) {
    $counts[$c['status']] = $c['c'];
    $counts['all']       += $c['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Management — EduLMS Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f0f4f8;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
        }
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
        .logout-btn {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white; padding: 6px 16px;
            border-radius: 20px; font-size: 13px;
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
            width: 220px;
            background: #1a0533;
            z-index: 999;
            padding-top: 20px;
            overflow-y: auto;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: rgba(255,255,255,0.75);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            gap: 12px;
            transition: background 0.2s;
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
        .main-content {
            margin-left: 220px;
            margin-top: 60px;
            padding: 30px;
        }
        .page-heading h4 {
            color: #2C3E50;
            font-weight: 700;
            font-size: 22px;
            margin: 0 0 5px;
        }
        .page-heading p {
            color: #7f8c8d;
            font-size: 14px;
            margin: 0 0 20px;
        }
        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #6c3483;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0 0 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #f0eafb;
        }
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
            border-color: #6c3483;
            box-shadow: 0 0 0 3px rgba(108,52,131,0.15);
        }
        .btn-add {
            background: linear-gradient(135deg, #1a0533, #6c3483);
            color: white; border: none;
            border-radius: 8px;
            padding: 11px 28px;
            font-size: 14px; font-weight: 600;
            transition: opacity 0.2s;
        }
        .btn-add:hover { opacity: 0.9; color: white; }

        /* ── FILTER TABS ── */
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .filter-tab {
            padding: 7px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid transparent;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .f-all     { background:#f0eafb; color:#6c3483; border-color:#6c3483; }
        .f-unpaid  { background:#fdedec; color:#E74C3C; border-color:#E74C3C; }
        .f-pending { background:#fef9e7; color:#f39c12; border-color:#f39c12; }
        .f-paid    { background:#eafaf1; color:#27AE60; border-color:#27AE60; }
        .f-all.active,     .f-all:hover     { background:#6c3483; color:white; }
        .f-unpaid.active,  .f-unpaid:hover  { background:#E74C3C; color:white; }
        .f-pending.active, .f-pending:hover { background:#f39c12; color:white; }
        .f-paid.active,    .f-paid:hover    { background:#27AE60; color:white; }
        .count-pill {
            background: rgba(255,255,255,0.3);
            border-radius: 10px;
            padding: 1px 7px;
            font-size: 11px;
        }

        /* ── TABLE ── */
        .search-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .search-input {
            border-radius: 8px;
            border: 1px solid #dde1e7;
            padding: 9px 14px;
            font-size: 13px;
            width: 280px;
        }
        .search-input:focus {
            border-color: #6c3483;
            outline: none;
            box-shadow: 0 0 0 3px rgba(108,52,131,0.15);
        }
        .fees-table th {
            background: #1a0533;
            color: white;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 15px;
        }
        .fees-table td {
            padding: 12px 15px;
            font-size: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
        }
        .fees-table tr:last-child td { border-bottom: none; }
        .fees-table tbody tr:hover td { background: #faf8fc; }

        /* ── BADGES ── */
        .badge-paid {
            background: #eafaf1; color: #27AE60;
            border: 1px solid #27AE60;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge-unpaid {
            background: #fdedec; color: #E74C3C;
            border: 1px solid #E74C3C;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .badge-pending {
            background: #fef9e7; color: #f39c12;
            border: 1px solid #f39c12;
            padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }

        /* ── ACTION BUTTONS ── */
        .btn-edit {
            background: #6c3483;
            color: white; border: none;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 12px; font-weight: 600;
            cursor: pointer;
        }
        .btn-edit:hover { opacity: 0.85; }
        .btn-del {
            background: #E74C3C;
            color: white; border: none;
            border-radius: 6px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
        }
        .btn-del:hover { opacity: 0.85; }

        /* ── MODAL ── */
        .modal-content  { border-radius: 12px; border: none; }
        .modal-header {
            background: linear-gradient(135deg, #1a0533, #6c3483);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        .modal-header .btn-close { filter: invert(1); }
        .btn-save-modal {
            background: linear-gradient(135deg, #1a0533, #6c3483);
            color: white; border: none;
            border-radius: 8px;
            padding: 10px 25px;
            font-size: 14px; font-weight: 600;
        }
        .btn-save-modal:hover { opacity: 0.9; color: white; }
        .overdue-tag {
            color: #E74C3C;
            font-size: 11px;
            font-weight: 600;
        }
        .empty-msg {
            text-align: center;
            padding: 40px;
            color: #95a5a6;
            font-size: 14px;
        }
        .empty-msg i {
            font-size: 36px;
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="top-navbar">
    <div class="brand">
        <i class="fas fa-graduation-cap"></i>EduLMS Admin
    </div>
    <div style="font-size:14px; color:rgba(255,255,255,0.85);">
        <i class="fas fa-file-invoice-dollar me-1"></i>Fee Management
    </div>
    <a href="../logout.php" class="logout-btn">
        <i class="fas fa-sign-out-alt me-1"></i>Logout
    </a>
</div>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-section">Main</div>
    <a href="dashboard.php">
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
    <a href="add_fees.php" class="active">
        <i class="fas fa-file-invoice-dollar"></i> Fee Management
    </a>
</div>

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="page-heading">
        <h4>
            <i class="fas fa-file-invoice-dollar me-2"
               style="color:#6c3483;"></i>Fee Management
        </h4>
        <p>Add new fee records and manage existing ones.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success py-2 mb-3" style="font-size:13px;">
            <i class="fas fa-check-circle me-1"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:13px;">
            <i class="fas fa-exclamation-circle me-1"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- ══════════════════════════════ -->
    <!-- ADD NEW FEE FORM              -->
    <!-- ══════════════════════════════ -->
    <div class="section-card">
        <div class="section-title">
            <i class="fas fa-plus me-2"></i>Add New Fee Record
        </div>
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label>Select Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="" disabled selected>
                            -- Select Student --
                        </option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['id'] ?>"
                                <?= (isset($_POST['student_id']) &&
                                    $_POST['student_id'] == $s['id'])
                                    ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['full_name']) ?>
                                (<?= htmlspecialchars($s['reg_no']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Description</label>
                    <input type="text"
                           name="description"
                           class="form-control"
                           placeholder="e.g. Semester Fee Spring 2025"
                           value="<?= isset($_POST['description'])
                               ? htmlspecialchars($_POST['description']) : '' ?>"
                           required>
                </div>
                <div class="col-md-4">
                    <label>Amount (Rs.)</label>
                    <input type="number"
                           name="amount"
                           class="form-control"
                           placeholder="e.g. 45000"
                           min="1"
                           value="<?= isset($_POST['amount'])
                               ? htmlspecialchars($_POST['amount']) : '' ?>"
                           required>
                </div>
                <div class="col-md-4">
                    <label>Due Date</label>
                    <input type="date"
                           name="due_date"
                           class="form-control"
                           value="<?= isset($_POST['due_date'])
                               ? htmlspecialchars($_POST['due_date']) : '' ?>"
                           required>
                </div>
                <div class="col-md-4">
                    <label>Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Unpaid"
                            <?= (!isset($_POST['status']) ||
                                $_POST['status']=='Unpaid') ? 'selected':'' ?>>
                            Unpaid
                        </option>
                        <option value="Pending"
                            <?= (isset($_POST['status']) &&
                                $_POST['status']=='Pending') ? 'selected':'' ?>>
                            Pending
                        </option>
                        <option value="Paid"
                            <?= (isset($_POST['status']) &&
                                $_POST['status']=='Paid') ? 'selected':'' ?>>
                            Paid
                        </option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" name="add_fee" class="btn-add">
                    <i class="fas fa-plus me-2"></i>Add Fee Record
                </button>
            </div>
        </form>
    </div>

    <!-- ══════════════════════════════ -->
    <!-- EXISTING FEE RECORDS          -->
    <!-- ══════════════════════════════ -->
    <div class="section-card">
        <div class="section-title">
            <i class="fas fa-list me-2"></i>Existing Fee Records
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <a href="?filter=all&search=<?= urlencode($search) ?>"
               class="filter-tab f-all <?= $filter=='all' ? 'active':'' ?>">
                <i class="fas fa-list"></i> All
                <span class="count-pill"><?= $counts['all'] ?></span>
            </a>
            <a href="?filter=Unpaid&search=<?= urlencode($search) ?>"
               class="filter-tab f-unpaid <?= $filter=='Unpaid' ? 'active':'' ?>">
                <i class="fas fa-times-circle"></i> Unpaid
                <span class="count-pill"><?= $counts['Unpaid'] ?></span>
            </a>
            <a href="?filter=Pending&search=<?= urlencode($search) ?>"
               class="filter-tab f-pending <?= $filter=='Pending' ? 'active':'' ?>">
                <i class="fas fa-clock"></i> Pending
                <span class="count-pill"><?= $counts['Pending'] ?></span>
            </a>
            <a href="?filter=Paid&search=<?= urlencode($search) ?>"
               class="filter-tab f-paid <?= $filter=='Paid' ? 'active':'' ?>">
                <i class="fas fa-check-circle"></i> Paid
                <span class="count-pill"><?= $counts['Paid'] ?></span>
            </a>
        </div>

        <!-- Search -->
        <div class="search-row">
            <form method="GET">
                <input type="hidden" name="filter" value="<?= $filter ?>">
                <input type="text"
                       name="search"
                       class="search-input"
                       placeholder="Search name, reg no, description..."
                       value="<?= htmlspecialchars($search) ?>"
                       oninput="this.form.submit()">
            </form>
            <div style="font-size:13px; color:#7f8c8d;">
                Showing <strong><?= count($fee_rows) ?></strong> records
            </div>
        </div>

        <!-- Table -->
        <?php if (empty($fee_rows)): ?>
            <div class="empty-msg">
                <i class="fas fa-file-invoice"></i>
                No fee records found.
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table fees-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fee_rows as $i => $fee):
                        $overdue = ($fee['status'] != 'Paid' &&
                                   strtotime($fee['due_date']) < time());
                    ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <div style="font-weight:600; color:#2C3E50;">
                                    <?= htmlspecialchars($fee['full_name']) ?>
                                </div>
                                <div style="font-size:12px; color:#7f8c8d;">
                                    <?= htmlspecialchars($fee['reg_no']) ?>
                                    — Sem <?= $fee['semester'] ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($fee['description']) ?></td>
                            <td>
                                <strong>Rs. <?= number_format($fee['amount']) ?></strong>
                            </td>
                            <td>
                                <?= date('d M Y', strtotime($fee['due_date'])) ?>
                                <?php if ($overdue): ?>
                                    <div class="overdue-tag">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Overdue
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($fee['status'] == 'Paid'): ?>
                                    <span class="badge-paid">
                                        <i class="fas fa-check me-1"></i>Paid
                                    </span>
                                <?php elseif ($fee['status'] == 'Unpaid'): ?>
                                    <span class="badge-unpaid">
                                        <i class="fas fa-times me-1"></i>Unpaid
                                    </span>
                                <?php else: ?>
                                    <span class="badge-pending">
                                        <i class="fas fa-clock me-1"></i>Pending
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px;">
                                    <button class="btn-edit"
                                        onclick="openEdit(
                                            <?= $fee['id'] ?>,
                                            '<?= addslashes(htmlspecialchars($fee['description'])) ?>',
                                            '<?= $fee['amount'] ?>',
                                            '<?= $fee['due_date'] ?>',
                                            '<?= $fee['status'] ?>'
                                        )">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirm(
                                            'Delete this fee record?')">
                                        <input type="hidden"
                                               name="fee_id"
                                               value="<?= $fee['id'] ?>">
                                        <button type="submit"
                                                name="delete_fee"
                                                class="btn-del">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- ══════════════════════════════ -->
<!-- EDIT MODAL                    -->
<!-- ══════════════════════════════ -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Fee Record
                </h5>
                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body p-4">
                <form method="POST">
                    <input type="hidden" name="fee_id" id="edit_fee_id">

                    <div class="mb-3">
                        <label>Description</label>
                        <input type="text"
                               name="new_description"
                               id="edit_desc"
                               class="form-control"
                               required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label>Amount (Rs.)</label>
                            <input type="number"
                                   name="new_amount"
                                   id="edit_amount"
                                   class="form-control"
                                   min="1" required>
                        </div>
                        <div class="col-6">
                            <label>Due Date</label>
                            <input type="date"
                                   name="new_due_date"
                                   id="edit_due"
                                   class="form-control"
                                   required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label>Status</label>
                        <select name="new_status"
                                id="edit_status"
                                class="form-select"
                                onchange="checkPaid(this.value)">
                            <option value="Unpaid">Unpaid</option>
                            <option value="Pending">Pending</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>

                    <!-- Paid confirmation hint -->
                    <div id="paid-hint"
                         class="alert alert-success py-2 mb-3"
                         style="font-size:13px; display:none;">
                        <i class="fas fa-check-circle me-1"></i>
                        This will mark the fee as <strong>Paid</strong>.
                        Student will see updated status immediately.
                    </div>

                    <div style="display:flex; gap:10px;">
                        <button type="submit"
                                name="update_fee"
                                class="btn-save-modal">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <button type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEdit(id, desc, amount, due, status) {
    document.getElementById('edit_fee_id').value = id;
    document.getElementById('edit_desc').value   = desc;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_due').value    = due;
    document.getElementById('edit_status').value = status;
    checkPaid(status);
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function checkPaid(val) {
    document.getElementById('paid-hint').style.display =
        val === 'Paid' ? 'block' : 'none';
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>