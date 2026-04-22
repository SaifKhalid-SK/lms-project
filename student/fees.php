<?php
session_start();
require '../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['user_id'];

// Fetch all fee records for this student
$sql = "SELECT * FROM fees WHERE student_id = ? ORDER BY due_date ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$fees = mysqli_stmt_get_result($stmt);
$fee_rows = [];
while ($f = mysqli_fetch_assoc($fees)) {
    $fee_rows[] = $f;
}

// Calculate totals
$total_amount  = 0;
$total_paid    = 0;
$total_unpaid  = 0;
foreach ($fee_rows as $fee) {
    $total_amount += $fee['amount'];
    if ($fee['status'] == 'Paid')   $total_paid   += $fee['amount'];
    if ($fee['status'] == 'Unpaid') $total_unpaid += $fee['amount'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Invoice — EduLMS</title>
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
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            text-align: center;
        }
        .stat-card .stat-amount {
            font-size: 24px;
            font-weight: 700;
            margin: 8px 0 4px;
        }
        .stat-card .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .fees-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            margin-top: 20px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #3498DB;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 18px;
            padding-bottom: 8px;
            border-bottom: 2px solid #eaf4fb;
        }
        .fees-table th {
            background: #2C3E50;
            color: white;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 15px;
        }
        .fees-table td {
            padding: 13px 15px;
            font-size: 14px;
            vertical-align: middle;
            border-bottom: 1px solid #f0f4f8;
        }
        .fees-table tr:last-child td { border-bottom: none; }
        .fees-table tr:hover td { background: #f8fafc; }
        .badge-paid {
            background: #eafaf1;
            color: #27AE60;
            border: 1px solid #27AE60;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-unpaid {
            background: #fdedec;
            color: #E74C3C;
            border: 1px solid #E74C3C;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-pending {
            background: #fef9e7;
            color: #f39c12;
            border: 1px solid #f39c12;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .notice-box {
            background: #fef9e7;
            border-left: 4px solid #f39c12;
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 13px;
            color: #7f6000;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
    </style>
</head>
<body>

<!-- NAVBAR -->
<div class="top-navbar">
    <div class="brand">
        <i class="fas fa-graduation-cap"></i>EduLMS
    </div>
    <div style="font-size:14px; color:rgba(255,255,255,0.85);">
        <i class="fas fa-file-invoice-dollar me-1"></i>Fee Invoice
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
    <a href="fees.php" class="active">
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
        <h4><i class="fas fa-file-invoice-dollar me-2" style="color:#3498DB;"></i>Fee Invoice</h4>
        <p>Your fee challan and payment records.</p>
    </div>

    <!-- SUMMARY STATS -->
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-coins fa-lg" style="color:#3498DB;"></i>
                <div class="stat-amount" style="color:#2C3E50;">
                    Rs. <?= number_format($total_amount) ?>
                </div>
                <div class="stat-label">Total Fee</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-check-circle fa-lg" style="color:#27AE60;"></i>
                <div class="stat-amount" style="color:#27AE60;">
                    Rs. <?= number_format($total_paid) ?>
                </div>
                <div class="stat-label">Total Paid</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-exclamation-circle fa-lg" style="color:#E74C3C;"></i>
                <div class="stat-amount" style="color:#E74C3C;">
                    Rs. <?= number_format($total_unpaid) ?>
                </div>
                <div class="stat-label">Total Unpaid</div>
            </div>
        </div>
    </div>

    <!-- FEES TABLE -->
    <div class="fees-card">
        <div class="section-title">
            <i class="fas fa-list me-2"></i>Fee Details
        </div>

        <?php if (empty($fee_rows)): ?>
            <div style="text-align:center; padding:40px; color:#95a5a6;">
                <i class="fas fa-file-invoice fa-3x mb-3 d-block"></i>
                No fee records found.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table fees-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fee_rows as $i => $fee): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <i class="fas fa-file-alt me-2" style="color:#3498DB;"></i>
                                    <?= htmlspecialchars($fee['description']) ?>
                                </td>
                                <td>
                                    <strong>Rs. <?= number_format($fee['amount']) ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $due    = strtotime($fee['due_date']);
                                    $today  = time();
                                    $is_overdue = ($fee['status'] == 'Unpaid' && $due < $today);
                                    ?>
                                    <span <?= $is_overdue ? 'style="color:#E74C3C; font-weight:600;"' : '' ?>>
                                        <?= date('d M Y', $due) ?>
                                        <?php if ($is_overdue): ?>
                                            <span style="font-size:11px;">(Overdue)</span>
                                        <?php endif; ?>
                                    </span>
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
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="notice-box">
                <i class="fas fa-info-circle fa-lg"></i>
                Please deposit your fee before the due date at the university accounts office.
                Bring this challan printout when making payment.
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>