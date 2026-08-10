<?php
// modules/loans/dashboard.php - Modern Dashboard
session_start();
include_once '../../config/database.php';
include_once '../../includes/auth.php';

check_auth();

$user_id = (int)$_SESSION['user_id'];

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT * FROM loans WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$loans_result = mysqli_stmt_get_result($stmt);
$all_loans = mysqli_fetch_all($loans_result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$total_loans = count($all_loans);
$total_balance = $user['account_balance'] ?? 0;

$total_loan_amount = 0;
$pending_loans = 0;
$approved_loans = 0;
foreach ($all_loans as $loan) {
    $total_loan_amount += $loan['amount'];
    if ($loan['status'] == 'pending') $pending_loans++;
    if ($loan['status'] == 'approved') $approved_loans++;
}

function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Ethio Microfinance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="bi bi-bank2"></i> EthioMF</a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="apply.php"><i class="bi bi-file-earmark-plus"></i> Apply Loan</a></li>
                    <li class="nav-item"><a class="nav-link" href="../accounts/deposit.php"><i class="bi bi-plus-circle"></i> Deposit</a></li>
                    <li class="nav-item"><a class="nav-link" href="../accounts/withdraw.php"><i class="bi bi-dash-circle"></i> Withdraw</a></li>
                    <li class="nav-item"><a class="nav-link" href="../users/profile.php"><i class="bi bi-person"></i> Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="../../index.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="dashboard-wrapper">
        <div class="container">
            <div class="dashboard-header fade-in-up">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h1><i class="bi bi-person-circle"></i> Welcome, <?php echo e($username = $_SESSION['username'] ?? ''); ?>!</h1>
                        <p class="mb-0">Here's an overview of your financial status</p>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <span class="badge-modern primary"><i class="bi bi-calendar"></i> <?php echo date('F d, Y'); ?></span>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-card"><div class="stat-icon primary"><i class="bi bi-wallet2"></i></div>
                    <div class="stat-number">$<?php echo number_format((float)$total_balance, 2); ?></div>
                    <div class="stat-label">Account Balance</div></div>
                </div>
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-card"><div class="stat-icon success"><i class="bi bi-currency-dollar"></i></div>
                    <div class="stat-number">$<?php echo number_format((float)$total_loan_amount, 2); ?></div>
                    <div class="stat-label">Total Loans</div></div>
                </div>
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-card"><div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                    <div class="stat-number"><?php echo (int)$pending_loans; ?></div>
                    <div class="stat-label">Pending Loans</div></div>
                </div>
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.4s;">
                    <div class="stat-card"><div class="stat-icon info"><i class="bi bi-file-text"></i></div>
                    <div class="stat-number"><?php echo (int)$total_loans; ?></div>
                    <div class="stat-label">Applications</div></div>
                </div>
            </div>

            <div class="card-modern mb-4 fade-in-up" style="animation-delay: 0.5s;">
                <div class="card-body">
                    <h5 class="fw-700 mb-3"><i class="bi bi-lightning-fill text-warning"></i> Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-md-3"><a href="apply.php" class="btn btn-gradient w-100"><i class="bi bi-file-earmark-plus"></i> Apply Loan</a></div>
                        <div class="col-md-3"><a href="../accounts/deposit.php" class="btn btn-gradient-success w-100" style="background: linear-gradient(135deg, #00D4AA 0%, #00B894 100%);"><i class="bi bi-plus-circle"></i> Deposit</a></div>
                        <div class="col-md-3"><a href="../accounts/withdraw.php" class="btn btn-gradient-danger w-100" style="background: linear-gradient(135deg, #FF6B6B 0%, #EE5A24 100%);"><i class="bi bi-dash-circle"></i> Withdraw</a></div>
                        <div class="col-md-3"><a href="repay.php" class="btn btn-outline-gradient w-100"><i class="bi bi-arrow-repeat"></i> Repay Loan</a></div>
                    </div>
                </div>
            </div>

            <div class="card-modern fade-in-up" style="animation-delay: 0.6s;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-list-ul text-primary"></i> Your Loan History</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead><tr><th>#</th><th>Amount</th><th>Interest</th><th>Status</th><th>Date</th><th>Balance</th></tr></thead>
                            <tbody>
                                <?php $counter = 1; foreach ($all_loans as $loan): ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong>$<?php echo number_format((float)$loan['amount'], 2); ?></strong></td>
                                    <td><?php echo e($loan['interest_rate']); ?>%</td>
                                    <td>
                                        <?php if ($loan['status'] == 'approved'): ?>
                                            <span class="badge-modern success"><i class="bi bi-check-circle"></i> Approved</span>
                                        <?php elseif ($loan['status'] == 'pending'): ?>
                                            <span class="badge-modern warning"><i class="bi bi-clock"></i> Pending</span>
                                        <?php else: ?>
                                            <span class="badge-modern danger"><i class="bi bi-x-circle"></i> Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($loan['application_date']); ?></td>
                                    <td>$<?php echo number_format((float)$loan['remaining_balance'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if ($total_loans == 0): ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                        No loans found. <a href="apply.php">Apply for your first loan</a>
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
