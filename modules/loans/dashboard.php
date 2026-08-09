<?php
// modules/loans/dashboard.php - Modern Dashboard
session_start();
include_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../users/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get user data
$user_query = "SELECT * FROM users WHERE id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get loans
$loans_query = "SELECT * FROM loans WHERE user_id = $user_id";
$loans = mysqli_query($conn, $loans_query);

$total_loans = mysqli_num_rows($loans);
$total_balance = $user['account_balance'] ?? 0;

// Calculate stats
$total_loan_amount = 0;
$pending_loans = 0;
$approved_loans = 0;
while($loan = mysqli_fetch_assoc($loans)) {
    $total_loan_amount += $loan['amount'];
    if($loan['status'] == 'pending') $pending_loans++;
    if($loan['status'] == 'approved') $approved_loans++;
}
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
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-modern fixed-top">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="bi bi-bank2"></i> EthioMF
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="apply.php">
                            <i class="bi bi-file-earmark-plus"></i> Apply Loan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../accounts/deposit.php">
                            <i class="bi bi-plus-circle"></i> Deposit
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../accounts/withdraw.php">
                            <i class="bi bi-dash-circle"></i> Withdraw
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../users/profile.php">
                            <i class="bi bi-person"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="dashboard-wrapper">
        <div class="container">
            <!-- Header -->
            <div class="dashboard-header fade-in-up">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <h1><i class="bi bi-person-circle"></i> Welcome, <?php echo $username; ?>!</h1>
                        <p class="mb-0">Here's an overview of your financial status</p>
                    </div>
                    <div class="col-md-5 text-md-end">
                        <span class="badge-modern primary">
                            <i class="bi bi-calendar"></i> <?php echo date('F d, Y'); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.1s;">
                    <div class="stat-card">
                        <div class="stat-icon primary">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div class="stat-number">$<?php echo number_format($total_balance, 2); ?></div>
                        <div class="stat-label">Account Balance</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.2s;">
                    <div class="stat-card">
                        <div class="stat-icon success">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div class="stat-number">$<?php echo number_format($total_loan_amount, 2); ?></div>
                        <div class="stat-label">Total Loans</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.3s;">
                    <div class="stat-card">
                        <div class="stat-icon warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <div class="stat-number"><?php echo $pending_loans; ?></div>
                        <div class="stat-label">Pending Loans</div>
                    </div>
                </div>
                <div class="col-md-3 fade-in-up" style="animation-delay: 0.4s;">
                    <div class="stat-card">
                        <div class="stat-icon info">
                            <i class="bi bi-file-text"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_loans; ?></div>
                        <div class="stat-label">Applications</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card-modern mb-4 fade-in-up" style="animation-delay: 0.5s;">
                <div class="card-body">
                    <h5 class="fw-700 mb-3"><i class="bi bi-lightning-fill text-warning"></i> Quick Actions</h5>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="apply.php" class="btn btn-gradient w-100">
                                <i class="bi bi-file-earmark-plus"></i> Apply Loan
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="../accounts/deposit.php" class="btn btn-gradient-success w-100" style="background: linear-gradient(135deg, #00D4AA 0%, #00B894 100%);">
                                <i class="bi bi-plus-circle"></i> Deposit
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="../accounts/withdraw.php" class="btn btn-gradient-danger w-100" style="background: linear-gradient(135deg, #FF6B6B 0%, #EE5A24 100%);">
                                <i class="bi bi-dash-circle"></i> Withdraw
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="repay.php" class="btn btn-outline-gradient w-100">
                                <i class="bi bi-arrow-repeat"></i> Repay Loan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loans Table -->
            <div class="card-modern fade-in-up" style="animation-delay: 0.6s;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span><i class="bi bi-list-ul text-primary"></i> Your Loan History</span>
                    <input type="text" class="form-control search-input w-auto" placeholder="Search loans..." style="min-width: 200px;">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Amount</th>
                                    <th>Interest</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                mysqli_data_seek($loans, 0);
                                while($loan = mysqli_fetch_assoc($loans)): 
                                ?>
                                <tr>
                                    <td><?php echo $counter++; ?></td>
                                    <td><strong>$<?php echo number_format($loan['amount'], 2); ?></strong></td>
                                    <td><?php echo $loan['interest_rate']; ?>%</td>
                                    <td>
                                        <?php if($loan['status'] == 'approved'): ?>
                                            <span class="badge-modern success">
                                                <i class="bi bi-check-circle"></i> Approved
                                            </span>
                                        <?php elseif($loan['status'] == 'pending'): ?>
                                            <span class="badge-modern warning">
                                                <i class="bi bi-clock"></i> Pending
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-modern danger">
                                                <i class="bi bi-x-circle"></i> Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $loan['application_date']; ?></td>
                                    <td>$<?php echo number_format($loan['remaining_balance'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($total_loans == 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                            No loans found. <a href="apply.php">Apply for your first loan</a>
                                        </td>
                                    </tr>
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